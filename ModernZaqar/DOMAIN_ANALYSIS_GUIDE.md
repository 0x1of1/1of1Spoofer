# Domain Security Analysis Guide

This guide explains how to use 1of1Spoofer's Domain Security Analyzer feature to determine if a domain is vulnerable to email spoofing.

## What the Analyzer Checks

The Domain Security Analyzer evaluates a domain's email security posture by checking for the presence and configuration of:

1. **SPF (Sender Policy Framework)** - Verifies whether a domain has properly configured which servers are authorized to send email on its behalf.

2. **DMARC (Domain-based Message Authentication, Reporting, and Conformance)** - Checks if the domain has a policy for handling emails that fail authentication checks.

3. **DKIM (DomainKeys Identified Mail)** - Checks for the presence of DKIM record selectors, which allow email receivers to verify that messages weren't altered in transit.

4. **MX Records** - Identifies the mail servers responsible for accepting email for the domain.

## Security Rating Explanation

The analyzer assigns a security rating based on the findings:

### High Risk (Red)

- No SPF record
- No DMARC record
- Weak or missing email security configurations

### Medium Risk (Yellow)

- SPF record exists but is misconfigured
- DMARC exists but is set to policy "none" (monitoring only)
- Partial email security protections

### Low Risk (Green)

- Properly configured SPF record
- DMARC policy set to "quarantine" or "reject"
- DKIM appears to be configured
- Comprehensive email security protections

## How to Perform a Domain Analysis

1. From the 1of1Spoofer interface, locate the **Domain Security Analyzer** section (usually in the right sidebar)

2. Enter the domain name you want to analyze (e.g., `example.com`)

3. Click the **Analyze** button

4. Review the results, which will show:
   - SPF Record details
   - DMARC Policy information
   - DKIM Records (if found)
   - MX Records
   - Overall security rating

## Understanding the Results

### SPF Records

```
v=spf1 include:_spf.example.com include:mailgun.org -all
```

Key elements to understand:

- `v=spf1` - Version of SPF
- `include:domain.com` - Includes the SPF record from another domain
- `ip4:192.168.1.1` - Authorizes a specific IP address
- `-all` - Strict policy (fail emails not matching)
- `~all` - Soft fail policy
- `?all` - Neutral policy
- `+all` - Allow all (very insecure)

### DMARC Records

```
v=DMARC1; p=reject; rua=mailto:reports@example.com; pct=100;
```

Key elements to understand:

- `v=DMARC1` - Version of DMARC
- `p=reject` - Policy to reject failing emails (most secure)
- `p=quarantine` - Policy to quarantine failing emails (moderately secure)
- `p=none` - Monitoring only, no action (least secure)
- `rua=` - Where aggregate reports should be sent
- `pct=100` - Percentage of messages subject to filtering

### DKIM Records

The analyzer checks for common DKIM selectors (`default`, `google`, `k1`, etc.) and reports if they exist.

### MX Records

Lists the mail servers that handle email for the domain, including priorities.

## Interpreting Spoofability

### Easy to Spoof

- No SPF or with `+all`
- No DMARC
- No DKIM

### Moderately Difficult to Spoof

- SPF with `~all`
- DMARC with `p=none`
- Some email security measures in place

### Difficult to Spoof

- SPF with `-all`
- DMARC with `p=reject` or `p=quarantine`
- DKIM implemented

## Best Practices for Email Security

If you're analyzing your own domain, consider implementing:

1. **SPF** with `-all` or at minimum `~all`
2. **DMARC** with `p=quarantine` or `p=reject`
3. **DKIM** signing for all outgoing emails

## Additional Resources

- [SPF Record Syntax](https://dmarcian.com/spf-syntax-table/)
- [DMARC.org Resources](https://dmarc.org/resources/)
- [DKIM.org](https://dkim.org/)

Remember that this analysis is provided for educational purposes to help understand email security measures.

---

_Last updated: March 20, 2024_
