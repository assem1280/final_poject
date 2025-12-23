<?php
/**
 * DEVELOPMENT ONLY - Reset All Orders
 * 
 * This script completely resets all order-related data for testing purposes.
 * It will:
 * - Delete all records from orders, order_items, custom_perfumes, custom_perfume_types
 * - Reset auto-increment IDs to 1
 * 
 * WARNING: This is destructive and should NEVER be used in production!
 */

header('Content-Type: application/json');
session_start();

// SECURITY: Only allow in development environment
// Uncomment the following line to disable this feature in production:
// die(json_encode(['success' => false, 'error' => 'This feature is disabled in production']));

// SECURITY: Only allow admin users
if (!isset($_SESSION['profile_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required.']);
    exit;
}

// Only allow POST requests for safety
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST request required']);
    exit;
}

// Require confirmation token
$input = json_decode(file_get_contents('php://input'), true);
$confirm = isset($input['confirm']) ? $input['confirm'] : '';

if ($confirm !== 'RESET_ALL_ORDERS') {
    echo json_encode([
        'success' => false, 
        'error' => 'Confirmation required. Send {"confirm": "RESET_ALL_ORDERS"} to proceed.'
    ]);
    exit;
}

require_once 'connect.php';

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Get counts before deletion for reporting
    $counts_before = [];
    
    $tables = ['custom_perfume_types', 'custom_perfumes', 'order_items', 'orders'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM `{$table}`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $counts_before[$table] = $result['count'];
    }
    
    // Disable foreign key checks temporarily for clean deletion
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Truncate tables in order (child tables first, then parent tables)
    // TRUNCATE is faster than DELETE and automatically resets auto-increment
    
    // 1. custom_perfume_types (depends on custom_perfumes)
    $conn->exec("TRUNCATE TABLE `custom_perfume_types`");
    
    // 2. custom_perfumes (depends on orders)
    $conn->exec("TRUNCATE TABLE `custom_perfumes`");
    
    // 3. order_items (depends on orders)
    $conn->exec("TRUNCATE TABLE `order_items`");
    
    // 4. orders (main table)
    $conn->exec("TRUNCATE TABLE `orders`");
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Commit transaction
    $conn->commit();
    
    // Log this action
    error_log("[DEV RESET] All orders reset by admin (profile_id: {$_SESSION['profile_id']}) at " . date('Y-m-d H:i:s'));
    
    echo json_encode([
        'success' => true,
        'message' => 'All order data has been reset successfully',
        'deleted_records' => $counts_before,
        'timestamp' => date('Y-m-d H:i:s'),
        'performed_by' => $_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? '')
    ]);
    
} catch (PDOException $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("[DEV RESET ERROR] " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
