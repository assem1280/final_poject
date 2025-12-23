<?php
/**
 * Database Setup for Email Notification System
 * 
 * Run this script once to create the necessary database table
 * for tracking email notifications.
 */

require_once __DIR__ . '/../perfdb/connect.php';

echo "<h1>Email Notification System - Database Setup</h1>\n";
echo "<pre>\n";

try {
    // Create email_notifications table for tracking sent emails
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS email_notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        notification_type ENUM('pending', 'completed', 'cancelled') NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('sent', 'failed', 'pending') DEFAULT 'sent',
        error_message TEXT NULL,
        
        INDEX idx_order_id (order_id),
        INDEX idx_notification_type (notification_type),
        INDEX idx_sent_at (sent_at),
        
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($createTableSQL);
    echo "✓ email_notifications table created successfully\n\n";
    
    // Verify table structure
    echo "Table Structure:\n";
    echo "----------------\n";
    $stmt = $conn->query("DESCRIBE email_notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        printf("%-20s %-30s %-10s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
    }
    
    echo "\n\n✓ Database setup completed successfully!\n";
    echo "\nNext Steps:\n";
    echo "1. Download PHPMailer: Run 'composer require phpmailer/phpmailer' or download manually\n";
    echo "2. Update email/config.php with your SMTP credentials\n";
    echo "3. Set EMAIL_DEV_MODE to false when ready to send real emails\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
