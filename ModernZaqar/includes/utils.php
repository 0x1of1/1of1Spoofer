<?php

/**
 * 1of1Spoofer - Utility Functions
 * 
 * This file contains utility functions used throughout the application.
 */

// Make sure we have the config
require_once __DIR__ . '/../config.php';

// Make sure functions are only defined once
if (!function_exists('log_message')) {
    /**
     * Logs a message to the application log
     *
     * @param string $message The message to log
     * @param string $level The log level (debug, info, warning, error)
     * @param array $context Additional context data
     * @return bool Whether the log was written successfully
     */
    function log_message($message, $level = 'info', $context = [])
    {
        if (!config('logging.enabled', true)) {
            return false;
        }

        $logDir = config('logging.dir', __DIR__ . '/../logs');
        $dateFormat = config('logging.file_format', 'Y-m-d');
        $filename = date($dateFormat) . '.log';
        $filepath = $logDir . '/' . $filename;

        // Check if the logging level is appropriate
        $logLevels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        $configLevel = config('logging.level', 'info');

        if (!isset($logLevels[$level]) || !isset($logLevels[$configLevel]) || $logLevels[$level] < $logLevels[$configLevel]) {
            return false;
        }

        // Format the log entry
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$ip}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        // Write the log
        return file_put_contents($filepath, $logEntry, FILE_APPEND | LOCK_EX) !== false;
    }
}

/**
 * Sanitizes user input
 *
 * @param string $input The input to sanitize
 * @param bool $allowHtml Whether to allow HTML tags
 * @return string The sanitized input
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($input, $allowHtml = false)
    {
        $input = trim($input);

        if ($allowHtml) {
            // Allow certain HTML tags but filter potentially harmful content
            $input = filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            // Strip all HTML tags
            $input = strip_tags($input);
        }

        return $input;
    }
}

/**
 * Validates an email address
 *
 * @param string $email The email to validate
 * @return bool Whether the email is valid
 */
if (!function_exists('is_valid_email')) {
    function is_valid_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Generates a CSRF token and stores it in the session
 *
 * @param string $formName The name of the form
 * @return string The CSRF token
 */
if (!function_exists('generate_csrf_token')) {
    /**
     * Generates a CSRF token for a form
     *
     * @param string $formName The form name
     * @return string The CSRF token
     */
    function generate_csrf_token($formName = 'default')
    {
        if (!session_id()) {
            session_start();
        }

        // Initialize the session tokens array if it doesn't exist
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        // Generate a new token if one doesn't exist for this form
        if (!isset($_SESSION['csrf_tokens'][$formName])) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf_tokens'][$formName] = $token;
            log_message("Generated new CSRF token for form: {$formName}", 'debug');
        } else {
            $token = $_SESSION['csrf_tokens'][$formName];
            log_message("Using existing CSRF token for form: {$formName}", 'debug');
        }

        return $token;
    }
}

/**
 * Verifies a CSRF token
 *
 * @param string $token The token to verify
 * @param string $formName The name of the form
 * @return bool Whether the token is valid
 */
if (!function_exists('verify_csrf_token')) {
    /**
     * Verifies a CSRF token
     *
     * @param string $token The token to verify
     * @param string $formName The form name
     * @return bool Whether the token is valid
     */
    function verify_csrf_token($token, $formName = 'default')
    {
        // Debug log
        log_message("Verifying CSRF token for form: {$formName}", 'debug', [
            'provided_token' => $token,
            'stored_tokens' => isset($_SESSION['csrf_tokens']) ? array_keys($_SESSION['csrf_tokens']) : []
        ]);

        if (!isset($_SESSION['csrf_tokens'][$formName])) {
            log_message("CSRF token not found for form: {$formName}", 'warning');
            return false;
        }

        $valid = hash_equals($_SESSION['csrf_tokens'][$formName], $token);

        if ($valid) {
            // Remove the token after successful validation
            unset($_SESSION['csrf_tokens'][$formName]);
            log_message("CSRF token validated successfully for form: {$formName}", 'debug');
        } else {
            log_message("CSRF token validation failed for form: {$formName}", 'warning', [
                'expected' => $_SESSION['csrf_tokens'][$formName],
                'received' => $token
            ]);
        }

        return $valid;
    }
}

/**
 * Checks if the user has exceeded the rate limit
 *
 * @return bool Whether the user has exceeded the rate limit
 */
if (!function_exists('check_rate_limit')) {
    function check_rate_limit()
    {
        if (!config('security.rate_limit.enabled', true)) {
            return true; // Rate limiting disabled
        }

        if (!session_id()) {
            session_start();
        }

        $now = time();
        $hourly_limit = config('security.rate_limit.max_emails_per_hour', 10);
        $daily_limit = config('security.rate_limit.max_emails_per_day', 50);

        // Initialize rate limiting data if not exists
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [
                'hourly' => [
                    'count' => 0,
                    'reset' => $now + 3600
                ],
                'daily' => [
                    'count' => 0,
                    'reset' => $now + 86400
                ]
            ];
        }

        // Reset hourly counter if needed
        if ($_SESSION['rate_limit']['hourly']['reset'] <= $now) {
            $_SESSION['rate_limit']['hourly']['count'] = 0;
            $_SESSION['rate_limit']['hourly']['reset'] = $now + 3600;
        }

        // Reset daily counter if needed
        if ($_SESSION['rate_limit']['daily']['reset'] <= $now) {
            $_SESSION['rate_limit']['daily']['count'] = 0;
            $_SESSION['rate_limit']['daily']['reset'] = $now + 86400;
        }

        // Check if limits are exceeded
        if ($_SESSION['rate_limit']['hourly']['count'] >= $hourly_limit) {
            return false;
        }

        if ($_SESSION['rate_limit']['daily']['count'] >= $daily_limit) {
            return false;
        }

        return true;
    }
}

