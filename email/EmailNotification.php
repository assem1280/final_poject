<?php
/**
 * EmailNotification Class
 * 
 * A reusable class for sending email notifications using PHPMailer
 * Supports SMTP (Gmail, etc.) and works on XAMPP localhost
 */

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

class EmailNotification {
    
    private $mailer;
    private $lastError = '';
    private $debugOutput = [];
    
    /**
     * Constructor - Initialize PHPMailer with SMTP settings
     */
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    /**
     * Configure SMTP settings from config file
     */
    private function configureSMTP() {
        try {
            // Validate configuration first
            $configErrors = validateEmailConfig();
            if (!empty($configErrors)) {
                $this->lastError = "Configuration Error: " . implode(', ', $configErrors);
                error_log("[EmailNotification] " . $this->lastError);
            }
            
            // Enable SMTP debugging if in debug mode
            if (EMAIL_DEBUG_MODE) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                // Capture debug output
                $this->mailer->Debugoutput = function($str, $level) {
                    $this->debugOutput[] = "[Level $level] $str";
                    error_log("[PHPMailer Debug] $str");
                };
            } else {
                $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
            }
            
            // SMTP Configuration
            $this->mailer->isSMTP();
            $this->mailer->Host       = SMTP_HOST;
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = SMTP_USERNAME;
            $this->mailer->Password   = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = SMTP_PORT;
            
            // SMTP Options for localhost/XAMPP compatibility
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Set sender
            $this->mailer->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            
            // Set character encoding
            $this->mailer->CharSet = 'UTF-8';
            
            error_log("[EmailNotification] SMTP configured: Host=" . SMTP_HOST . ", Port=" . SMTP_PORT . ", User=" . SMTP_USERNAME);
            
        } catch (Exception $e) {
            $this->lastError = "SMTP Configuration Error: " . $e->getMessage();
            error_log($this->lastError);
        }
    }
    
    /**
     * Send an email
     * 
     * @param string $toEmail Recipient email address
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $htmlBody HTML content of the email
     * @param string $textBody Plain text content (optional)
     * @return bool True if sent successfully, false otherwise
     */
    public function send($toEmail, $toName, $subject, $htmlBody, $textBody = '') {
        $this->debugOutput = []; // Reset debug output
        
        error_log("[EmailNotification] Attempting to send email to: {$toEmail}");
        error_log("[EmailNotification] Subject: {$subject}");
        error_log("[EmailNotification] DEV_MODE: " . (EMAIL_DEV_MODE ? 'true' : 'false'));
        
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipient
            $this->mailer->addAddress($toEmail, $toName);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $htmlBody;
            $this->mailer->AltBody = $textBody ?: strip_tags($htmlBody);
            
            // Log email if enabled
            if (EMAIL_LOG_ENABLED) {
                $this->logEmail($toEmail, $toName, $subject, $htmlBody);
            }
            
            // In development mode, only log the email, don't send
            if (EMAIL_DEV_MODE) {
                error_log("[EmailNotification] DEV MODE: Email logged but not sent to {$toEmail}");
                return true;
            }
            
            // Send the email
            error_log("[EmailNotification] Connecting to SMTP server...");
            $result = $this->mailer->send();
            error_log("[EmailNotification] Email sent successfully to {$toEmail}");
            return true;
            
        } catch (Exception $e) {
            $this->lastError = "Email sending failed: " . $this->mailer->ErrorInfo;
            error_log("[EmailNotification] ERROR: " . $this->lastError);
            error_log("[EmailNotification] Exception: " . $e->getMessage());
            
            // Log debug output if available
            if (!empty($this->debugOutput)) {
                error_log("[EmailNotification] SMTP Debug Output:");
                foreach ($this->debugOutput as $line) {
                    error_log("  " . $line);
                }
            }
            
            return false;
        }
    }
    
    /**
     * Log email to file for testing/debugging
     */
    private function logEmail($toEmail, $toName, $subject, $htmlBody) {
        try {
            // Ensure log directory exists
            if (!is_dir(EMAIL_LOG_PATH)) {
                mkdir(EMAIL_LOG_PATH, 0755, true);
            }
            
            // Create log filename
            $timestamp = date('Y-m-d_H-i-s');
            $safeEmail = preg_replace('/[^a-zA-Z0-9_]/', '_', $toEmail);
            $filename = EMAIL_LOG_PATH . "{$timestamp}_{$safeEmail}.html";
            
            // Create log content
            $logContent = "
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; color: #333; background: #f5f5f5; margin: 0; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 20px; margin: 0; border: 1px solid #ddd; }
        .footer { background: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
        .meta { background: #fff3cd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .meta p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class='header'><h2>📧 Email Log - " . STORE_NAME . "</h2></div>
    <div class='content'>
        <div class='meta'>
            <p><strong>To:</strong> {$toEmail} ({$toName})</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Sent:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Mode:</strong> " . (EMAIL_DEV_MODE ? 'Development (Not Actually Sent)' : 'Production') . "</p>
        </div>
        <hr>
        <h3>Message Content:</h3>
        <div style='border: 1px solid #eee; padding: 15px; background: #fafafa;'>
            {$htmlBody}
        </div>
    </div>
    <div class='footer'><p>Email logged for testing - " . STORE_NAME . "</p></div>
</body>
</html>";
            
            file_put_contents($filename, $logContent);
            error_log("[EmailNotification] Email logged to: {$filename}");
            
        } catch (Exception $e) {
            error_log("[EmailNotification] Failed to log email: " . $e->getMessage());
        }
    }
    
    /**
     * Get the last error message
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Get debug output from last send attempt
     * @return array
     */
    public function getDebugOutput() {
        return $this->debugOutput;
    }
    
    /**
     * Test SMTP connection without sending an email
     * @return array ['success' => bool, 'message' => string, 'debug' => array]
     */
    public function testConnection() {
        $this->debugOutput = [];
        
        try {
            // Temporarily enable debug for this test
            $originalDebug = $this->mailer->SMTPDebug;
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mailer->Debugoutput = function($str, $level) {
                $this->debugOutput[] = $str;
            };
            
            // Try to connect
            $smtp = $this->mailer->getSMTPInstance();
            $smtp->Timeout = 10;
            
            $connected = $smtp->connect(
                (SMTP_ENCRYPTION === 'ssl' ? 'ssl://' : '') . SMTP_HOST,
                SMTP_PORT
            );
            
            if (!$connected) {
                return [
                    'success' => false,
                    'message' => 'Could not connect to SMTP server: ' . SMTP_HOST . ':' . SMTP_PORT,
                    'debug' => $this->debugOutput
                ];
            }
            
            // Try EHLO
            if (!$smtp->hello(gethostname())) {
                $smtp->close();
                return [
                    'success' => false,
                    'message' => 'EHLO command failed',
                    'debug' => $this->debugOutput
                ];
            }
            
            // Try STARTTLS if using TLS
            if (SMTP_ENCRYPTION === 'tls') {
                if (!$smtp->startTLS()) {
                    $smtp->close();
                    return [
                        'success' => false,
                        'message' => 'STARTTLS failed',
                        'debug' => $this->debugOutput
                    ];
                }
                // Re-EHLO after TLS
                $smtp->hello(gethostname());
            }
            
            // Try authentication
            if (!$smtp->authenticate(SMTP_USERNAME, SMTP_PASSWORD)) {
                $smtp->close();
                return [
                    'success' => false,
                    'message' => 'Authentication failed. Check your email and app password.',
                    'debug' => $this->debugOutput
                ];
            }
            
            $smtp->quit();
            $smtp->close();
            
            // Restore original debug setting
            $this->mailer->SMTPDebug = $originalDebug;
            
            return [
                'success' => true,
                'message' => 'SMTP connection and authentication successful!',
                'debug' => $this->debugOutput
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'debug' => $this->debugOutput
            ];
        }
    }
}
?>
