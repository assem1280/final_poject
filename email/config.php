<?php
/**
 * Email Configuration File
 * SMTP settings for PHPMailer
 * 
 * Instructions for Gmail SMTP:
 * 1. Enable 2-Step Verification in your Google Account
 * 2. Generate an App Password: Google Account > Security > App passwords
 * 3. Use that App Password below (not your regular Gmail password)
 * 
 * IMPORTANT: Replace the placeholder values with your actual Gmail credentials!
 */

// ============================================================
// SMTP Configuration - UPDATE THESE VALUES!
// ============================================================
define('SMTP_HOST', 'smtp.gmail.com');           // SMTP server hostname
define('SMTP_PORT', 587);                         // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', 'your-email@gmail.com'); // TODO: Replace with your Gmail address
define('SMTP_PASSWORD', 'your-app-password');    // TODO: Replace with your Gmail App Password (16 characters)
define('SMTP_ENCRYPTION', 'tls');                // Encryption type: 'tls' or 'ssl'

// Sender Information
define('MAIL_FROM_EMAIL', 'your-email@gmail.com'); // TODO: Replace with your Gmail address
define('MAIL_FROM_NAME', 'ROSE Perfume Store');

// Store Information (used in email templates)
define('STORE_NAME', 'ROSE Perfume Store');
define('STORE_URL', 'http://localhost/pefumeppp/docs/');
define('STORE_LOGO', 'http://localhost/pefumeppp/docs/images/logo.png');

// ============================================================
// Email Settings
// ============================================================

// Set to true to see detailed SMTP communication (for debugging connection issues)
define('EMAIL_DEBUG_MODE', true);

// Log emails to files for testing/verification
define('EMAIL_LOG_ENABLED', true);
define('EMAIL_LOG_PATH', __DIR__ . '/../emails_log/');

// ============================================================
// IMPORTANT: Development vs Production Mode
// ============================================================
// When EMAIL_DEV_MODE = true:  Emails are ONLY logged to files, NOT actually sent
// When EMAIL_DEV_MODE = false: Emails are ACTUALLY SENT via SMTP
//
// Set to FALSE to actually send emails!
define('EMAIL_DEV_MODE', false);

// ============================================================
// Validation Check - Warns if using placeholder values
// ============================================================
function validateEmailConfig() {
    $errors = [];
    
    if (SMTP_USERNAME === 'your-email@gmail.com') {
        $errors[] = 'SMTP_USERNAME is still set to placeholder value';
    }
    
    if (SMTP_PASSWORD === 'your-app-password') {
        $errors[] = 'SMTP_PASSWORD is still set to placeholder value';
    }
    
    if (MAIL_FROM_EMAIL === 'your-email@gmail.com') {
        $errors[] = 'MAIL_FROM_EMAIL is still set to placeholder value';
    }
    
    return $errors;
}
?>
