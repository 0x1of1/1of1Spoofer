<?php

/**
 * 1of1Spoofer Environment Test Script
 * 
 * Run this script to verify that your server environment has all the required
 * components to run 1of1Spoofer properly. This script checks PHP version,
 * required extensions, directory permissions, and email functionality.
 */

// Prevent direct access in production
if (
    file_exists(__DIR__ . '/.env') &&
    !isset($_GET['force']) &&
    file_get_contents(__DIR__ . '/.env') !== ''
) {
    die("This script is for testing only. To run it on a production server, add ?force to the URL.");
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Function to check if a PHP extension is loaded
function check_extension($name)
{
    if (extension_loaded($name)) {
        return "<span style='color:green'>✓ $name</span>";
    } else {
        return "<span style='color:red'>✗ $name</span>";
    }
}

// Function to check directory permissions
function check_dir_permissions($path)
{
    if (!file_exists($path)) {
        return "<span style='color:red'>✗ Directory does not exist</span>";
    }

    if (is_writable($path)) {
        return "<span style='color:green'>✓ Writable</span>";
    } else {
        return "<span style='color:red'>✗ Not writable</span>";
    }
}

// Function to check mail functionality
function check_mail()
{
    // Check if mail() function is available
    if (!function_exists('mail')) {
        return "<span style='color:red'>✗ mail() function is disabled</span>";
    }

    return "<span style='color:green'>✓ mail() function available</span>";
}

// Check if Composer dependencies are installed
function check_composer()
{
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        return "<span style='color:green'>✓ Composer dependencies installed</span>";
    } else {
        return "<span style='color:red'>✗ Composer dependencies not installed</span>";
    }
}

// Store test results
$tests = [
    'PHP Version' => version_compare(PHP_VERSION, '8.0.0', '>=')
        ? "<span style='color:green'>✓ " . PHP_VERSION . "</span>"
        : "<span style='color:red'>✗ " . PHP_VERSION . " (required: 8.0.0+)</span>",
    'Extensions' => [
        'JSON' => check_extension('json'),
        'FileInfo' => check_extension('fileinfo'),
        'cURL' => check_extension('curl'),
        'ZIP' => check_extension('zip'),
        'MBString' => check_extension('mbstring'),
        'XML' => check_extension('xml')
    ],
    'Directories' => [
        'Logs' => check_dir_permissions(__DIR__ . '/logs'),
        'Uploads' => check_dir_permissions(__DIR__ . '/uploads')
    ],
    'Email Function' => check_mail(),
    'Composer' => check_composer()
];

// Check DNS functions for domain analysis
if (function_exists('dns_get_record')) {
    $dns_functions = "<span style='color:green'>✓ DNS functions available</span>";
} else {
    $dns_functions = "<span style='color:red'>✗ DNS functions not available</span>";
}
$tests['DNS Functions'] = $dns_functions;

// Check if config file exists
if (file_exists(__DIR__ . '/config.php')) {
    $config_file = "<span style='color:green'>✓ Config file exists</span>";
} else {
    $config_file = "<span style='color:red'>✗ Config file not found</span>";
}
$tests['Config File'] = $config_file;

// Check server information
$server_info = [
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Server IP' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'Unknown',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'Max Upload Size' => ini_get('upload_max_filesize'),
    'Max POST Size' => ini_get('post_max_size'),
    'Memory Limit' => ini_get('memory_limit')
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1of1Spoofer Environment Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        h2 {
            color: #3498db;
            margin-top: 30px;
        }

        .section {
            background: #f9f9f9;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .summary {
            margin-top: 30px;
            padding: 15px;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 30px;
            font-size: 0.9em;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>1of1Spoofer Environment Test</h1>
        <p>This tool checks if your server environment meets all the requirements for running 1of1Spoofer.</p>

        <div class="section">
            <h2>PHP Environment</h2>
            <table>
                <tr>
                    <th>Requirement</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>PHP Version (8.0.0+ required)</td>
                    <td><?php echo $tests['PHP Version']; ?></td>
                </tr>
                <?php foreach ($tests['Extensions'] as $extension => $status): ?>
                    <tr>
                        <td>PHP <?php echo $extension; ?> Extension</td>
                        <td><?php echo $status; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section">
            <h2>Directories & Permissions</h2>
            <table>
                <tr>
                    <th>Directory</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($tests['Directories'] as $directory => $status): ?>
                    <tr>
                        <td><?php echo $directory; ?></td>
                        <td><?php echo $status; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section">
            <h2>Email Functionality</h2>
            <table>
                <tr>
                    <th>Requirement</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>PHP mail() Function</td>
                    <td><?php echo $tests['Email Function']; ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>DNS Functions (for Domain Analysis)</h2>
            <table>
                <tr>
                    <th>Requirement</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>DNS Functions</td>
                    <td><?php echo $tests['DNS Functions']; ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Configuration</h2>
            <table>
                <tr>
                    <th>Requirement</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>Config File</td>
                    <td><?php echo $tests['Config File']; ?></td>
                </tr>
                <tr>
                    <td>Composer Dependencies</td>
                    <td><?php echo $tests['Composer']; ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Server Information</h2>
            <table>
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                </tr>
                <?php foreach ($server_info as $key => $value): ?>
                    <tr>
                        <td><?php echo $key; ?></td>
                        <td><?php echo $value; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php
        // Determine overall status
        $all_pass = true;
        $has_warnings = false;

        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $all_pass = false;
        }

        foreach ($tests['Extensions'] as $status) {
            if (strpos($status, 'red') !== false) {
                $all_pass = false;
            }
        }

        foreach ($tests['Directories'] as $status) {
            if (strpos($status, 'red') !== false) {
                $all_pass = false;
            }
        }

        if (strpos($tests['Email Function'], 'red') !== false) {
            $has_warnings = true;
        }

        if (strpos($tests['DNS Functions'], 'red') !== false) {
            $has_warnings = true;
        }

        if (strpos($tests['Config File'], 'red') !== false) {
            $all_pass = false;
        }

        if (strpos($tests['Composer'], 'red') !== false) {
            $all_pass = false;
        }
        ?>

        <?php if ($all_pass && !$has_warnings): ?>
            <div class="section success">
                <h2>All Tests Passed! ✅</h2>
                <p>Your environment meets all the requirements for running 1of1Spoofer. You're good to go!</p>
            </div>
        <?php elseif ($all_pass && $has_warnings): ?>
            <div class="section warning">
                <h2>⚠️ Tests Passed with Warnings</h2>
                <p>Your environment meets the basic requirements, but there are some warnings that might affect functionality.</p>
                <p>For optimal performance, please address the items marked with warnings above.</p>
            </div>
        <?php else: ?>
            <div class="section error">
                <h2>Some Tests Failed ❌</h2>
                <p>Your environment does not meet all the requirements for running 1of1Spoofer. Please fix the issues marked in red above.</p>
            </div>
        <?php endif; ?>

        <footer>
            <p>1of1Spoofer Environment Test v1.0</p>
            <p>This test script is part of the <a href="https://github.com/yourusername/1of1Spoofer">1of1Spoofer</a> project.</p>
        </footer>
    </div>
</body>

</html>