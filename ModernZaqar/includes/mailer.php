<?php

/**
 * 1of1Spoofer - Mail Sender
 * 
 * This file contains the classes and functions for sending emails 
 * using different methods (PHP mail(), SMTP, SendGrid, or Mailgun).
 */

// Make sure we have the config and utils
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/utils.php';

// PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailSender class for sending spoofed emails
 */
class EmailSender
{
    private $from_email;
    private $from_name;
    private $to_email;
    private $subject;
    private $message;
    private $attachments = [];
    private $reply_to = null;
    private $send_method;
    private $error = null;
    private $success = false;

    /**
     * Constructor
     *
     * @param array $options Email sending options
     */
    public function __construct($options = [])
    {
        // Set default send method from config
        $this->send_method = config('send_method', 'mail');

        // Override with provided options
        if (isset($options['send_method'])) {
            $this->send_method = $options['send_method'];
        }
    }

    /**
     * Set email details
     *
     * @param string $from_email Sender email
     * @param string $from_name Sender name
     * @param string $to_email Recipient email
     * @param string $subject Email subject
     * @param string $message Email body
     * @param string $reply_to Reply-to email (optional)
     * @return EmailSender This instance for method chaining
     */
    public function setEmail($from_email, $from_name, $to_email, $subject, $message, $reply_to = null)
    {
        $this->from_email = sanitize_input($from_email);
        $this->from_name = sanitize_input($from_name);
        $this->to_email = sanitize_input($to_email);
        $this->subject = sanitize_input($subject);
        $this->message = $message; // Allow HTML in message

        if ($reply_to) {
            $this->reply_to = sanitize_input($reply_to);
        }

        return $this;
    }

