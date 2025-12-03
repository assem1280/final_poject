<?php
// Delete product
header('Content-Type: application/json');
session_start();

// Verify admin authentication
if (!isset($_SESSION['profile_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied - admin only']);
    exit;
}

require_once 'connect.php';

try {
    // Validate input
    if (!isset($_POST['p_id'])) {
        throw new Exception('Product ID is required');
    }
    
    $p_id = intval($_POST['p_id']);
    
    // Check if product exists
    $check_query = "SELECT p_id FROM products WHERE p_id = :p_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([':p_id' => $p_id]);
    
    if ($check_stmt->rowCount() === 0) {
        throw new Exception('Product not found');
    }
    
    // Delete product
    $query = "DELETE FROM products WHERE p_id = :p_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':p_id' => $p_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
