<?php

/**
 * 1of1Spoofer - Domain Security Analyzer
 * 
 * This file contains functions to analyze a domain's email security configuration
 * by checking SPF, DKIM, and DMARC records.
 */

// Make sure we have the config and utils
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/utils.php';

/**
 * Analyze a domain's email security configuration
 *
 * @param string $domain The domain to analyze
 * @return array Analysis results
 */
if (!function_exists('analyze_domain_security')) {
    function analyze_domain_security($domain)
    {
        // Add debug logs to track execution
        error_log("Starting domain analysis for: " . $domain);

        if (!config('analyzer.enabled', true)) {
            return ['status' => 'error', 'message' => 'Domain analyzer is disabled.'];
        }

        // Validate the domain
        $domain = sanitize_input($domain);
        if (empty($domain)) {
            return ['status' => 'error', 'message' => 'Domain cannot be empty.'];
        }

        // Extract the domain from an email address if provided
        if (strpos($domain, '@') !== false) {
            $domain = explode('@', $domain)[1];
        }

        // Remove protocol and path if a URL was provided
        $domain = preg_replace('/(https?:\/\/)?(www\.)?([^\/]+).*/', '$3', $domain);

        // Initialize results
        $results = [
            'domain' => $domain,
            'status' => 'success',
            'spf' => ['exists' => false, 'record' => null, 'valid' => false, 'policy' => null],
            'dmarc' => ['exists' => false, 'record' => null, 'valid' => false, 'policy' => null],
            'mx' => ['exists' => false, 'records' => []],
            'vulnerability_score' => 0,
            'summary' => '',
            'spoofable' => true
        ];

        try {
            // Check SPF record
            if (config('analyzer.check_spf', true)) {
                $results['spf'] = check_spf_record($domain);
            }

            // Check DMARC record
            if (config('analyzer.check_dmarc', true)) {
                $results['dmarc'] = check_dmarc_record($domain);
            }

            // Check MX records
            $results['mx'] = check_mx_records($domain);

            // Calculate vulnerability score (0-10, where 0 is most vulnerable)
            $results['vulnerability_score'] = calculate_vulnerability_score(
                $results['spf'],
                $results['dmarc']
            );

            // Generate summary
            $results['summary'] = get_vulnerability_summary($results['vulnerability_score']);

            // Determine if domain is spoofable
            $results['spoofable'] = is_domain_spoofable($results);

            // Log the analysis
            log_message("Domain analysis: {$domain}", 'info', [
                'spf_exists' => $results['spf']['exists'],
                'dmarc_exists' => $results['dmarc']['exists'],
                'score' => $results['vulnerability_score'],
                'spoofable' => $results['spoofable']
            ]);
        } catch (Exception $e) {
            $results['status'] = 'error';
            $results['message'] = 'Error analyzing domain: ' . $e->getMessage();
            log_message("Domain analysis error: {$domain} - {$e->getMessage()}", 'error');
        }

        error_log("Completed domain analysis for: " . $domain);
        return $results;
    }
}

/**
 * Check if a domain has an SPF record and analyze its policy
 *
 * @param string $domain The domain to check
 * @return array SPF record analysis
 */
