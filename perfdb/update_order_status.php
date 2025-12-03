<?php
// Update order status - Employee API
header('Content-Type: application/json');
session_start();

// Debug: Log session info
error_log('[update_order_status] Session Debug: profile_id=' . (isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : 'NOT SET') . ', role=' . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET'));

// Verify employee session
if (!isset($_SESSION['profile_id']) || $_SESSION['role'] !== 'employee') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => 'Employee access required',
        'debug' => [
            'has_profile_id' => isset($_SESSION['profile_id']),
            'has_role' => isset($_SESSION['role']),
            'role_value' => isset($_SESSION['role']) ? $_SESSION['role'] : null
        ]
    ]);
    exit;
}

try {
    require_once 'connect.php';
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['order_id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing order_id or status']);
        exit;
    }
    
    $order_id = intval($input['order_id']);
    $status = trim($input['status']);
    
    // Validate status value
    $allowed_statuses = ['pending', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid status value']);
        exit;
    }
    
    // Update order status
    $query = "UPDATE orders SET status = :status WHERE order_id = :order_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':status' => $status,
        ':order_id' => $order_id
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'order_id' => $order_id,
            'status' => $status
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
