<?php
/**
 * Email Templates for Order Notifications
 * 
 * Contains HTML templates for order-related emails
 */

require_once __DIR__ . '/config.php';

class EmailTemplates {
    
    /**
     * Get the base HTML wrapper for all emails
     * 
     * @param string $content The main content to wrap
     * @return string Complete HTML email
     */
    private static function getBaseTemplate($content) {
        $storeName = STORE_NAME;
        $storeUrl = STORE_URL;
        $year = date('Y');
        
        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$storeName}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .email-header .logo {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .email-body {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .message-box {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 0 8px 8px 0;
            margin: 25px 0;
        }
        .message-box.success {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-left-color: #4caf50;
        }
        .message-box h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        .message-box p {
            margin: 0;
            color: #555;
        }
        .order-details {
            background: #fafafa;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .order-details h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 16px;
        }
        .order-details p {
            margin: 8px 0;
            color: #555;
        }
        .order-details .order-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        .email-footer p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
        }
        .email-footer a {
            color: #667eea;
            text-decoration: none;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='email-header'>
            <div class='logo'>🌹</div>
            <h1>{$storeName}</h1>
        </div>
        <div class='email-body'>
            {$content}
        </div>
        <div class='email-footer'>
            <p>Thank you for choosing {$storeName}!</p>
            <p><a href='{$storeUrl}'>Visit Our Store</a></p>
            <p style='margin-top: 15px; font-size: 11px; color: #999;'>
                © {$year} {$storeName}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>";
    }
    
    /**
     * Generate Order Pending (In Preparation) Email
     * 
     * @param int $orderId The order ID
     * @param string $customerName Customer's name
     * @param float $totalAmount Order total (optional)
     * @return array ['subject' => string, 'html' => string, 'text' => string]
     */
    public static function orderPending($orderId, $customerName, $totalAmount = null) {
        $subject = "Order #{$orderId} – In Preparation";
        
        $totalHtml = '';
        if ($totalAmount !== null) {
            $formattedTotal = number_format($totalAmount, 2);
            $totalHtml = "<p><strong>Order Total:</strong> \${$formattedTotal}</p>";
        }
        
        $content = "
            <p class='greeting'>Hello <strong>{$customerName}</strong>,</p>
            
            <p>Thank you for your order.</p>
            
            <div class='message-box'>
                <h2>📦 YOUR ORDER IS BEING PREPARED</h2>
                <p>We'll notify you when it's ready.</p>
            </div>
            
            <div class='order-details'>
                <h3>Order Information</h3>
                <p>Order Number: <span class='order-number'>#{$orderId}</span></p>
                {$totalHtml}
                <p><strong>Status:</strong> In Preparation</p>
            </div>
            
            <p>If you have any questions about your order, please don't hesitate to contact us.</p>
            
            <p style='margin-top: 30px;'>Best regards,<br><strong>" . STORE_NAME . "</strong></p>";
        
        $html = self::getBaseTemplate($content);
        
        $text = "Hello {$customerName},\n\n"
              . "Thank you for your order.\n\n"
              . "YOUR ORDER IS BEING PREPARED. WE'LL NOTIFY YOU WHEN IT'S READY.\n\n"
              . "Order Number: #{$orderId}\n"
              . ($totalAmount !== null ? "Order Total: \$" . number_format($totalAmount, 2) . "\n" : "")
              . "\nBest regards,\n" . STORE_NAME;
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Generate Order Completed Email
     * 
     * @param int $orderId The order ID
     * @param string $customerName Customer's name
     * @param float $totalAmount Order total (optional)
     * @return array ['subject' => string, 'html' => string, 'text' => string]
     */
    public static function orderCompleted($orderId, $customerName, $totalAmount = null) {
        $subject = "Order #{$orderId} – Completed";
        
        $totalHtml = '';
        if ($totalAmount !== null) {
            $formattedTotal = number_format($totalAmount, 2);
            $totalHtml = "<p><strong>Order Total:</strong> \${$formattedTotal}</p>";
        }
        
        $content = "
            <p class='greeting'>Hello <strong>{$customerName}</strong>,</p>
            
            <p>Great news! Your order has been completed successfully.</p>
            
            <div class='message-box success'>
                <h2>✅ ORDER COMPLETED</h2>
                <p>PLEASE WAIT FOR OUR RESPONSE WITHIN TWO DAYS TO ARRANGE PICKUP OR DELIVERY.</p>
            </div>
            
            <div class='order-details'>
                <h3>Order Information</h3>
                <p>Order Number: <span class='order-number'>#{$orderId}</span></p>
                {$totalHtml}
                <p><strong>Status:</strong> Completed ✓</p>
            </div>
            
            <p>We will contact you shortly to arrange the pickup or delivery of your order.</p>
            
            <p style='margin-top: 30px;'>Thank you for shopping with us,<br><strong>" . STORE_NAME . "</strong></p>";
        
        $html = self::getBaseTemplate($content);
        
        $text = "Hello {$customerName},\n\n"
              . "Your order has been completed successfully.\n\n"
              . "PLEASE WAIT FOR OUR RESPONSE WITHIN TWO DAYS TO ARRANGE PICKUP OR DELIVERY.\n\n"
              . "Order Number: #{$orderId}\n"
              . ($totalAmount !== null ? "Order Total: \$" . number_format($totalAmount, 2) . "\n" : "")
              . "\nThank you for shopping with us,\n" . STORE_NAME;
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Generate Order Cancelled Email
     * 
     * @param int $orderId The order ID
     * @param string $customerName Customer's name
     * @param string $reason Cancellation reason (optional)
     * @return array ['subject' => string, 'html' => string, 'text' => string]
     */
    public static function orderCancelled($orderId, $customerName, $reason = '') {
        $subject = "Order #{$orderId} – Cancelled";
        
        $reasonHtml = '';
        if (!empty($reason)) {
            $reasonHtml = "<p><strong>Reason:</strong> {$reason}</p>";
        }
        
        $content = "
            <p class='greeting'>Hello <strong>{$customerName}</strong>,</p>
            
            <p>We regret to inform you that your order has been cancelled.</p>
            
            <div class='message-box' style='background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); border-left-color: #f44336;'>
                <h2>❌ ORDER CANCELLED</h2>
                <p>Your order has been cancelled.</p>
                {$reasonHtml}
            </div>
            
            <div class='order-details'>
                <h3>Order Information</h3>
                <p>Order Number: <span class='order-number'>#{$orderId}</span></p>
                <p><strong>Status:</strong> Cancelled</p>
            </div>
            
            <p>If you have any questions or believe this was done in error, please contact us immediately.</p>
            
            <p style='margin-top: 30px;'>Best regards,<br><strong>" . STORE_NAME . "</strong></p>";
        
        $html = self::getBaseTemplate($content);
        
        $text = "Hello {$customerName},\n\n"
              . "We regret to inform you that your order has been cancelled.\n\n"
              . "Order Number: #{$orderId}\n"
              . (!empty($reason) ? "Reason: {$reason}\n" : "")
              . "\nIf you have any questions, please contact us.\n\n"
              . "Best regards,\n" . STORE_NAME;
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Generate Welcome Email for new signups
     * 
     * @param string $customerName Customer's full name
     * @param string $email Customer's email
     * @return array ['subject' => string, 'html' => string, 'text' => string]
     */
    public static function welcomeEmail($customerName, $email) {
        $subject = "Welcome to " . STORE_NAME . "! 🌹";
        
        $storeUrl = STORE_URL;
        
        $content = "
            <p class='greeting'>Hello <strong>{$customerName}</strong>,</p>
            
            <p>Welcome to " . STORE_NAME . "! We're thrilled to have you join our community of fragrance enthusiasts.</p>
            
            <div class='message-box'>
                <h2>🎉 ACCOUNT CREATED SUCCESSFULLY</h2>
                <p>Your account has been created and you're ready to explore our exclusive collection of perfumes.</p>
            </div>
            
            <div class='order-details'>
                <h3>Your Account Details</h3>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Account Status:</strong> Active ✓</p>
            </div>
            
            <p>Here's what you can do now:</p>
            <ul style='line-height: 2;'>
                <li>🛍️ Browse our exclusive perfume collection</li>
                <li>💖 Create your own custom fragrance</li>
                <li>📦 Track your orders in real-time</li>
                <li>🎁 Enjoy member-only offers</li>
            </ul>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$storeUrl}' class='btn' style='display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 25px; font-weight: bold;'>Start Shopping</a>
            </div>
            
            <p style='margin-top: 30px;'>Best regards,<br><strong>" . STORE_NAME . " Team</strong></p>";
        
        $html = self::getBaseTemplate($content);
        
        $text = "Hello {$customerName},\n\n"
              . "Welcome to " . STORE_NAME . "!\n\n"
              . "Your account has been created successfully.\n\n"
              . "Email: {$email}\n"
              . "Account Status: Active\n\n"
              . "Visit us at: " . STORE_URL . "\n\n"
              . "Best regards,\n" . STORE_NAME . " Team";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
}
?>
