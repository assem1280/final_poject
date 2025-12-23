<?php
/**
 * Order Notification Service
 * 
 * Handles sending email notifications for order status changes
 * Automatically detects status changes and sends appropriate emails
 */

require_once __DIR__ . '/EmailNotification.php';
require_once __DIR__ . '/EmailTemplates.php';
require_once __DIR__ . '/../perfdb/connect.php';

class OrderNotificationService {
    
    private $emailer;
    private $conn;
    
    /**
     * Constructor
     * 
     * @param PDO $conn Database connection (optional, will use global if not provided)
     */
    public function __construct($conn = null) {
        $this->emailer = new EmailNotification();
        $this->conn = $conn;
        
        // If no connection provided, try to use global
        if ($this->conn === null) {
            global $conn;
            $this->conn = $conn;
        }
    }
    
    /**
     * Get customer information for an order
     * 
     * @param int $orderId The order ID
     * @return array|null Customer data or null if not found
     */
    private function getOrderCustomerInfo($orderId) {
        try {
            $query = "SELECT 
                        o.order_id,
                        o.total_amount,
                        o.status,
                        o.created_at,
                        cp.profile_id,
                        cp.first_name,
                        cp.last_name,
                        cp.email,
                        cp.phone
                      FROM orders o
                      JOIN customer_profiles cp ON o.customer_profile_id = cp.profile_id
                      WHERE o.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':order_id' => $orderId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result['customer_name'] = trim($result['first_name'] . ' ' . $result['last_name']);
                $result['customer_email'] = $result['email'];
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("[OrderNotificationService] Database error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Send notification for a new order (PENDING status)
     * 
     * @param int $orderId The order ID
     * @return bool True if email sent successfully
     */
    public function sendOrderPendingNotification($orderId) {
        $orderInfo = $this->getOrderCustomerInfo($orderId);
        
        if (!$orderInfo) {
            error_log("[OrderNotificationService] Order not found: {$orderId}");
            return false;
        }
        
        if (empty($orderInfo['customer_email'])) {
            error_log("[OrderNotificationService] No email for order: {$orderId}");
            return false;
        }
        
        // Generate email content
        $email = EmailTemplates::orderPending(
            $orderId,
            $orderInfo['customer_name'],
            $orderInfo['total_amount']
        );
        
        // Send email
        $result = $this->emailer->send(
            $orderInfo['customer_email'],
            $orderInfo['customer_name'],
            $email['subject'],
            $email['html'],
            $email['text']
        );
        
        if ($result) {
            $this->logNotification($orderId, 'pending', $orderInfo['customer_email']);
        }
        
        return $result;
    }
    
    /**
     * Send notification when order is completed
     * 
     * @param int $orderId The order ID
     * @return bool True if email sent successfully
     */
    public function sendOrderCompletedNotification($orderId) {
        $orderInfo = $this->getOrderCustomerInfo($orderId);
        
        if (!$orderInfo) {
            error_log("[OrderNotificationService] Order not found: {$orderId}");
            return false;
        }
        
        if (empty($orderInfo['customer_email'])) {
            error_log("[OrderNotificationService] No email for order: {$orderId}");
            return false;
        }
        
        // Generate email content
        $email = EmailTemplates::orderCompleted(
            $orderId,
            $orderInfo['customer_name'],
            $orderInfo['total_amount']
        );
        
        // Send email
        $result = $this->emailer->send(
            $orderInfo['customer_email'],
            $orderInfo['customer_name'],
            $email['subject'],
            $email['html'],
            $email['text']
        );
        
        if ($result) {
            $this->logNotification($orderId, 'completed', $orderInfo['customer_email']);
        }
        
        return $result;
    }
    
    /**
     * Send notification when order is cancelled
     * 
     * @param int $orderId The order ID
     * @param string $reason Cancellation reason (optional)
     * @return bool True if email sent successfully
     */
    public function sendOrderCancelledNotification($orderId, $reason = '') {
        $orderInfo = $this->getOrderCustomerInfo($orderId);
        
        if (!$orderInfo) {
            error_log("[OrderNotificationService] Order not found: {$orderId}");
            return false;
        }
        
        if (empty($orderInfo['customer_email'])) {
            error_log("[OrderNotificationService] No email for order: {$orderId}");
            return false;
        }
        
        // Generate email content
        $email = EmailTemplates::orderCancelled(
            $orderId,
            $orderInfo['customer_name'],
            $reason
        );
        
        // Send email
        $result = $this->emailer->send(
            $orderInfo['customer_email'],
            $orderInfo['customer_name'],
            $email['subject'],
            $email['html'],
            $email['text']
        );
        
        if ($result) {
            $this->logNotification($orderId, 'cancelled', $orderInfo['customer_email']);
        }
        
        return $result;
    }
    
    /**
     * Automatically send notification based on status change
     * 
     * @param int $orderId The order ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @return bool True if notification was sent
     */
    public function handleStatusChange($orderId, $oldStatus, $newStatus) {
        // Normalize status values to lowercase
        $oldStatus = strtolower($oldStatus);
        $newStatus = strtolower($newStatus);
        
        // If status hasn't changed, do nothing
        if ($oldStatus === $newStatus) {
            return false;
        }
        
        error_log("[OrderNotificationService] Status change detected for order {$orderId}: {$oldStatus} -> {$newStatus}");
        
        // Send appropriate notification based on new status
        switch ($newStatus) {
            case 'pending':
                return $this->sendOrderPendingNotification($orderId);
                
            case 'completed':
                return $this->sendOrderCompletedNotification($orderId);
                
            case 'cancelled':
                return $this->sendOrderCancelledNotification($orderId);
                
            default:
                error_log("[OrderNotificationService] Unknown status: {$newStatus}");
                return false;
        }
    }
    
    /**
     * Log notification to database (optional - for tracking)
     */
    private function logNotification($orderId, $status, $email) {
        try {
            // Check if email_notifications table exists
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'email_notifications'");
            if ($checkTable->rowCount() > 0) {
                $query = "INSERT INTO email_notifications (order_id, notification_type, recipient_email, sent_at)
                         VALUES (:order_id, :type, :email, NOW())";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([
                    ':order_id' => $orderId,
                    ':type' => $status,
                    ':email' => $email
                ]);
            }
        } catch (PDOException $e) {
            // Silently fail - logging is optional
            error_log("[OrderNotificationService] Failed to log notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get last error from emailer
     * @return string
     */
    public function getLastError() {
        return $this->emailer->getLastError();
    }
}

/**
 * Helper function to send order notification
 * Can be called from anywhere in the application
 * 
 * @param int $orderId The order ID
 * @param string $status The order status (pending, completed, cancelled)
 * @param PDO $conn Database connection (optional)
 * @return bool
 */
function sendOrderNotification($orderId, $status, $conn = null) {
    $service = new OrderNotificationService($conn);
    
    switch (strtolower($status)) {
        case 'pending':
            return $service->sendOrderPendingNotification($orderId);
        case 'completed':
            return $service->sendOrderCompletedNotification($orderId);
        case 'cancelled':
            return $service->sendOrderCancelledNotification($orderId);
        default:
            return false;
    }
}
?>