/**
 * Increments the rate limit counters
 */
if (!function_exists('increment_rate_limit')) {
    function increment_rate_limit()
    {
        if (!config('security.rate_limit.enabled', true)) {
            return;
        }

        if (!session_id()) {
            session_start();
        }

        // Make sure rate limit data is initialized
        check_rate_limit();

        // Increment counters
        $_SESSION['rate_limit']['hourly']['count']++;
        $_SESSION['rate_limit']['daily']['count']++;
    }
}

/**
 * Gets the time remaining until rate limit reset
 *
 * @return array The time remaining for hourly and daily limits
 */
if (!function_exists('get_rate_limit_reset_time')) {
    function get_rate_limit_reset_time()
    {
        if (!session_id()) {
            session_start();
        }

        // Make sure rate limit data is initialized
        check_rate_limit();

        $now = time();
        $hourly_reset = $_SESSION['rate_limit']['hourly']['reset'] - $now;
        $daily_reset = $_SESSION['rate_limit']['daily']['reset'] - $now;

        return [
            'hourly' => $hourly_reset > 0 ? $hourly_reset : 0,
            'daily' => $daily_reset > 0 ? $daily_reset : 0
        ];
    }
}

/**
 * Validates a file upload
 *
 * @param array $file The uploaded file ($_FILES array item)
 * @return array Result with status and message
 */
if (!function_exists('validate_file_upload')) {
    function validate_file_upload($file)
    {
        if (!config('uploads.enabled', true)) {
            return ['status' => false, 'message' => 'File uploads are disabled.'];
        }

        // Check if the file was uploaded properly
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $error = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
                default => 'Unknown upload error.'
            };
            return ['status' => false, 'message' => $error];
        }

        // Check file size
        $maxSize = config('uploads.max_size', 5 * 1024 * 1024);
        if ($file['size'] > $maxSize) {
            return [
                'status' => false,
                'message' => 'File is too large. Maximum size is ' . ($maxSize / 1024 / 1024) . 'MB.'
            ];
        }

        // Check file type
        $allowedTypes = config('uploads.allowed_types', []);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $isAllowed = false;
        foreach ($allowedTypes as $extension => $mime) {
            if ($mime === $mimeType) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return ['status' => false, 'message' => 'File type not allowed.'];
        }

        // Scan file content if enabled
        if (config('uploads.scan_uploads', true)) {
            // Basic content check - scan for PHP code or other potentially malicious content
            $content = file_get_contents($file['tmp_name']);
            $suspiciousPatterns = [
                '/<\?php/i',
                '/<script/i',
                '/eval\(/i',
                '/exec\(/i',
                '/system\(/i',
                '/passthru\(/i',
                '/shell_exec\(/i',
                '/base64_decode\(/i'
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    return ['status' => false, 'message' => 'File contains potentially malicious content.'];
                }
            }
        }

        return ['status' => true, 'message' => 'File is valid.'];
    }
}

/**
 * Format time duration in a human-readable format
 *
 * @param int $seconds The number of seconds
 * @return string Formatted time string
 */
if (!function_exists('format_time_duration')) {
    function format_time_duration($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' second' . ($seconds != 1 ? 's' : '');
        } else if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '');
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . ' hour' . ($hours != 1 ? 's' : '') .
                ($minutes > 0 ? ' and ' . $minutes . ' minute' . ($minutes != 1 ? 's' : '') : '');
        }
    }
}

/**
 * Determines if dark mode should be used based on user preference or config
 *
 * @return bool Whether dark mode should be used
 */
if (!function_exists('should_use_dark_mode')) {
    function should_use_dark_mode()
    {
        // Check cookie for user preference
        if (isset($_COOKIE['dark_mode'])) {
            return $_COOKIE['dark_mode'] === 'true';
        }

        // Check config for default theme
        $default_theme = config('ui.theme', 'light');
        if ($default_theme === 'dark') {
            return true;
        } else if ($default_theme === 'auto') {
            // Try to detect system preference (not fully reliable)
            if (isset($_SERVER['HTTP_SEC_CH_PREFERS_COLOR_SCHEME'])) {
                return $_SERVER['HTTP_SEC_CH_PREFERS_COLOR_SCHEME'] === 'dark';
            }
        }

        // Default to light mode
        return false;
    }
}

/**
 * Formats a file size in bytes to a human-readable string
 *
 * @param int $bytes The size in bytes
 * @param int $precision The number of decimal places
 * @return string The formatted file size
 */
if (!function_exists('format_file_size')) {
    function format_file_size($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Generate CSRF token for forms
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Validate CSRF token
if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token)
    {
        // Check if the token exists and matches
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
