<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', 'logs/smtp_test.log');

echo "<h1>SMTP Connection Test</h1>";
echo "<pre>";

// Check for vendor directory
echo "Checking for vendor directory...\n";
if (is_dir('vendor')) {
    echo "✓ Vendor directory exists at: " . realpath('vendor') . "\n";

    // Check for PHPMailer directory
    if (is_dir('vendor/phpmailer/phpmailer')) {
        echo "✓ PHPMailer directory exists at: " . realpath('vendor/phpmailer/phpmailer') . "\n";

        // List PHPMailer files
        echo "PHPMailer files:\n";
        $files = scandir('vendor/phpmailer/phpmailer');
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                echo "  - " . $file . "\n";
            }
        }
        echo "\n";
    } else {
        echo "✗ PHPMailer directory not found\n";
    }
} else {
    echo "✗ Vendor directory not found\n";
}

// Try to load dependencies
echo "Trying to load dependencies...\n";
try {
    require_once 'vendor/autoload.php';
    echo "✓ Autoloader loaded successfully\n";

    require_once 'config.php';
    echo "✓ Config loaded successfully\n";

    require_once 'includes/utils.php';
    echo "✓ Utils loaded successfully\n";

    echo "\nTesting PHPMailer class access...\n";
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "✓ PHPMailer class exists\n";
    } else {
        echo "✗ PHPMailer class not found\n";
    }

    if (class_exists('PHPMailer\\PHPMailer\\SMTP')) {
        echo "✓ SMTP class exists\n";
    } else {
        echo "✗ SMTP class not found\n";
    }

    if (class_exists('PHPMailer\\PHPMailer\\Exception')) {
        echo "✓ Exception class exists\n";
    } else {
        echo "✗ Exception class not found\n";
    }
} catch (Exception $e) {
    echo "✗ Error loading dependencies: " . $e->getMessage() . "\n";
}

echo "</pre>";

// SMTP Testing Section
echo "<hr><h2>SMTP Connection Test</h2>";
echo "<pre>";

// Get SMTP settings from URL parameters or use defaults
$host = $_GET['host'] ?? 'lessonsdrivingschool.co.uk';
$port = $_GET['port'] ?? 587;
$username = $_GET['username'] ?? 'unbiased@lessonsdrivingschool.co.uk';
$password = $_GET['password'] ?? '60_horos$co7E!_pe';
$encryption = $_GET['encryption'] ?? 'tls';
$from = $_GET['from'] ?? 'unbiased@lessonsdrivingschool.co.uk';
$to = $_GET['to'] ?? 'yarnovichj@mkmors.com';

echo "Testing SMTP connection with the following settings:\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Username: " . ($username ? '[PROVIDED]' : '[EMPTY]') . "\n";
echo "Password: " . ($password ? '[PROVIDED]' : '[EMPTY]') . "\n";
echo "Encryption: $encryption\n";
echo "From: $from\n";
echo "To: $to\n\n";

try {
    // Initialize PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    // Enable verbose debug output
    $mail->SMTPDebug = 3; // Detailed debug output
    $mail->Debugoutput = function ($str, $level) {
        echo htmlspecialchars($str) . "<br>\n";
    };

    // Set mailer to use SMTP
    $mail->isSMTP();

    // Disable SSL verification (for testing only)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    // SMTP server settings
    $mail->Host = $host;
    $mail->Port = $port;
    $mail->SMTPSecure = $encryption;
    $mail->SMTPAuth = !empty($username);

    // SMTP authentication
    if (!empty($username)) {
        $mail->Username = $username;
        $mail->Password = $password;
    }

    // Set sender and recipient
    $mail->setFrom($from, 'SMTP Test');
    $mail->addAddress($to, 'Test Recipient');

    // Email content
    $mail->Subject = 'SMTP Test Email';
    $mail->Body = 'This is a test email to verify SMTP connection.';

    // Try connecting without sending
    echo "Attempting to connect to SMTP server...\n";
    $mail->SMTPConnect();
    echo "✓ Connection successful!\n";

    // Try sending an email
    echo "\nAttempting to send a test email...\n";
    if ($mail->send()) {
        echo "✓ Test email sent successfully!\n";
    } else {
        echo "✗ Failed to send test email.\n";
    }
} catch (PHPMailer\PHPMailer\Exception $e) {
    echo "✗ PHPMailer Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ General Error: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Show diagnostic information
echo "<hr><h3>Diagnostic Information:</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "\n";
echo "Extensions loaded: " . implode(', ', get_loaded_extensions()) . "\n";
echo "</pre>";

// Add a form to test with different parameters
echo "<hr><h3>Try with different settings:</h3>";
echo '<form method="GET" action="test_smtp.php">';
echo '<div style="margin-bottom: 10px;"><label style="display:inline-block;width:100px;">Host:</label>';
echo '<input type="text" name="host" value="' . htmlspecialchars($host) . '" style="width:300px;"></div>';

echo '<div style="margin-bottom: 10px;"><label style="display:inline-block;width:100px;">Port:</label>';
echo '<input type="number" name="port" value="' . htmlspecialchars($port) . '"></div>';

echo '<div style="margin-bottom: 10px;"><label style="display:inline-block;width:100px;">Username:</label>';
echo '<input type="text" name="username" value="' . htmlspecialchars($username) . '" style="width:300px;"></div>';

echo '<div style="margin-bottom: 10px;"><label style="display:inline-block;width:100px;">Password:</label>';
echo '<input type="password" name="password" value="' . htmlspecialchars($password) . '" style="width:300px;"></div>';

echo '<div style="margin-bottom: 10px;"><label style="display:inline-block;width:100px;">Encryption:</label>';
echo '<select name="encryption">';
echo '<option value="tls"' . ($encryption == 'tls' ? ' selected' : '') . '>TLS</option>';
echo '<option value="ssl"' . ($encryption == 'ssl' ? ' selected' : '') . '>SSL</option>';
echo '<option value=""' . ($encryption == '' ? ' selected' : '') . '>None</option>';
echo '</select></div>';

echo '<div style="margin-bottom: 10px;"><label style="display:inline-block;width:100px;">From Email:</label>';
echo '<input type="email" name="from" value="' . htmlspecialchars($from) . '" style="width:300px;"></div>';

echo '<div style="margin-bottom: 10px;"><label style="display:inline-block;width:100px;">To Email:</label>';
echo '<input type="email" name="to" value="' . htmlspecialchars($to) . '" style="width:300px;"></div>';

echo '<button type="submit">Test Connection</button>';
echo '</form>';
