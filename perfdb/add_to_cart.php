<?php
// Add product to cart
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$product_id = isset($input['product_id']) ? intval($input['product_id']) : 0;
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

// Check if user is logged in
$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

if ($profile_id) {
    // User is logged in - save to database (cart_items table)
    $check_query = "SELECT * FROM cart_items WHERE customer_profile_id = $profile_id AND product_id = $product_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update quantity
        $update_query = "UPDATE cart_items SET quantity = quantity + $quantity WHERE customer_profile_id = $profile_id AND product_id = $product_id";
        $result = mysqli_query($conn, $update_query);
    } else {
        // Insert new item
        $insert_query = "INSERT INTO cart_items (customer_profile_id, product_id, quantity) VALUES ($profile_id, $product_id, $quantity)";
        $result = mysqli_query($conn, $insert_query);
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product added to cart']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add to cart: ' . mysqli_error($conn)]);
    }
} else {
    // User not logged in - use session cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    echo json_encode(['success' => true, 'message' => 'Product added to cart (session)']);
}

mysqli_close($conn);
?>
