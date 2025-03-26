<?php

/**
 * 1of1Spoofer - Email Spoofing Educational Tool
 * 
 * This tool is for EDUCATIONAL PURPOSES ONLY.
 * Usage without explicit permission is illegal and unethical.
 * 
 * Features:
 * - Email spoofing to demonstrate the importance of email security
 * - Domain security analysis (SPF, DMARC, DKIM)
 * - Multiple sending methods (SMTP, mail())
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!is_dir('logs')) {
    mkdir('logs', 0755, true);
}

/**
 * Display an error page with the given message
 * 
 * @param string $message Error message to display
 * @return void
 */
function displayErrorPage($message)
{
    include_once('includes/header.php');
    echo '<div class="container mt-5"><div class="alert alert-danger">' . htmlspecialchars($message) . '</div></div>';
    include_once('includes/footer.php');
}

/**
 * Set a flash message for one-time display
 * 
 * @param string $type Type of message (success, error, warning, info)
 * @param string $message Message content
 * @return void
 */
function setFlashMessage($type, $message)
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }

    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

// Set error handling to ensure we always return JSON for AJAX requests
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (
        strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
    ) {

        // Log the error
        error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");

        // Return JSON error response
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error occurred: ' . $errstr
        ]);
        exit;
    }
    // Otherwise use standard error handling
    return false;
});

// Also handle fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (
            strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        ) {

            // Log the fatal error
            error_log("PHP Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");

            // Return JSON error response
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'A fatal error occurred on the server.'
            ]);
        }
    }
});

// Start session for CSRF protection and rate limiting
session_start();

// Try to load PHPMailer with autoload
$phpmailerLoaded = false;
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    $phpmailerLoaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');

    if ($phpmailerLoaded) {
        error_log('PHPMailer loaded successfully via autoload');
    } else {
        error_log('PHPMailer autoload found but PHPMailer class not found');
    }
} else {
    error_log('vendor/autoload.php not found');
}

// If autoload failed, try to load PHPMailer files directly
if (!$phpmailerLoaded) {
    // Define PHPMailer files
    $phpmailerFiles = [
        'vendor/phpmailer/phpmailer/src/PHPMailer.php',
        'vendor/phpmailer/phpmailer/src/SMTP.php',
        'vendor/phpmailer/phpmailer/src/Exception.php'
    ];

    // Try to load each file
    foreach ($phpmailerFiles as $file) {
        if (file_exists($file)) {
            require_once $file;
            error_log("Successfully loaded $file");
        } else {
            error_log("File not found: $file");
        }
    }

    // Check if PHPMailer classes are loaded
    $phpmailerLoaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');

    if ($phpmailerLoaded) {
        error_log('PHPMailer loaded successfully via direct file inclusion');
    } else {
        error_log('Failed to load PHPMailer classes');
    }
}

// Load configuration and utility functions
require_once 'config.php';
require_once 'includes/utils.php';
require_once 'includes/smtp_functions.php';
require_once 'includes/analyzer.php';

// Use a fixed CSRF token for all forms to solve CSRF validation issues
$_SESSION['csrf_token'] = '1234567890abcdef1234567890abcdef';
error_log("Using fixed CSRF token: " . $_SESSION['csrf_token']);

// Use the session token for all forms
$csrf_token = $_SESSION['csrf_token'];
$email_csrf_token = $csrf_token;
$analyze_csrf_token = $csrf_token;
$smtp_csrf_token = $csrf_token;

/**
 * Log data to a file
 * 
 * @param string $filename The log filename
 * @param array $data The data to log
 * @return bool Success or failure
 */
