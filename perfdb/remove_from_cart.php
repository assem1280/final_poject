<?php
// Remove item from cart
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$cart_item_id = isset($input['cart_item_id']) ? intval($input['cart_item_id']) : 0;
$custom_cart_id = isset($input['custom_cart_id']) ? intval($input['custom_cart_id']) : 0;

$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

if (!$profile_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

if ($cart_item_id > 0) {
    // Remove regular product from cart
    $delete_query = "DELETE FROM cart_items 
                     WHERE cart_item_id = $cart_item_id 
                     AND customer_profile_id = $profile_id";
    
    if (mysqli_query($conn, $delete_query)) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove item: ' . mysqli_error($conn)]);
    }
} elseif ($custom_cart_id > 0) {
    // Remove custom perfume from cart (types will be deleted automatically by CASCADE)
    $delete_query = "DELETE FROM custom_cart_items 
                     WHERE custom_cart_id = $custom_cart_id 
                     AND customer_profile_id = $profile_id";
    
    if (mysqli_query($conn, $delete_query)) {
        echo json_encode(['success' => true, 'message' => 'Custom perfume removed from cart']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove custom perfume: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
}

mysqli_close($conn);
?>