if (!function_exists('check_spf_record')) {
    function check_spf_record($domain)
    {
        $result = [
            'exists' => false,
            'record' => null,
            'valid' => false,
            'policy' => null,
            'all_mechanism' => null,
            'score' => 0  // 0 = vulnerable, 1 = partially protected, 2 = protected
        ];

        try {
            $timeout = config('analyzer.dns_timeout', 5);
            $records = dns_get_record($domain, DNS_TXT);

            foreach ($records as $record) {
                $txt = $record['txt'] ?? '';
                if (stripos($txt, 'v=spf1') === 0) {
                    $result['exists'] = true;
                    $result['record'] = $txt;
                    $result['valid'] = true;

                    // Check the SPF policy (the 'all' mechanism)
                    if (preg_match('/ -all\b/i', $txt)) {
                        $result['all_mechanism'] = 'hard fail (-all)';
                        $result['policy'] = 'restrictive';
                        $result['score'] = 2;
                    } else if (preg_match('/ ~all\b/i', $txt)) {
                        $result['all_mechanism'] = 'soft fail (~all)';
                        $result['policy'] = 'restrictive';
                        $result['score'] = 2;
                    } else if (preg_match('/ \?all\b/i', $txt)) {
                        $result['all_mechanism'] = 'neutral (?all)';
                        $result['policy'] = 'neutral';
                        $result['score'] = 1;
                    } else if (preg_match('/ \+all\b/i', $txt)) {
                        $result['all_mechanism'] = 'pass (+all)';
                        $result['policy'] = 'permissive';
                        $result['score'] = 0;
                    } else {
                        // Default to +all if no mechanism specified
                        $result['all_mechanism'] = 'implicit pass (+all)';
                        $result['policy'] = 'permissive';
                        $result['score'] = 0;
                    }

                    break;
                }
            }
        } catch (Exception $e) {
            // No SPF record found or error
        }

        return $result;
    }
}

/**
 * Check if a domain has a DMARC record and analyze its policy
 *
 * @param string $domain The domain to check
 * @return array DMARC record analysis
 */
if (!function_exists('check_dmarc_record')) {
    function check_dmarc_record($domain)
    {
        $result = [
            'exists' => false,
            'record' => null,
            'valid' => false,
            'policy' => null,
            'policy_value' => null,
            'pct' => null,
            'rua' => null,
            'score' => 0  // 0 = vulnerable, 1 = partially protected, 2 = protected
        ];

        try {
            $timeout = config('analyzer.dns_timeout', 5);
            $records = dns_get_record("_dmarc.{$domain}", DNS_TXT);

            foreach ($records as $record) {
                $txt = $record['txt'] ?? '';
                if (stripos($txt, 'v=DMARC1') === 0) {
                    $result['exists'] = true;
                    $result['record'] = $txt;
                    $result['valid'] = true;

                    // Extract DMARC policy
                    if (preg_match('/p=reject/i', $txt)) {
                        $result['policy'] = 'reject';
                        $result['policy_value'] = 'reject';
                        $result['score'] = 2;
                    } else if (preg_match('/p=quarantine/i', $txt)) {
                        $result['policy'] = 'quarantine';
                        $result['policy_value'] = 'quarantine';
                        $result['score'] = 1;
                    } else if (preg_match('/p=none/i', $txt)) {
                        $result['policy'] = 'none (monitoring only)';
                        $result['policy_value'] = 'none';
                        $result['score'] = 0;
                    } else {
                        $result['policy'] = 'unknown';
                        $result['score'] = 0;
                    }

                    // Extract percentage (pct)
                    if (preg_match('/pct=(\d+)/i', $txt, $matches)) {
                        $result['pct'] = $matches[1];
                    }

                    // Extract reporting URI
                    if (preg_match('/rua=mailto:([^\s;]+)/i', $txt, $matches)) {
                        $result['rua'] = $matches[1];
                    }

                    break;
                }
            }
        } catch (Exception $e) {
            // No DMARC record found or error
        }

        return $result;
    }
}

/**
 * Check if a domain has MX records
 *
 * @param string $domain The domain to check
 * @return array MX records analysis
 */
if (!function_exists('check_mx_records')) {
    function check_mx_records($domain)
    {
        $result = [
            'exists' => false,
            'records' => []
        ];

        try {
            $timeout = config('analyzer.dns_timeout', 5);
            $records = dns_get_record($domain, DNS_MX);

            if (!empty($records)) {
                $result['exists'] = true;
                foreach ($records as $record) {
                    $result['records'][] = "{$record['pri']} {$record['target']}";
                }
            }
        } catch (Exception $e) {
            // No MX records found or error
        }

        return $result;
    }
}

/**
 * Calculate vulnerability score based on SPF and DMARC records
 *
 * @param array $spf SPF analysis results
 * @param array $dmarc DMARC analysis results
 * @return int Vulnerability score (0-10, where 10 is least vulnerable)
 */
