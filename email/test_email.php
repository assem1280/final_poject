<?php
/**
 * Email Notification System - Debug & Test Tool
 * 
 * This script helps diagnose email sending issues on XAMPP
 * Run this to test your SMTP configuration
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/EmailNotification.php';
require_once __DIR__ . '/EmailTemplates.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email Debug Tool - " . STORE_NAME . "</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background: #f0f2f5; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #667eea; margin-bottom: 5px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card h2 { margin-top: 0; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        .check-item { padding: 10px; margin: 5px 0; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #ddd; }
        .check-item.pass { border-left-color: #28a745; }
        .check-item.fail { border-left-color: #dc3545; background: #fff5f5; }
        .check-item.warn { border-left-color: #ffc107; background: #fffbf0; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; max-height: 300px; overflow-y: auto; }
        .btn { display: inline-block; padding: 12px 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 25px; margin: 5px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { opacity: 0.9; }
        .btn-secondary { background: #6c757d; }
        .btn-success { background: #28a745; }
        form { display: inline; }
        input[type='email'] { padding: 12px 15px; border: 2px solid #ddd; border-radius: 25px; width: 300px; font-size: 14px; }
        input[type='email']:focus { border-color: #667eea; outline: none; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background: #f8f9fa; }
        .debug-output { font-family: 'Consolas', monospace; font-size: 11px; line-height: 1.4; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Email Debug Tool</h1>
    <p class='subtitle'>Diagnose and test your email notification system</p>
";

// ============================================================
// STEP 1: Configuration Check
// ============================================================
echo "<div class='card'>";
echo "<h2>1️⃣ Configuration Check</h2>";

$allPassed = true;

// Check SMTP Host
$class = 'pass';
echo "<div class='check-item {$class}'>✓ SMTP Host: <strong>" . SMTP_HOST . "</strong></div>";

// Check SMTP Port
$class = 'pass';
echo "<div class='check-item {$class}'>✓ SMTP Port: <strong>" . SMTP_PORT . "</strong></div>";

// Check SMTP Encryption
$class = 'pass';
echo "<div class='check-item {$class}'>✓ Encryption: <strong>" . SMTP_ENCRYPTION . "</strong></div>";

// Check SMTP Username
if (SMTP_USERNAME === 'your-email@gmail.com') {
    $class = 'fail';
    $allPassed = false;
    echo "<div class='check-item {$class}'>✗ SMTP Username: <span class='error'>NOT CONFIGURED</span> - Update config.php!</div>";
} else {
    $class = 'pass';
    echo "<div class='check-item {$class}'>✓ SMTP Username: <strong>" . SMTP_USERNAME . "</strong></div>";
}

// Check SMTP Password
if (SMTP_PASSWORD === 'your-app-password') {
    $class = 'fail';
    $allPassed = false;
    echo "<div class='check-item {$class}'>✗ SMTP Password: <span class='error'>NOT CONFIGURED</span> - Update config.php!</div>";
} else {
    $class = 'pass';
    $masked = str_repeat('*', strlen(SMTP_PASSWORD) - 4) . substr(SMTP_PASSWORD, -4);
    echo "<div class='check-item {$class}'>✓ SMTP Password: <strong>{$masked}</strong></div>";
}

// Check Dev Mode
if (EMAIL_DEV_MODE) {
    $class = 'warn';
    echo "<div class='check-item {$class}'>⚠ Dev Mode: <span class='warning'>ENABLED</span> - Emails logged but NOT sent!</div>";
} else {
    $class = 'pass';
    echo "<div class='check-item {$class}'>✓ Dev Mode: <strong>DISABLED</strong> - Emails will be sent</div>";
}

// Check Debug Mode
if (EMAIL_DEBUG_MODE) {
    $class = 'pass';
    echo "<div class='check-item {$class}'>✓ Debug Mode: <strong>ENABLED</strong> - Detailed logs available</div>";
} else {
    $class = 'warn';
    echo "<div class='check-item {$class}'>⚠ Debug Mode: <strong>DISABLED</strong> - Enable for troubleshooting</div>";
}

echo "</div>";

// ============================================================
// STEP 2: PHP Extensions Check
// ============================================================
echo "<div class='card'>";
echo "<h2>2️⃣ PHP Extensions Check</h2>";

// OpenSSL
if (extension_loaded('openssl')) {
    echo "<div class='check-item pass'>✓ OpenSSL extension: <strong>Loaded</strong></div>";
} else {
    echo "<div class='check-item fail'>✗ OpenSSL extension: <span class='error'>NOT LOADED</span> - Required for SMTP!</div>";
    $allPassed = false;
}

// Sockets
if (function_exists('fsockopen')) {
    echo "<div class='check-item pass'>✓ fsockopen: <strong>Available</strong></div>";
} else {
    echo "<div class='check-item fail'>✗ fsockopen: <span class='error'>NOT AVAILABLE</span></div>";
    $allPassed = false;
}

// stream_socket_client
if (function_exists('stream_socket_client')) {
    echo "<div class='check-item pass'>✓ stream_socket_client: <strong>Available</strong></div>";
} else {
    echo "<div class='check-item warn'>⚠ stream_socket_client: Not available (fallback to fsockopen)</div>";
}

echo "</div>";

// ============================================================
// STEP 3: Connection Test
// ============================================================
echo "<div class='card'>";
echo "<h2>3️⃣ SMTP Connection Test</h2>";

if (isset($_GET['test_connection'])) {
    echo "<p class='info'>Testing connection to " . SMTP_HOST . ":" . SMTP_PORT . "...</p>";
    
    $emailer = new EmailNotification();
    $result = $emailer->testConnection();
    
    if ($result['success']) {
        echo "<div class='check-item pass'>✓ " . $result['message'] . "</div>";
    } else {
        echo "<div class='check-item fail'>✗ " . $result['message'] . "</div>";
    }
    
    if (!empty($result['debug'])) {
        echo "<h4>Debug Output:</h4>";
        echo "<pre class='debug-output'>";
        foreach ($result['debug'] as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";
    }
} else {
    echo "<p>Click the button below to test SMTP connection and authentication:</p>";
    echo "<a href='?test_connection=1' class='btn'>🔌 Test SMTP Connection</a>";
}
echo "</div>";

// ============================================================
// STEP 4: Send Test Email
// ============================================================
echo "<div class='card'>";
echo "<h2>4️⃣ Send Test Email</h2>";

if (isset($_POST['send_test'])) {
    $testEmail = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
    
    if (!$testEmail) {
        echo "<div class='check-item fail'>✗ Invalid email address</div>";
    } else {
        echo "<p class='info'>Sending test email to: <strong>{$testEmail}</strong></p>";
        
        // Generate welcome email as test
        $template = EmailTemplates::welcomeEmail('Test User', $testEmail);
        
        $emailer = new EmailNotification();
        $result = $emailer->send(
            $testEmail,
            'Test User',
            '[TEST] ' . $template['subject'],
            $template['html'],
            $template['text']
        );
        
        if ($result) {
            if (EMAIL_DEV_MODE) {
                echo "<div class='check-item warn'>⚠ Email LOGGED (Dev Mode) - Check emails_log folder</div>";
            } else {
                echo "<div class='check-item pass'>✓ Email sent successfully! Check your inbox.</div>";
            }
        } else {
            echo "<div class='check-item fail'>✗ Failed to send email: " . htmlspecialchars($emailer->getLastError()) . "</div>";
        }
        
        // Show debug output
        $debug = $emailer->getDebugOutput();
        if (!empty($debug)) {
            echo "<h4>SMTP Debug Output:</h4>";
            echo "<pre class='debug-output'>";
            foreach ($debug as $line) {
                echo htmlspecialchars($line) . "\n";
            }
            echo "</pre>";
        }
    }
}

echo "<form method='post' style='margin-top: 15px;'>
    <input type='email' name='test_email' placeholder='Enter your email address' required>
    <button type='submit' name='send_test' class='btn btn-success'>📧 Send Test Email</button>
</form>";

echo "</div>";

// ============================================================
// STEP 5: Recent Email Logs
// ============================================================
echo "<div class='card'>";
echo "<h2>5️⃣ Recent Email Logs</h2>";

$logPath = EMAIL_LOG_PATH;
if (is_dir($logPath)) {
    $files = glob($logPath . '*.html');
    rsort($files);
    $files = array_slice($files, 0, 10);
    
    if (!empty($files)) {
        echo "<table>";
        echo "<tr><th>Timestamp</th><th>Recipient</th><th>Action</th></tr>";
        foreach ($files as $file) {
            $filename = basename($file);
            $parts = explode('_', $filename);
            $date = isset($parts[0]) ? $parts[0] : '';
            $time = isset($parts[1]) ? str_replace('-', ':', $parts[1]) : '';
            $email = isset($parts[2]) ? str_replace(['_', '.html'], ['@', ''], $parts[2]) : '';
            
            echo "<tr>";
            echo "<td>{$date} {$time}</td>";
            echo "<td>" . htmlspecialchars($email) . "</td>";
            echo "<td><a href='../emails_log/{$filename}' target='_blank' class='btn' style='padding: 5px 15px; font-size: 12px;'>View</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No email logs found. Send a test email to generate logs.</p>";
    }
} else {
    echo "<p class='info'>Log directory doesn't exist yet. It will be created when the first email is sent.</p>";
}

echo "</div>";

// ============================================================
// STEP 6: Quick Fixes
// ============================================================
echo "<div class='card'>";
echo "<h2>6️⃣ Common Issues & Solutions</h2>";

echo "<table>";
echo "<tr><th>Issue</th><th>Solution</th></tr>";

echo "<tr>
    <td><strong>Authentication Failed</strong></td>
    <td>
        1. Enable 2-Step Verification in Google Account<br>
        2. Generate App Password: Google Account → Security → App passwords<br>
        3. Use the 16-character App Password (not your regular password)
    </td>
</tr>";

echo "<tr>
    <td><strong>Connection Timeout</strong></td>
    <td>
        1. Check if port 587 (TLS) or 465 (SSL) is not blocked by firewall<br>
        2. Try switching between TLS and SSL in config.php<br>
        3. Verify internet connection
    </td>
</tr>";

echo "<tr>
    <td><strong>Emails Logged but Not Sent</strong></td>
    <td>
        Set <code>EMAIL_DEV_MODE</code> to <code>false</code> in config.php
    </td>
</tr>";

echo "<tr>
    <td><strong>SSL Certificate Error</strong></td>
    <td>
        Already handled - SMTPOptions configured to allow self-signed certificates
    </td>
</tr>";

echo "</table>";
echo "</div>";

// ============================================================
// Configuration File Location
// ============================================================
echo "<div class='card'>";
echo "<h2>📁 Configuration File</h2>";
echo "<p>Edit this file to update your SMTP settings:</p>";
echo "<pre>" . realpath(__DIR__ . '/config.php') . "</pre>";
echo "</div>";

echo "
</div>
</body>
</html>";
?>
