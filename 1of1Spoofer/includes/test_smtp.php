<?php
require_once 'smtp_functions.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get test parameters from POST or use defaults
$host = $_POST['host'] ?? '';
$port = $_POST['port'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$security = $_POST['security'] ?? 'none';

// Check if form was submitted
$formSubmitted = !empty($_POST['submit']);

function detailedSmtpTest($host, $port, $username, $password, $security)
{
    $smtp = array(
        'host' => $host,
        'port' => (int)$port,
        'username' => $username,
        'password' => $password,
        'security' => $security
    );

    $mail = new PHPMailer(true);
    $debugOutput = '';

    $outputBuffer = '';
    $captureOutput = function ($str, $level) use (&$outputBuffer) {
        $line = date('Y-m-d H:i:s') . " [Level $level] " . $str . "\n";
        $outputBuffer .= $line;
        return $line;
    };

    $results = array(
        'basic' => array('success' => false, 'message' => '', 'output' => ''),
        'ssl' => array('success' => false, 'message' => '', 'output' => ''),
        'auth_methods' => array(),
        'socket' => array('success' => false, 'message' => '', 'output' => '')
    );

    // Test 1: Basic Connection
    try {
        $outputBuffer = '';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPDebug = 3;
        $mail->Debugoutput = function ($str, $level) use (&$outputBuffer, $captureOutput) {
            echo $captureOutput($str, $level);
        };
        $mail->Host = $smtp['host'];
        $mail->Port = $smtp['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];

        // Set security if specified
        if ($smtp['security'] == 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else if ($smtp['security'] == 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // Try connection
        if ($mail->smtpConnect()) {
            $results['basic'] = array(
                'success' => true,
                'message' => "Basic connection successful!",
                'output' => $outputBuffer
            );
            $mail->smtpClose();
        }
    } catch (Exception $e) {
        $results['basic'] = array(
            'success' => false,
            'message' => "Basic connection failed: " . $e->getMessage(),
            'output' => $outputBuffer
        );
    }

    // Test 2: With SSL Options
    try {
        $outputBuffer = '';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPDebug = 3;
        $mail->Debugoutput = function ($str, $level) use (&$outputBuffer, $captureOutput) {
            echo $captureOutput($str, $level);
        };
        $mail->Host = $smtp['host'];
        $mail->Port = $smtp['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];

        // Set security if specified
        if ($smtp['security'] == 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else if ($smtp['security'] == 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        if ($mail->smtpConnect()) {
            $results['ssl'] = array(
                'success' => true,
                'message' => "SSL connection successful!",
                'output' => $outputBuffer
            );
            $mail->smtpClose();
        }
    } catch (Exception $e) {
        $results['ssl'] = array(
            'success' => false,
            'message' => "SSL connection failed: " . $e->getMessage(),
            'output' => $outputBuffer
        );
    }

    // Test 3: With Different Auth Methods
    $authMethods = ['PLAIN', 'LOGIN', 'CRAM-MD5'];
    foreach ($authMethods as $authMethod) {
        try {
            $outputBuffer = '';
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = 3;
            $mail->Debugoutput = function ($str, $level) use (&$outputBuffer, $captureOutput) {
                echo $captureOutput($str, $level);
            };
            $mail->Host = $smtp['host'];
            $mail->Port = $smtp['port'];
            $mail->SMTPAuth = true;
            $mail->AuthType = $authMethod;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];

            // Set security if specified
            if ($smtp['security'] == 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else if ($smtp['security'] == 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            if ($mail->smtpConnect()) {
                $results['auth_methods'][$authMethod] = array(
                    'success' => true,
                    'message' => "Connection with $authMethod successful!",
                    'output' => $outputBuffer
                );
                $mail->smtpClose();
            }
        } catch (Exception $e) {
            $results['auth_methods'][$authMethod] = array(
                'success' => false,
                'message' => "Connection with $authMethod failed: " . $e->getMessage(),
                'output' => $outputBuffer
            );
        }
    }

    // Test 4: Socket Test
    echo "\nTest 4: Direct Socket Test\n";
    $outputBuffer = '';
    $fp = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, 30);
    if (!$fp) {
        $results['socket'] = array(
            'success' => false,
            'message' => "Socket connection failed: $errstr ($errno)",
            'output' => ''
        );
    } else {
        $response = fgets($fp, 515);
        $results['socket'] = array(
            'success' => true,
            'message' => "Socket connection successful! Server response: " . $response,
            'output' => $response
        );
        fclose($fp);
    }

    return $results;
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>SMTP Connection Tester</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        input[type="submit"] {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background: #45a049;
        }

        .results {
            margin-top: 20px;
        }

        .test-result {
            margin-bottom: 30px;
            border-left: 4px solid #ddd;
            padding-left: 15px;
        }

        .success {
            border-left-color: #4CAF50;
        }

        .failure {
            border-left-color: #f44336;
        }

        .result-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .result-message {
            margin-bottom: 10px;
        }

        .output {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            font-family: monospace;
            white-space: pre-wrap;
            overflow-x: auto;
            max-height: 200px;
            overflow-y: auto;
        }

        .summary {
            background: #e9f7ef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>SMTP Connection Tester</h1>

        <form method="post" action="">
            <div>
                <label for="host">SMTP Host:</label>
                <input type="text" id="host" name="host" value="<?php echo htmlspecialchars($host); ?>" required>
            </div>

            <div>
                <label for="port">SMTP Port:</label>
                <input type="text" id="port" name="port" value="<?php echo htmlspecialchars($port); ?>" required>
            </div>

            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>

            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>" required>
            </div>

            <div>
                <label for="security">Security:</label>
                <select id="security" name="security">
                    <option value="none" <?php echo $security == 'none' ? 'selected' : ''; ?>>None</option>
                    <option value="ssl" <?php echo $security == 'ssl' ? 'selected' : ''; ?>>SSL/TLS</option>
                    <option value="tls" <?php echo $security == 'tls' ? 'selected' : ''; ?>>STARTTLS</option>
                </select>
            </div>

            <div>
                <input type="submit" name="submit" value="Test Connection">
            </div>
        </form>

        <?php if ($formSubmitted): ?>
            <div class="results">
                <h2>Test Results</h2>

                <?php
                $testResults = detailedSmtpTest($host, $port, $username, $password, $security);
                $workingConfigs = [];

                // Basic Connection Test
                echo '<div class="test-result ' . ($testResults['basic']['success'] ? 'success' : 'failure') . '">';
                echo '<div class="result-title">Test 1: Basic Connection</div>';
                echo '<div class="result-message">' . $testResults['basic']['message'] . '</div>';
                echo '<div class="output">' . htmlspecialchars($testResults['basic']['output']) . '</div>';
                echo '</div>';

                if ($testResults['basic']['success']) {
                    $workingConfigs[] = "Basic connection with " . $security . " security";
                }

                // SSL Options Test
                echo '<div class="test-result ' . ($testResults['ssl']['success'] ? 'success' : 'failure') . '">';
                echo '<div class="result-title">Test 2: Connection with SSL Options</div>';
                echo '<div class="result-message">' . $testResults['ssl']['message'] . '</div>';
                echo '<div class="output">' . htmlspecialchars($testResults['ssl']['output']) . '</div>';
                echo '</div>';

                if ($testResults['ssl']['success']) {
                    $workingConfigs[] = "Connection with SSL options and " . $security . " security";
                }

                // Auth Methods Tests
                foreach ($testResults['auth_methods'] as $method => $result) {
                    echo '<div class="test-result ' . ($result['success'] ? 'success' : 'failure') . '">';
                    echo '<div class="result-title">Test 3: ' . $method . ' Authentication</div>';
                    echo '<div class="result-message">' . $result['message'] . '</div>';
                    echo '<div class="output">' . htmlspecialchars($result['output']) . '</div>';
                    echo '</div>';

                    if ($result['success']) {
                        $workingConfigs[] = "AUTH " . $method . " with " . $security . " security";
                    }
                }

                // Socket Test
                echo '<div class="test-result ' . ($testResults['socket']['success'] ? 'success' : 'failure') . '">';
                echo '<div class="result-title">Test 4: Direct Socket Connection</div>';
                echo '<div class="result-message">' . $testResults['socket']['message'] . '</div>';
                if ($testResults['socket']['output']) {
                    echo '<div class="output">' . htmlspecialchars($testResults['socket']['output']) . '</div>';
                }
                echo '</div>';

                // Summary
                echo '<div class="summary">';
                echo '<h3>Summary</h3>';
                if (!empty($workingConfigs)) {
                    echo '<p>✅ Working configurations:</p>';
                    echo '<ul>';
                    foreach ($workingConfigs as $config) {
                        echo '<li>' . htmlspecialchars($config) . '</li>';
                    }
                    echo '</ul>';

                    echo '<p>Recommended SMTP settings for your application:</p>';
                    echo '<pre>';
                    echo "Host: " . htmlspecialchars($host) . "\n";
                    echo "Port: " . htmlspecialchars($port) . "\n";
                    echo "Username: " . htmlspecialchars($username) . "\n";
                    echo "Security: " . htmlspecialchars($security) . "\n";
                    if (isset($testResults['auth_methods']['PLAIN']['success']) && $testResults['auth_methods']['PLAIN']['success']) {
                        echo "Auth Method: PLAIN\n";
                    } elseif (isset($testResults['auth_methods']['LOGIN']['success']) && $testResults['auth_methods']['LOGIN']['success']) {
                        echo "Auth Method: LOGIN\n";
                    } elseif (isset($testResults['auth_methods']['CRAM-MD5']['success']) && $testResults['auth_methods']['CRAM-MD5']['success']) {
                        echo "Auth Method: CRAM-MD5\n";
                    }
                    echo '</pre>';
                } else {
                    echo '<p>❌ No working configurations found. Please try:</p>';
                    echo '<ul>';
                    echo '<li>Verify your credentials are correct</li>';
                    echo '<li>Check if the SMTP server is online</li>';
                    echo '<li>Try a different security setting</li>';
                    echo '<li>Contact your SMTP provider for specific requirements</li>';
                    echo '</ul>';
                }
                echo '</div>';
                ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>