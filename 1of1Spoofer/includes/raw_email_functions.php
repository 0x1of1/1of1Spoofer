<?php

/**
 * 1of1Spoofer - Raw Email Functions
 * 
 * Functions for parsing and sending raw email files (.eml)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Parse an EML file and return an array of its parts
 * 
 * @param string $filePath Path to the EML file
 * @return array|false Array of email parts or false on failure
 */
function parseEmlFile($filePath)
{
    if (!file_exists($filePath)) {
        error_log("EML file not found: $filePath");
        return false;
    }

    // Set a reasonable size limit (10MB)
    $fileSize = filesize($filePath);
    if ($fileSize > 10 * 1024 * 1024) {
        error_log("EML file too large: $fileSize bytes");
        return false;
    }

    $rawEmail = file_get_contents($filePath);
    if (!$rawEmail) {
        error_log("Failed to read EML file: $filePath");
        return false;
    }

    // Parse email headers and body
    $parts = preg_split("/\r?\n\r?\n/", $rawEmail, 2);

    if (count($parts) < 2) {
        error_log("EML file has invalid format (couldn't separate headers and body)");
        return false;
    }

    $headers = $parts[0];
    $body = $parts[1];

    // Parse headers into an associative array
    $headerLines = explode("\n", $headers);
    $parsedHeaders = [];
    $currentHeader = '';

    foreach ($headerLines as $line) {
        if (preg_match('/^([A-Za-z0-9\-]+):\s*(.*)$/', $line, $matches)) {
            $currentHeader = $matches[1];
            $parsedHeaders[$currentHeader] = trim($matches[2]);
        } elseif (preg_match('/^\s+(.+)$/', $line, $matches) && $currentHeader) {
            // Handle header continuation
            $parsedHeaders[$currentHeader] .= ' ' . trim($matches[1]);
        }
    }

    // Extract basic email information
    $from = extractEmailAddress($parsedHeaders['From'] ?? '');
    $to = extractEmailAddress($parsedHeaders['To'] ?? '');
    $subject = $parsedHeaders['Subject'] ?? '';

    // Clean up body if needed - limit its size for JavaScript processing
    if (strlen($body) > 100000) {
        $body = substr($body, 0, 100000) . "\n\n[Content truncated due to size]";
    }

    // Remove Reply-To header to prevent it from being preserved
    if (isset($parsedHeaders['Reply-To'])) {
        error_log("Removing Reply-To header from original email: " . $parsedHeaders['Reply-To']);
        unset($parsedHeaders['Reply-To']);
    }

    // Return the parsed email
    return [
        'headers' => $parsedHeaders,
        'raw_headers' => $headers,
        'body' => $body,
        'from_email' => $from['email'],
        'from_name' => $from['name'],
        'to_email' => $to['email'],
        'to_name' => $to['name'],
        'subject' => $subject,
        'raw_email' => $rawEmail
    ];
}

/**
 * Extract email address and name from a From/To header
 * 
 * @param string $header The header string (e.g., "John Doe <john@example.com>")
 * @return array Associative array with 'name' and 'email' keys
 */
function extractEmailAddress($header)
{
    $result = ['name' => '', 'email' => ''];

    // Try to match the pattern "Name <email@domain.com>"
    if (preg_match('/^(.*?)\s*<([^>]+)>/', $header, $matches)) {
        $result['name'] = trim($matches[1]);
        $result['email'] = trim($matches[2]);
    }
    // Just an email address
    elseif (filter_var($header, FILTER_VALIDATE_EMAIL)) {
        $result['email'] = trim($header);
    }

    return $result;
}

/**
 * Modify an EML file with new fields and send it
 * 
 * @param array $emlData The parsed EML data
 * @param array $newData New data to inject into the email
 * @param array $smtpSettings SMTP server settings
 * @return array Result of the send operation
 */
