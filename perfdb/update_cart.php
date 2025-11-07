<?php
// Update cart item quantity
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$cart_item_id = isset($input['cart_item_id']) ? intval($input['cart_item_id']) : 0;
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;

if ($cart_item_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

if (!$profile_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Update quantity
$update_query = "UPDATE cart_items 
                 SET quantity = $quantity 
                 WHERE cart_item_id = $cart_item_id 
                 AND customer_profile_id = $profile_id";

if (mysqli_query($conn, $update_query)) {
    echo json_encode(['success' => true, 'message' => 'Quantity updated']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update quantity: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