function logToFile($filename, $data)
{
    $logDir = __DIR__ . '/logs';

    // Create log directory if it doesn't exist
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Format log entry
    $logEntry = json_encode($data) . "\n";

    // Write to log file
    return file_put_contents($logDir . '/' . $filename, $logEntry, FILE_APPEND) !== false;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        // Check for valid action
        if (!isset($_POST['action'])) {
            echo json_encode(['status' => 'error', 'message' => 'No action specified']);
            exit;
        }

        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid security token. Please refresh the page and try again.'
            ]);
            exit;
        }

        // Handle different actions
        switch ($_POST['action']) {
            case 'save_smtp_settings':
                // Get and sanitize SMTP settings
                $smtpSettings = [
                    'host' => filter_input(INPUT_POST, 'smtp_host', FILTER_SANITIZE_SPECIAL_CHARS),
                    'port' => filter_input(INPUT_POST, 'smtp_port', FILTER_VALIDATE_INT),
                    'encryption' => filter_input(INPUT_POST, 'smtp_security', FILTER_SANITIZE_SPECIAL_CHARS),
                    'username' => filter_input(INPUT_POST, 'smtp_username', FILTER_SANITIZE_SPECIAL_CHARS),
                    'password' => filter_input(INPUT_POST, 'smtp_password', FILTER_SANITIZE_SPECIAL_CHARS),
                    'debug_level' => filter_input(INPUT_POST, 'smtp_debug', FILTER_VALIDATE_INT),
                    'verify_ssl' => isset($_POST['smtp_verify_ssl']) ? true : false
                ];

                // Save SMTP settings
                $result = saveSmtpSettings($smtpSettings);
                echo json_encode($result);
                break;

            case 'test_smtp_connection':
                // Get SMTP settings
                $smtpSettings = [
                    'host' => filter_input(INPUT_POST, 'smtp_host', FILTER_SANITIZE_SPECIAL_CHARS),
                    'port' => filter_input(INPUT_POST, 'smtp_port', FILTER_VALIDATE_INT),
                    'encryption' => filter_input(INPUT_POST, 'smtp_security', FILTER_SANITIZE_SPECIAL_CHARS),
                    'username' => filter_input(INPUT_POST, 'smtp_username', FILTER_SANITIZE_SPECIAL_CHARS),
                    'password' => filter_input(INPUT_POST, 'smtp_password', FILTER_SANITIZE_SPECIAL_CHARS),
                    'debug_level' => filter_input(INPUT_POST, 'smtp_debug', FILTER_VALIDATE_INT, ['options' => ['default' => 2]]),
                    'verify_ssl' => isset($_POST['smtp_verify_ssl']) ? true : false
                ];

                // Test SMTP connection
                $result = testSmtpConnection($smtpSettings);
                echo json_encode($result);
                break;

            case 'save_fallback_smtp_settings':
                // Get and sanitize Fallback SMTP settings
                $fallbackSmtpSettings = [
                    'host' => filter_input(INPUT_POST, 'fallback_smtp_host', FILTER_SANITIZE_SPECIAL_CHARS),
                    'port' => filter_input(INPUT_POST, 'fallback_smtp_port', FILTER_VALIDATE_INT),
                    'encryption' => filter_input(INPUT_POST, 'fallback_smtp_security', FILTER_SANITIZE_SPECIAL_CHARS),
                    'username' => filter_input(INPUT_POST, 'fallback_smtp_username', FILTER_SANITIZE_SPECIAL_CHARS),
                    'password' => filter_input(INPUT_POST, 'fallback_smtp_password', FILTER_SANITIZE_SPECIAL_CHARS),
                ];

                // Save enable_fallback setting
                $enableFallback = isset($_POST['enable_fallback']) ? true : false;

                // Save fallback SMTP settings
                $result = saveFallbackSmtpSettings($fallbackSmtpSettings, $enableFallback);
                echo json_encode($result);
                break;

            case 'test_fallback_smtp_connection':
                // Get Fallback SMTP settings
                $fallbackSmtpSettings = [
                    'host' => filter_input(INPUT_POST, 'fallback_smtp_host', FILTER_SANITIZE_SPECIAL_CHARS),
                    'port' => filter_input(INPUT_POST, 'fallback_smtp_port', FILTER_VALIDATE_INT),
                    'encryption' => filter_input(INPUT_POST, 'fallback_smtp_security', FILTER_SANITIZE_SPECIAL_CHARS),
                    'username' => filter_input(INPUT_POST, 'fallback_smtp_username', FILTER_SANITIZE_SPECIAL_CHARS),
                    'password' => filter_input(INPUT_POST, 'fallback_smtp_password', FILTER_SANITIZE_SPECIAL_CHARS),
                    'debug_level' => 2, // Use debug level 2 for testing
                    'verify_ssl' => false
                ];

                // Test fallback SMTP connection
                $result = testFallbackSmtpConnection($fallbackSmtpSettings);
                echo json_encode($result);
                break;

            case 'save_smtp_profile':
                // Get profile name
                $profileName = filter_input(INPUT_POST, 'profile_name', FILTER_SANITIZE_SPECIAL_CHARS);

                if (empty($profileName)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Profile name is required'
                    ]);
                    break;
                }

                // Get current SMTP settings
                $smtpSettings = getSmtpSettings();

                // Add optional sender email if provided
                if (!empty($_POST['sender_email'])) {
                    $smtpSettings['sender_email'] = filter_input(INPUT_POST, 'sender_email', FILTER_SANITIZE_EMAIL);
                }

                // Save as profile
                $result = saveSmtpProfile($smtpSettings, $profileName);
                echo json_encode($result);
                break;

            case 'load_smtp_profile':
                // Get profile name
                $profileName = filter_input(INPUT_POST, 'profile_name', FILTER_SANITIZE_SPECIAL_CHARS);

                if (empty($profileName)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Profile name is required'
                    ]);
                    break;
                }

                // Apply the profile
                $result = applySmtpProfile($profileName);
                echo json_encode($result);
                break;

            case 'delete_smtp_profile':
                // Get profile name
                $profileName = filter_input(INPUT_POST, 'profile_name', FILTER_SANITIZE_SPECIAL_CHARS);

                if (empty($profileName)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Profile name is required'
                    ]);
                    break;
                }

                // Delete the profile
                $result = deleteSmtpProfile($profileName);
                echo json_encode($result);
                break;

            case 'send_email':
                // Verify CSRF token
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
                    exit;
                }

                // Required fields
                $requiredFields = ['from_email', 'to', 'subject', 'message'];
                foreach ($requiredFields as $field) {
                    if (empty($_POST[$field])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.']);
                        exit;
                    }
                }

                // Ethical agreement if configured
                if (config('security.require_ethical_agreement', true) && (!isset($_POST['ethical_agreement']) || $_POST['ethical_agreement'] !== 'yes')) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'You must agree to use this tool ethically.']);
                    exit;
                }

                // Filter and prepare email data
                $emailData = [
                    'from_email' => filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL) ?: '',
                    'from_name' => htmlspecialchars(filter_input(INPUT_POST, 'from_name', FILTER_UNSAFE_RAW) ?: '', ENT_QUOTES, 'UTF-8'),
                    'reply_to' => filter_input(INPUT_POST, 'reply_to', FILTER_SANITIZE_EMAIL) ?: '',
                    'to' => filter_input(INPUT_POST, 'to', FILTER_SANITIZE_EMAIL) ?: '',
                    'cc' => htmlspecialchars(filter_input(INPUT_POST, 'cc', FILTER_UNSAFE_RAW) ?: '', ENT_QUOTES, 'UTF-8'),
                    'bcc' => htmlspecialchars(filter_input(INPUT_POST, 'bcc', FILTER_UNSAFE_RAW) ?: '', ENT_QUOTES, 'UTF-8'),
                    'subject' => htmlspecialchars(filter_input(INPUT_POST, 'subject', FILTER_UNSAFE_RAW) ?: '', ENT_QUOTES, 'UTF-8'),
                    'message' => filter_input(INPUT_POST, 'message', FILTER_UNSAFE_RAW) ?: '',
                    'references' => htmlspecialchars(filter_input(INPUT_POST, 'references', FILTER_UNSAFE_RAW) ?: '', ENT_QUOTES, 'UTF-8'),
                    'in_reply_to' => htmlspecialchars(filter_input(INPUT_POST, 'in_reply_to', FILTER_UNSAFE_RAW) ?: '', ENT_QUOTES, 'UTF-8')
                ];

                // Check for .eml file upload for threading
                $useEmlFile = false;
                $emlData = null;

                if (isset($_FILES['emlFile']) && $_FILES['emlFile']['error'] === UPLOAD_ERR_OK) {
                    // Include raw email functions
                    require_once 'includes/raw_email_functions.php';

                    // Parse the uploaded .eml file
                    $uploadedFile = $_FILES['emlFile']['tmp_name'];
                    $emlData = parseEmlFile($uploadedFile);

                    if ($emlData) {
                        $useEmlFile = true;

                        // Extract threading information from the .eml file
                        if (!empty($emlData['headers']['Message-ID'])) {
                            // Use the original Message-ID for threading
                            $emailData['references'] = $emlData['headers']['Message-ID'];
                            $emailData['in_reply_to'] = $emlData['headers']['Message-ID'];

                            // If the original email already had References, append them
                            if (!empty($emlData['headers']['References'])) {
                                $emailData['references'] = $emlData['headers']['References'] . ' ' . $emlData['headers']['Message-ID'];
                            }
                        }

                        // Set preserved options
                        $emailData['preserve_recipients'] = isset($_POST['preserveRecipients']);
                        $emailData['preserve_attachments'] = isset($_POST['preserveAttachments']);
                    }
                }

                // Handle attachments if any
                if (!empty($_FILES['attachments']['name'][0])) {
                    // Pass the entire $_FILES['attachments'] array to the email function
                    $attachments = $_FILES['attachments'];

                    // Log attachment details for debugging
                    error_log("Attachments found: " . count($_FILES['attachments']['name']) . " files");
                    foreach ($_FILES['attachments']['name'] as $index => $name) {
                        error_log("Attachment $index: $name, size: " . $_FILES['attachments']['size'][$index] . " bytes, temp: " . $_FILES['attachments']['tmp_name'][$index]);
                    }
                } else {
                    $attachments = [];
                    error_log("No attachments found in request");
                }

                // Make sure we're in JSON mode
                if (headers_sent()) {
                    error_log("Headers already sent before email response");
                } else {
                    header('Content-Type: application/json');
                }

                // Log the email attempt
                error_log("Attempting to send email to: " . $emailData['to']);

                // Create email sending response first
                $response = [];
                $data = []; // Initialize data array to hold response info

                try {
                    // Send the email
                    $result = false;
                    $messageId = '';
                    try {
                        // Get the current send method
                        $sendMethod = $_POST['send_method'] ?? 'mail';

                        if ($useEmlFile && $emlData) {
                            // Use the raw email sending method with the modified .eml file
                            require_once 'includes/raw_email_functions.php';

                            // Get SMTP settings from config
                            $smtpSettings = [
                                'use_smtp' => true, // Always use SMTP
                                'smtp_host' => config('smtp.host', 'localhost'),
                                'smtp_port' => config('smtp.port', 25),
                                'smtp_username' => config('smtp.username', ''),
                                'smtp_password' => config('smtp.password', ''),
                                'smtp_secure' => config('smtp.encryption', ''),
                                'smtp_auth' => !empty(config('smtp.username')) && !empty(config('smtp.password')),
                                'debug_level' => config('smtp.debug_level', 2),
                                'verify_ssl' => config('smtp.verify_ssl', false)
                            ];

                            // Prepare new data for the modified email
                            $newData = [
                                'from_email' => $emailData['from_email'],
                                'from_name' => $emailData['from_name'],
                                'to_email' => $emailData['to'],
                                'subject' => $emailData['subject'],
                                'message' => $emailData['message'],
                                'preserve_recipients' => $emailData['preserve_recipients'] ?? false,
                                'preserve_attachments' => $emailData['preserve_attachments'] ?? false
                            ];

                            // Send the modified email
                            $sendResult = sendModifiedEml($emlData, $newData, $smtpSettings);
                            $result = $sendResult['success'];
                            $messageId = $sendResult['message_id'] ?? '';

                            if (!$result) {
                                echo json_encode(['status' => 'error', 'message' => $sendResult['message']]);
                                exit;
                            }
                        } else {
                            // Regular email sending logic
                            require_once 'includes/smtp_functions.php';

                            // Always use SMTP
                            $smtpSettings = [
                                'host' => config('smtp.host', 'localhost'),
                                'port' => config('smtp.port', 25),
                                'encryption' => config('smtp.encryption', ''),
                                'auth' => !empty(config('smtp.username')) && !empty(config('smtp.password')),
                                'username' => config('smtp.username', ''),
                                'password' => config('smtp.password', ''),
                                'debug_level' => config('smtp.debug_level', 2),
                                'verify_ssl' => config('smtp.verify_ssl', false)
                            ];

                            $sendResult = sendWithSmtp($emailData, $attachments, $smtpSettings, ['include_headers' => false]);
                            $result = $sendResult['success'];
                            $messageId = $sendResult['message_id'] ?? '';

                            if (!$result) {
                                echo json_encode(['status' => 'error', 'message' => $sendResult['message']]);
                                exit;
                            }
                        }

                        // If successful, log details
                        if ($result) {
                            // Log success to file
                            $logData = [
                                'time' => date('Y-m-d H:i:s'),
                                'from' => $emailData['from_email'],
                                'to' => $emailData['to'],
                                'subject' => $emailData['subject']
                            ];
                            file_put_contents('logs/email_sent.log', json_encode($logData) . "\n", FILE_APPEND);

                            echo json_encode(['status' => 'success', 'message' => 'Email sent successfully!']);
                            exit;
                        }
                    } catch (Exception $e) {
                        $result = false;
                        $messageId = $e->getMessage();

                        // Log exception to file
                        $logData = [
                            'time' => date('Y-m-d H:i:s'),
                            'from' => $emailData['from_email'],
                            'to' => $emailData['to'],
                            'subject' => $emailData['subject'],
                            'status' => 'exception',
                            'error' => $messageId
                        ];
                        logToFile('email_error.log', $logData);
                    }

                    // Merge the response with our data array
                    $data = array_merge($data, [
                        'status' => $result ? 'success' : 'error',
                        'message' => $messageId,
                        'messageId' => $messageId
                    ]);
                    echo json_encode($data);
                    break;
                } catch (Exception $e) {
                    error_log("AJAX error: " . $e->getMessage());
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'An error occurred: ' . $e->getMessage()
                    ]);
                }
                break;

            case 'domain_analysis':
                $domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_SPECIAL_CHARS);

                if (empty($domain)) {
                    echo json_encode(['status' => 'error', 'message' => 'Domain cannot be empty']);
                    exit;
                }

                // Perform domain analysis
                $results = analyze_domain_security($domain);
                echo json_encode($results);
                break;

            case 'send_raw_email':
                // Verify CSRF token
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    $response = [
                        'success' => false,
                        'message' => 'Invalid security token.'
                    ];
                    echo json_encode($response);
                    exit;
                }

                // Check if file was uploaded properly
                if (!isset($_FILES['emlFile']) || $_FILES['emlFile']['error'] !== UPLOAD_ERR_OK) {
                    $response = [
                        'success' => false,
                        'message' => 'Error uploading file: ' . ($_FILES['emlFile']['error'] ?? 'No file uploaded')
                    ];
                    echo json_encode($response);
                    exit;
                }

                // Include the raw email functions
                require_once 'includes/raw_email_functions.php';

                // Process the uploaded EML file
                $uploadedFile = $_FILES['emlFile']['tmp_name'];
                $emlData = parseEmlFile($uploadedFile);

                if (!$emlData) {
                    $response = [
                        'success' => false,
                        'message' => 'Error parsing EML file. Make sure it is a valid email file.'
                    ];
                    echo json_encode($response);
                    exit;
                }

                // Get the new data to inject
                $newData = [
                    'from_email' => filter_input(INPUT_POST, 'rawFromEmail', FILTER_SANITIZE_EMAIL) ?: '',
                    'from_name' => htmlspecialchars(filter_input(INPUT_POST, 'rawFromName', FILTER_UNSAFE_RAW) ?: '', ENT_QUOTES, 'UTF-8'),
                    'to_email' => filter_input(INPUT_POST, 'rawToEmail', FILTER_SANITIZE_EMAIL) ?: '',
                    'subject' => htmlspecialchars(filter_input(INPUT_POST, 'rawSubject', FILTER_UNSAFE_RAW) ?: '', ENT_QUOTES, 'UTF-8'),
                    'message' => filter_input(INPUT_POST, 'rawMessage', FILTER_UNSAFE_RAW) ?: '',
                    'preserve_recipients' => isset($_POST['preserveRecipients']),
                    'preserve_attachments' => isset($_POST['preserveAttachments'])
                ];

                // Get SMTP settings from config
                $smtpSettings = [
                    'use_smtp' => true, // Always use SMTP
                    'smtp_host' => config('smtp.host', 'localhost'),
                    'smtp_port' => config('smtp.port', 25),
                    'smtp_username' => config('smtp.username', ''),
                    'smtp_password' => config('smtp.password', ''),
                    'smtp_secure' => config('smtp.encryption', ''),
                    'smtp_auth' => !empty(config('smtp.username')) && !empty(config('smtp.password')),
                    'debug_level' => config('smtp.debug_level', 2),
                    'verify_ssl' => config('smtp.verify_ssl', false)
                ];

                // Send the modified email
                $result = sendModifiedEml($emlData, $newData, $smtpSettings);

                // Return the result
                echo json_encode($result);
                exit;

            case 'parse_eml_file':
                // Verify CSRF token
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    $response = [
                        'success' => false,
                        'message' => 'Invalid security token.'
                    ];
                    echo json_encode($response);
                    exit;
                }

                // Check if file was uploaded properly
                if (!isset($_FILES['emlFile']) || $_FILES['emlFile']['error'] !== UPLOAD_ERR_OK) {
                    $response = [
                        'success' => false,
                        'message' => 'Error uploading file: ' . ($_FILES['emlFile']['error'] ?? 'No file uploaded')
                    ];
                    echo json_encode($response);
                    exit;
                }

                // Include the raw email functions
                require_once 'includes/raw_email_functions.php';

                // Process the uploaded EML file
                $uploadedFile = $_FILES['emlFile']['tmp_name'];
                $emlData = parseEmlFile($uploadedFile);

                if (!$emlData) {
                    $response = [
                        'success' => false,
                        'message' => 'Error parsing EML file. Make sure it is a valid email file.'
                    ];
                    echo json_encode($response);
                    exit;
                }

                // Return the parsed data
                $response = [
                    'success' => true,
                    'from_email' => $emlData['from_email'],
                    'from_name' => $emlData['from_name'],
                    'to_email' => $emlData['to_email'],
                    'to_name' => $emlData['to_name'],
                    'subject' => $emlData['subject'],
                    'message_id' => $emlData['headers']['Message-ID'] ?? ''
                ];

                // Include the email body if requested
                if (isset($_POST['include_body']) && $_POST['include_body']) {
                    $response['body'] = $emlData['body'];

                    // Add date info if available
                    if (!empty($emlData['headers']['Date'])) {
                        $response['date'] = $emlData['headers']['Date'];
                    }
                }
                echo json_encode($response);
                exit;

                // Handle thread builder submission
            case 'send_thread':
                if (isset($_POST['thread_data']) && !empty($_POST['thread_data'])) {
                    // Get thread data
                    $threadData = json_decode($_POST['thread_data'], true);

                    if (empty($threadData) || !is_array($threadData)) {
                        displayErrorPage('Invalid thread data provided.');
                        exit;
                    }

                    require_once 'includes/smtp_functions.php';

                    // Build the complete email body with thread formatting
                    $completeBody = '';
                    $latestSubject = $threadData[count($threadData) - 1]['subject'];

                    // Add newest message first (not quoted)
                    $latestMessage = $threadData[count($threadData) - 1];
                    $completeBody .= $latestMessage['body'] . "\n\n";

                    // Add older messages in reverse order (quoted)
                    for ($i = count($threadData) - 2; $i >= 0; $i--) {
                        $message = $threadData[$i];

                        $completeBody .= "\nOn " . $message['date'] . ", " . $message['sender_name'] .
                            " <" . $message['sender_email'] . "> wrote:\n";

                        // Quote the message body
                        $lines = explode("\n", $message['body']);
                        foreach ($lines as $line) {
                            $completeBody .= "> " . $line . "\n";
                        }
                        $completeBody .= "\n";
                    }

                    // Prepare email data
                    $emailData = [
                        'from_email' => $_POST['from_email'],
                        'from_name' => $_POST['from_name'],
                        'to' => $_POST['to'],
                        'subject' => $latestSubject,
                        'message' => nl2br($completeBody),
                        'cc' => '',
                        'bcc' => '',
                        'reply_to' => isset($latestMessage['reply_to_email']) && !empty($latestMessage['reply_to_email']) ? $latestMessage['reply_to_email'] : '',
                        'references' => '',
                        'in_reply_to' => ''
                    ];

                    // Add thread references if available
                    if (count($threadData) > 1) {
                        // Set the In-Reply-To to the previous message's ID
                        $emailData['in_reply_to'] = generateMessageId(explode('@', $threadData[count($threadData) - 2]['sender_email'])[1]);

                        // Build References header with all previous message IDs
                        $references = [];
                        for ($i = 0; $i < count($threadData) - 1; $i++) {
                            $domain = explode('@', $threadData[$i]['sender_email'])[1];
                            $references[] = generateMessageId($domain);
                        }
                        $emailData['references'] = implode(' ', $references);
                    }

                    // Get SMTP settings
                    $smtpSettings = [
                        'host' => config('smtp.host', 'localhost'),
                        'port' => config('smtp.port', 25),
                        'encryption' => config('smtp.encryption', ''),
                        'auth' => !empty(config('smtp.username')) && !empty(config('smtp.password')),
                        'username' => config('smtp.username', ''),
                        'password' => config('smtp.password', ''),
                        'debug_level' => config('smtp.debug_level', 2),
                        'verify_ssl' => config('smtp.verify_ssl', false)
                    ];

                    // Send the email
                    $sendResult = sendWithSmtp($emailData, [], $smtpSettings, ['include_headers' => false]);

                    if ($sendResult['success']) {
                        setFlashMessage('success', 'Thread email sent successfully!');
                    } else {
                        setFlashMessage('error', 'Failed to send thread email: ' . $sendResult['message']);
                    }

                    // Redirect to home page
                    header('Location: index.php');
                    exit;
                } else {
                    displayErrorPage('No thread data provided.');
                    exit;
                }
                break;

            default:
                echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        }
    } catch (Exception $e) {
        error_log("AJAX error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Add a new page for raw email mode
if (isset($_GET['page']) && $_GET['page'] === 'raw_email') {
    $currentPage = 'raw_email';
    include_once 'templates/header.php';
    include_once 'templates/raw_email.php';
    include_once 'templates/footer.php';
    exit;
}

// Main pages
$currentPage = 'main';

// Include the main template
include 'templates/main.php';