function sendModifiedEml($emlData, $newData, $smtpSettings)
{
    // Start with the original raw email
    $rawEmail = $emlData['raw_email'];

    // Create a temporary file path
    $tempFile = tempnam(sys_get_temp_dir(), 'eml_');
    file_put_contents($tempFile, $rawEmail);

    // Ensure logs directory exists
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Set up logging
    $logFile = $logDir . '/raw_email.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Processing raw email\n", FILE_APPEND);

    try {
        // Create the PHPMailer instance
        $mail = new PHPMailer(true);

        // Always use SMTP
        $mail->isSMTP();
        $mail->Host = $smtpSettings['smtp_host'];
        $mail->Port = $smtpSettings['smtp_port'];

        if ($smtpSettings['smtp_auth']) {
            $mail->SMTPAuth = true;
            $mail->Username = $smtpSettings['smtp_username'];
            $mail->Password = $smtpSettings['smtp_password'];
        }

        $mail->SMTPSecure = $smtpSettings['smtp_secure'];

        // Set debug level if provided
        if (isset($smtpSettings['debug_level'])) {
            $mail->SMTPDebug = $smtpSettings['debug_level'];
            // Capture debug output
            $mail->Debugoutput = function ($str, $level) use ($logFile) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [SMTP DEBUG]: {$str}\n", FILE_APPEND);
            };
        }

        // SSL verification settings
        if (isset($smtpSettings['verify_ssl']) && $smtpSettings['verify_ssl'] === false) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        // Configure character set for proper handling of special chars
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Special handling for raw email - use PHPMailer's ability to process existing messages
        $mail->createHeader();

        // Set up the message - always use the new From and To data
        // From
        if (!empty($newData['from_email'])) {
            $fromName = !empty($newData['from_name']) ? $newData['from_name'] : '';
            $mail->setFrom($newData['from_email'], $fromName);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting From: {$fromName} <{$newData['from_email']}>\n", FILE_APPEND);
        } else {
            $mail->setFrom($emlData['from_email'], $emlData['from_name']);
        }

        // To
        if (!empty($newData['to_email'])) {
            $mail->clearAddresses();
            $mail->addAddress($newData['to_email']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting To: {$newData['to_email']}\n", FILE_APPEND);
        } else {
            $mail->addAddress($emlData['to_email'], $emlData['to_name']);
        }

        // Set Reply-To if provided
        if (!empty($newData['reply_to'])) {
            $mail->addReplyTo($newData['reply_to']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting Reply-To: {$newData['reply_to']}\n", FILE_APPEND);
        }

        // Subject
        if (!empty($newData['subject'])) {
            $mail->Subject = $newData['subject'];
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting Subject: {$newData['subject']}\n", FILE_APPEND);
        } else {
            $mail->Subject = $emlData['subject'];
        }

        // Preserve CC/BCC if requested
        if (!empty($newData['preserve_recipients']) && !empty($emlData['headers']['Cc'])) {
            $ccRecipients = explode(',', $emlData['headers']['Cc']);
            foreach ($ccRecipients as $cc) {
                $ccData = extractEmailAddress(trim($cc));
                if (!empty($ccData['email'])) {
                    $mail->addCC($ccData['email'], $ccData['name']);
                }
            }
        }

        if (!empty($newData['preserve_recipients']) && !empty($emlData['headers']['Bcc'])) {
            $bccRecipients = explode(',', $emlData['headers']['Bcc']);
            foreach ($bccRecipients as $bcc) {
                $bccData = extractEmailAddress(trim($bcc));
                if (!empty($bccData['email'])) {
                    $mail->addBCC($bccData['email'], $bccData['name']);
                }
            }
        }

        // Handle threading headers
        if (!empty($emlData['headers']['References'])) {
            $mail->addCustomHeader('References', $emlData['headers']['References']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Preserving References: {$emlData['headers']['References']}\n", FILE_APPEND);
        }

        if (!empty($emlData['headers']['In-Reply-To'])) {
            $mail->addCustomHeader('In-Reply-To', $emlData['headers']['In-Reply-To']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Preserving In-Reply-To: {$emlData['headers']['In-Reply-To']}\n", FILE_APPEND);
        }

        if (!empty($emlData['headers']['Message-ID'])) {
            // Add as a reference if we're modifying the email
            $messageId = $emlData['headers']['Message-ID'];

            // Generate a new Message-ID that continues the conversation
            $domain = explode('@', $newData['from_email'] ?: $emlData['from_email'])[1] ?? 'example.com';
            $newMessageId = '<' . uniqid(rand(), true) . '@' . $domain . '>';

            $mail->MessageID = $newMessageId;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting new Message-ID: {$newMessageId}\n", FILE_APPEND);

            // If there are existing references, add the original message ID to them
            if (!empty($emlData['headers']['References'])) {
                $references = $emlData['headers']['References'] . ' ' . $messageId;
                $mail->addCustomHeader('References', $references);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Updated References: {$references}\n", FILE_APPEND);
            } else {
                // Start a new references chain with just the original message ID
                $mail->addCustomHeader('References', $messageId);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added References: {$messageId}\n", FILE_APPEND);
            }

            // Set In-Reply-To to the original message ID
            $mail->addCustomHeader('In-Reply-To', $messageId);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting In-Reply-To: {$messageId}\n", FILE_APPEND);
        }

        // Set message body
        if (!empty($newData['message'])) {
            // Use the new message
            $mail->isHTML(true);
            $mail->Body = $newData['message'];
            $mail->AltBody = strip_tags($newData['message']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Using new message body\n", FILE_APPEND);
        } else {
            // Try to determine if the original is HTML
            $isHtml = preg_match('/<html|<body|<div|<p|<table/i', $emlData['body']);

            if ($isHtml) {
                $mail->isHTML(true);
                $mail->Body = $emlData['body'];
                $mail->AltBody = strip_tags($emlData['body']);
            } else {
                $mail->Body = $emlData['body'];
            }
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Using original message body\n", FILE_APPEND);
        }

        // Send the email
        $result = $mail->send();

        if ($result) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [SUCCESS]: Email sent successfully\n", FILE_APPEND);
            return ['success' => true, 'message' => 'Email sent successfully!', 'message_id' => $mail->MessageID];
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Mailer Error: " . $mail->ErrorInfo . "\n", FILE_APPEND);
            return ['success' => false, 'message' => 'Email failed to send: ' . $mail->ErrorInfo];
        }
    } catch (Exception $e) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    } finally {
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