    /**
     * Add an attachment
     *
     * @param string $path File path
     * @param string $name File name (optional)
     * @return EmailSender This instance for method chaining
     */
    public function addAttachment($path, $name = '')
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name
        ];

        return $this;
    }

    /**
     * Set the send method
     *
     * @param string $method Send method ('mail', 'smtp', 'sendgrid', or 'mailgun')
     * @return EmailSender This instance for method chaining
     */
    public function setSendMethod($method)
    {
        $this->send_method = $method;
        return $this;
    }

    /**
     * Get the last error
     *
     * @return string|null The error message or null if no error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Check if the email was sent successfully
     *
     * @return bool Whether the email was sent successfully
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * Validate the email
     *
     * @return bool Whether the email is valid
     */
    private function validate()
    {
        // Check if required fields are set
        if (empty($this->from_email) || empty($this->to_email) || empty($this->subject)) {
            $this->error = "Missing required fields (from email, to email, or subject).";
            return false;
        }

        // Validate email addresses
        if (!is_valid_email($this->from_email)) {
            $this->error = "Invalid sender email address.";
            return false;
        }

        if (!is_valid_email($this->to_email)) {
            $this->error = "Invalid recipient email address.";
            return false;
        }

        if ($this->reply_to && !is_valid_email($this->reply_to)) {
            $this->error = "Invalid reply-to email address.";
            return false;
        }

        // Check rate limit
        if (!check_rate_limit()) {
            $reset_times = get_rate_limit_reset_time();
            $this->error = "Rate limit exceeded. Please try again in " . format_time_duration($reset_times['hourly']) . ".";
            return false;
        }

        return true;
    }

    /**
     * Send the email
     *
     * @return bool Whether the email was sent successfully
     */
    public function send()
    {
        // Validate the email
        if (!$this->validate()) {
            return false;
        }

        // Choose the appropriate sending method
        $result = false;

        switch ($this->send_method) {
            case 'mail':
                $result = $this->sendWithPhpMail();
                break;
            case 'smtp':
                $result = $this->sendWithSmtp();
                break;
            case 'sendgrid':
                $result = $this->sendWithSendGrid();
                break;
            case 'mailgun':
                $result = $this->sendWithMailgun();
                break;
            default:
                $this->error = "Invalid send method: {$this->send_method}";
                return false;
        }

        // Increment rate limit counter on success
        if ($result) {
            increment_rate_limit();

            // Log the email
            log_message("Email sent", 'info', [
                'from' => $this->from_email,
                'to' => $this->to_email,
                'subject' => $this->subject,
                'method' => $this->send_method,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ]);
        } else {
            // Log the error
            log_message("Email sending failed: {$this->error}", 'error', [
                'from' => $this->from_email,
                'to' => $this->to_email,
                'subject' => $this->subject,
                'method' => $this->send_method
            ]);
        }

        $this->success = $result;
        return $result;
    }

    /**
     * Send email using PHP mail() function
     *
     * @return bool Whether the email was sent successfully
     */
    private function sendWithPhpMail()
    {
        try {
            // Create a new PHPMailer instance for mail() function
            $mail = new PHPMailer(true);
            $mail->isMail();

            // Set up the email
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($this->to_email);
            $mail->Subject = $this->subject;

            // Set reply-to if specified
            if ($this->reply_to) {
                $mail->addReplyTo($this->reply_to);
            }

            // Set return path if configured
            if (config('mail.use_return_path', true)) {
                $return_path = config('mail.return_path', '');
                if (!empty($return_path)) {
                    $mail->Sender = $return_path;
                } else {
                    $mail->Sender = $this->from_email;
                }
            }

            // Set HTML content
            $mail->isHTML(true);
            $mail->Body = $this->message;
            $mail->AltBody = strip_tags($this->message);

            // Add attachments
            foreach ($this->attachments as $attachment) {
                $mail->addAttachment($attachment['path'], $attachment['name']);
            }

            // Send the email
            return $mail->send();
        } catch (Exception $e) {
            $this->error = "PHP Mail Error: " . $mail->ErrorInfo;
            return false;
        }
    }

    /**
     * Send email using SMTP
     *
     * @return bool Whether the email was sent successfully
     */
    private function sendWithSmtp()
    {
        try {
            // Log SMTP connection attempt with full details
            error_log("SMTP Connection Details: Host=" . config('smtp.host') . ", Port=" . config('smtp.port', 587) .
                ", Encryption=" . config('smtp.encryption', 'tls') .
                ", Username=" . (config('smtp.username') ? config('smtp.username') : 'none') .
                ", SSL Verification=" . (config('smtp.verify_ssl', false) ? 'enabled' : 'disabled'));

            // Create a new PHPMailer instance for SMTP
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = config('smtp.debug', 4); // Set to highest debug level for troubleshooting
            $mail->Debugoutput = function ($str, $level) {
                error_log("SMTP Debug ($level): $str");
            };

            $mail->Timeout = 30; // Increase timeout to 30 seconds
            $mail->Host = config('smtp.host');
            $mail->Port = config('smtp.port', 587);

            // Set encryption (TLS/SSL)
            $encryption = config('smtp.encryption', 'tls');
            if (!empty($encryption)) {
                $mail->SMTPSecure = $encryption;
            }

            // Set authentication
            if (!empty(config('smtp.username')) && !empty(config('smtp.password'))) {
                $mail->SMTPAuth = true;
                $mail->Username = config('smtp.username');
                $mail->Password = config('smtp.password');

                // Get the SMTP username to use as envelope sender
                $smtpUsername = config('smtp.username');

                // Allow using a different From address from the authenticated username
                // The envelope sender will be the authenticated email, but the From header will be the spoofed one
                $mail->Sender = $smtpUsername; // Envelope sender (MAIL FROM)

                // Add an X-Spoofed-From header to indicate this is a spoofed email
                $mail->addCustomHeader('X-Spoofed-From', "{$this->from_name} <{$this->from_email}>");

                // Log authentication information (without the password)
                error_log("SMTP authentication with username: " . $mail->Username);
            } else {
                $mail->SMTPAuth = false;
                error_log("SMTP without authentication - this may fail if the server requires authentication");
            }

            // Always disable SSL certificate verification to fix connection issues
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'verify_depth' => 0
                ]
            ];
            error_log("SMTP SSL verification forcibly disabled with extended options");

            // Set up the email
            // Keep the original From information for the visible headers
            try {
                $mail->setFrom($this->from_email, $this->from_name);
            } catch (Exception $e) {
                // If setting the from address fails, try using the SMTP username as a fallback
                error_log("Warning: Failed to set From address: " . $e->getMessage());
                error_log("Trying fallback: Using SMTP username as From address");
                if (!empty($smtpUsername)) {
                    $mail->setFrom($smtpUsername, $this->from_name);
                }
            }

            $mail->addAddress($this->to_email);
            $mail->Subject = $this->subject;

            // Set reply-to if specified
            if ($this->reply_to) {
                $mail->addReplyTo($this->reply_to);
            }

            // Set HTML email
            $mail->isHTML(true);
            $mail->Body = $this->message;
            $mail->AltBody = strip_tags($this->message);

            // Add attachments
            foreach ($this->attachments as $attachment) {
                if (file_exists($attachment['path'])) {
                    $mail->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ? $attachment['name'] : basename($attachment['path'])
                    );
                }
            }

            // Log that we're about to send the email
            error_log("Attempting to send email via SMTP: From={$this->from_email}, To={$this->to_email}, Subject={$this->subject}");

            // Send the email
            $result = $mail->send();
            if (!$result) {
                $this->error = "SMTP Error: " . $mail->ErrorInfo;
                error_log("SMTP Send Error: " . $mail->ErrorInfo);
            } else {
                error_log("Email sent successfully via SMTP");
            }
            return $result;
        } catch (Exception $e) {
            $this->error = "SMTP Exception: " . $e->getMessage();
            error_log("SMTP Exception: " . $e->getMessage());

            // Add stack trace for better debugging
            error_log("SMTP Exception Stack Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Send email using SendGrid API
     *
     * @return bool Whether the email was sent successfully
     */
    private function sendWithSendGrid()
    {
        try {
            $api_key = config('sendgrid.api_key');
            if (empty($api_key)) {
                $this->error = "SendGrid API key is not configured.";
                return false;
            }

            // Prepare the email data
            $data = [
                'personalizations' => [
                    [
                        'to' => [
                            ['email' => $this->to_email]
                        ],
                        'subject' => $this->subject
                    ]
                ],
                'from' => [
                    'email' => $this->from_email,
                    'name' => $this->from_name
                ],
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $this->message
                    ]
                ]
            ];

            // Add reply-to if specified
            if ($this->reply_to) {
                $data['reply_to'] = [
                    'email' => $this->reply_to
                ];
            }

            // Add attachments
            if (!empty($this->attachments)) {
                $data['attachments'] = [];
                foreach ($this->attachments as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $content = base64_encode(file_get_contents($attachment['path']));
                        $data['attachments'][] = [
                            'content' => $content,
                            'filename' => $attachment['name'] ? $attachment['name'] : basename($attachment['path']),
                            'type' => mime_content_type($attachment['path']),
                            'disposition' => 'attachment'
                        ];
                    }
                }
            }

            // Send the API request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $this->error = "SendGrid API Error: " . curl_error($ch);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            if ($http_code >= 200 && $http_code < 300) {
                return true;
            } else {
                $this->error = "SendGrid API Error: HTTP Code $http_code. Response: " . $response;
                return false;
            }
        } catch (Exception $e) {
            $this->error = "SendGrid Exception: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Send email using Mailgun API
     *
     * @return bool Whether the email was sent successfully
     */
    private function sendWithMailgun()
    {
        try {
            $api_key = config('mailgun.api_key');
            $domain = config('mailgun.domain');

            if (empty($api_key) || empty($domain)) {
                $this->error = "Mailgun API key or domain is not configured.";
                return false;
            }

            // Prepare the multipart form data
            $data = [
                'from' => "{$this->from_name} <{$this->from_email}>",
                'to' => $this->to_email,
                'subject' => $this->subject,
                'html' => $this->message
            ];

            // Add reply-to if specified
            if ($this->reply_to) {
                $data['h:Reply-To'] = $this->reply_to;
            }

            // Prepare the curl request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.mailgun.net/v3/$domain/messages");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_USERPWD, "api:$api_key");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            // Add attachments
            if (!empty($this->attachments)) {
                $post_data = $data;
                foreach ($this->attachments as $i => $attachment) {
                    if (file_exists($attachment['path'])) {
                        $filename = $attachment['name'] ? $attachment['name'] : basename($attachment['path']);
                        $post_data["attachment[$i]"] = curl_file_create(
                            $attachment['path'],
                            mime_content_type($attachment['path']),
                            $filename
                        );
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $this->error = "Mailgun API Error: " . curl_error($ch);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            if ($http_code >= 200 && $http_code < 300) {
                return true;
            } else {
                $this->error = "Mailgun API Error: HTTP Code $http_code. Response: " . $response;
                return false;
            }
        } catch (Exception $e) {
            $this->error = "Mailgun Exception: " . $e->getMessage();
            return false;
        }
    }
}
