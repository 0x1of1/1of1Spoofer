<?php

/**
 * 1of1Spoofer - AJAX Request Handlers
 * 
 * This file contains handlers for AJAX requests in the application
 */

// Include necessary files
require_once 'utils.php';
require_once 'smtp_functions.php';

// Start session if not already started
if (!isset($_SESSION) && !headers_sent()) {
    session_start();
}

// Check for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Handle SMTP settings submission
    if (isset($_POST['action']) && $_POST['action'] === 'save_smtp_settings') {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid security token. Please refresh the page and try again.'
            ]);
            exit;
        }

        // Get and sanitize SMTP settings
        $smtpSettings = [
            'host' => filter_input(INPUT_POST, 'smtp_host', FILTER_SANITIZE_SPECIAL_CHARS),
            'port' => filter_input(INPUT_POST, 'smtp_port', FILTER_VALIDATE_INT),
            'security' => filter_input(INPUT_POST, 'smtp_security', FILTER_SANITIZE_SPECIAL_CHARS),
            'username' => filter_input(INPUT_POST, 'smtp_username', FILTER_SANITIZE_SPECIAL_CHARS),
            'password' => filter_input(INPUT_POST, 'smtp_password', FILTER_SANITIZE_SPECIAL_CHARS),
            'debug' => filter_input(INPUT_POST, 'smtp_debug', FILTER_VALIDATE_INT),
            'verify_ssl' => isset($_POST['smtp_verify_ssl']) ? true : false
        ];

        // Save SMTP settings
        $result = saveSmtpSettings($smtpSettings);

        // Return JSON response
        echo json_encode($result);
        exit;
    }

    // Handle sending email
    if (isset($_POST['action']) && $_POST['action'] === 'send_email') {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid security token. Please refresh the page and try again.'
            ]);
            exit;
        }

        // Get and validate email data
        $emailData = [
            'from_name' => filter_input(INPUT_POST, 'from_name', FILTER_SANITIZE_SPECIAL_CHARS),
            'from_email' => filter_input(INPUT_POST, 'from_email', FILTER_VALIDATE_EMAIL),
            'to' => filter_input(INPUT_POST, 'to', FILTER_SANITIZE_SPECIAL_CHARS),
            'cc' => filter_input(INPUT_POST, 'cc', FILTER_SANITIZE_SPECIAL_CHARS),
            'bcc' => filter_input(INPUT_POST, 'bcc', FILTER_SANITIZE_SPECIAL_CHARS),
            'reply_to' => filter_input(INPUT_POST, 'reply_to', FILTER_VALIDATE_EMAIL),
            'subject' => filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS),
            'message' => $_POST['message'] // We'll keep HTML for rich text
        ];

        // Handle attachments if any
        $attachments = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $uploadDir = config('uploads.dir', __DIR__ . '/../uploads');

            // Ensure upload directory exists
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Process each attachment
            foreach ($_FILES['attachments']['name'] as $index => $name) {
                if ($_FILES['attachments']['error'][$index] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['attachments']['tmp_name'][$index];
                    $name = basename($name);
                    $uploadPath = $uploadDir . '/' . $name;

                    // Move uploaded file
                    if (move_uploaded_file($tmp_name, $uploadPath)) {
                        $attachments[] = [
                            'path' => $uploadPath,
                            'name' => $name
                        ];
                    }
                }
            }
        }

        // Add attachments to email data
        $emailData['attachments'] = $attachments;

        // Get SMTP settings
        $smtpSettings = getSmtpSettings();

        // Send email using SMTP
        $result = sendWithSmtp($emailData, $smtpSettings);

        // Return JSON response
        echo json_encode($result);
        exit;
    }

    // Default response for unknown action
    echo json_encode([
        'status' => 'error',
        'message' => 'Unknown action'
    ]);
    exit;
}

// Not an AJAX request, redirect to home page
header('Location: /');
exit;