if (!function_exists('calculate_vulnerability_score')) {
    function calculate_vulnerability_score($spf, $dmarc)
    {
        $score = 0;

        // Add SPF score (0-4)
        if (isset($spf['score'])) {
            $score += $spf['score'] * 2; // Convert 0-2 to 0-4
        }

        // Add DMARC score (0-4)
        if (isset($dmarc['score'])) {
            $score += $dmarc['score'] * 2; // Convert 0-2 to 0-4
        }

        // Add 2 points if both exist, for having a complete email security setup
        if ($spf['exists'] && $dmarc['exists']) {
            $score += 2;
        }

        // Return the final score (capped at 10)
        return min(10, $score);
    }
}

/**
 * Get a human-readable summary of the vulnerability score
 *
 * @param int $score Vulnerability score (0-10)
 * @return string Summary text
 */
if (!function_exists('get_vulnerability_summary')) {
    function get_vulnerability_summary($score)
    {
        if ($score >= 8) {
            return "This domain has strong email security measures in place. It is unlikely to be spoofed successfully.";
        } else if ($score >= 5) {
            return "This domain has moderate email security. Some spoofing attempts may be blocked, but others might succeed.";
        } else if ($score >= 3) {
            return "This domain has basic email security. Many spoofing attempts are likely to succeed.";
        } else {
            return "This domain has minimal or no email security. It is highly vulnerable to spoofing attacks.";
        }
    }
}

/**
 * Determine if a domain is likely to be spoofable based on its security records
 *
 * @param array $results Analysis results containing SPF and DMARC data
 * @return bool Whether the domain is likely to be spoofable
 */
if (!function_exists('is_domain_spoofable')) {
    function is_domain_spoofable($results)
    {
        // If SPF exists with restrictive policy and DMARC with reject/quarantine, domain is well-protected
        if (
            isset($results['spf']['exists']) &&
            $results['spf']['exists'] &&
            isset($results['spf']['policy']) &&
            $results['spf']['policy'] === 'restrictive' &&
            isset($results['dmarc']['exists']) &&
            $results['dmarc']['exists'] &&
            isset($results['dmarc']['policy_value']) &&
            in_array($results['dmarc']['policy_value'], ['reject', 'quarantine'])
        ) {
            return false;
        }

        // If vulnerability score is 7 or higher, consider the domain protected
        if (isset($results['vulnerability_score']) && $results['vulnerability_score'] >= 7) {
            return false;
        }

        // Otherwise, consider the domain potentially spoofable
        return true;
    }
}

/**
 * Get security recommendations for a domain based on analysis
 *
 * @param array $results Domain analysis results
 * @return array Recommendations
 */
if (!function_exists('get_security_recommendations')) {
    function get_security_recommendations($results)
    {
        $recommendations = [];

        // SPF recommendations
        if (!$results['spf']['exists']) {
            $recommendations[] = "Implement an SPF record (e.g., 'v=spf1 include:_spf.example.com ~all').";
        } else if ($results['spf']['policy'] !== 'restrictive') {
            $recommendations[] = "Strengthen your SPF policy by using '-all' or '~all' to indicate that non-matching senders should be rejected or treated with suspicion.";
        }

        // DMARC recommendations
        if (!$results['dmarc']['exists']) {
            $recommendations[] = "Implement a DMARC record (e.g., 'v=DMARC1; p=reject; rua=mailto:dmarc@{$results['domain']}').";
        } else if ($results['dmarc']['policy_value'] === 'none') {
            $recommendations[] = "Strengthen your DMARC policy from 'none' to 'quarantine' or 'reject' after monitoring reports.";
        } else if ($results['dmarc']['policy_value'] === 'quarantine') {
            $recommendations[] = "Consider strengthening your DMARC policy from 'quarantine' to 'reject' for better protection.";
        }

        // DKIM recommendation
        if (config('analyzer.check_dkim', true)) {
            $recommendations[] = "Implement DKIM (DomainKeys Identified Mail) to cryptographically sign emails from your domain.";
        }

        return $recommendations;
    }
}
