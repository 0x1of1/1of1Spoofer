<?php
// Test script for SMTP connection

// Require PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Create a log file for output
$logFile = 'logs/smtp_test_log.txt';
file_put_contents($logFile, "Starting SMTP test at " . date('Y-m-d H:i:s') . "\n");

// SMTP Configuration
$smtpSettings = [
    'host' => 'lessonsdrivingschool.co.uk',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'unbiased@lessonsdrivingschool.co.uk',
    'password' => '60_horos$co7E!_pe',
    'from_email' => 'unbiased@lessonsdrivingschool.co.uk', // Use the same email as username
    'to_email' => 'yarnovichj@mkmors.com', // The recipient email
    'debug_level' => 4
];

// Output configuration (omitting password)
$logOutput = $smtpSettings;
$logOutput['password'] = '********';
file_put_contents($logFile, "SMTP Settings: " . print_r($logOutput, true) . "\n", FILE_APPEND);

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = $smtpSettings['debug_level'];
    $mail->Debugoutput = function ($str, $level) use ($logFile) {
        file_put_contents($logFile, $str . "\n", FILE_APPEND);
    };
    $mail->isSMTP();
    $mail->Host = $smtpSettings['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpSettings['username'];
    $mail->Password = $smtpSettings['password'];
    $mail->SMTPSecure = $smtpSettings['encryption'];
    $mail->Port = $smtpSettings['port'];

    // SSL verification
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    // Recipients
    $mail->setFrom($smtpSettings['from_email'], 'SMTP Test');
    $mail->addAddress($smtpSettings['to_email']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test Email';
    $mail->Body    = 'This is a test email to verify SMTP configuration.';
    $mail->AltBody = 'This is a test email to verify SMTP configuration.';

    file_put_contents($logFile, "Attempting to send email...\n", FILE_APPEND);
    $mail->send();
    file_put_contents($logFile, "Email sent successfully!\n", FILE_APPEND);
    echo "Email sent successfully! Check $logFile for details.\n";
} catch (Exception $e) {
    file_put_contents($logFile, "Email could not be sent. Error: {$mail->ErrorInfo}\n", FILE_APPEND);
    echo "Email could not be sent. Error: {$mail->ErrorInfo}\n";
}
