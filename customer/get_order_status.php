<?php
// Get order status for customer
header('Content-Type: application/json');
session_start();

// Verify customer session
if (!isset($_SESSION['profile_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Customer access required']);
    exit;
}

try {
    require_once '../perfdb/connect.php';
    
    if (!isset($_GET['order_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing order_id parameter']);
        exit;
    }
    
    $order_id = intval($_GET['order_id']);
    $customer_id = $_SESSION['profile_id'];
    
    // Get order status - verify it belongs to the current customer
    $query = "SELECT status FROM orders WHERE order_id = :order_id AND customer_profile_id = :customer_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':order_id' => $order_id,
        ':customer_id' => $customer_id
    ]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'status' => $order['status']
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
