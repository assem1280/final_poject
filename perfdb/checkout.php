<?php
// Checkout - Create order from cart
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$payment_method = isset($input['payment_method']) ? mysqli_real_escape_string($conn, $input['payment_method']) : 'cash';
$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

if (!$profile_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Calculate total from cart
    $total_query = "SELECT SUM(p.price * ci.quantity) as cart_total
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.p_id
                    WHERE ci.customer_profile_id = $profile_id";
    
    $total_result = mysqli_query($conn, $total_query);
    $total_row = mysqli_fetch_assoc($total_result);
    $cart_total = $total_row['cart_total'] ? $total_row['cart_total'] : 0;
    
    // Add custom perfumes total
    $custom_total_query = "SELECT SUM(custom_price) as custom_total
                          FROM custom_cart_items
                          WHERE customer_profile_id = $profile_id";
    
    $custom_result = mysqli_query($conn, $custom_total_query);
    $custom_row = mysqli_fetch_assoc($custom_result);
    $custom_total = $custom_row['custom_total'] ? $custom_row['custom_total'] : 0;
    
    $total_amount = $cart_total + $custom_total;
    
    if ($total_amount <= 0) {
        throw new Exception('Cart is empty');
    }
    
    // Create order
    $order_query = "INSERT INTO orders (customer_profile_id, total_amount, payment_method, status) 
                    VALUES ($profile_id, $total_amount, '$payment_method', 'pending')";
    
    if (!mysqli_query($conn, $order_query)) {
        throw new Exception('Failed to create order');
    }
    
    $order_id = mysqli_insert_id($conn);
    
    // Move cart items to order_items
    $items_query = "SELECT product_id, quantity, (SELECT price FROM products WHERE p_id = product_id) as price
                    FROM cart_items
                    WHERE customer_profile_id = $profile_id";
    
    $items_result = mysqli_query($conn, $items_query);
    
    while ($item = mysqli_fetch_assoc($items_result)) {
        $p_id = $item['product_id'];
        $qty = $item['quantity'];
        $price = $item['price'];
        
        $order_item_query = "INSERT INTO order_items (order_id, p_id, quantity, price) 
                            VALUES ($order_id, $p_id, $qty, $price)";
        mysqli_query($conn, $order_item_query);
    }
    
    // Move custom perfumes to custom_perfumes table
    $custom_query = "SELECT custom_cart_id, bottle_design_id, oil_amount_grams, custom_price
                     FROM custom_cart_items
                     WHERE customer_profile_id = $profile_id";
    
    $custom_items = mysqli_query($conn, $custom_query);
    
    while ($custom = mysqli_fetch_assoc($custom_items)) {
        $custom_cart_id = $custom['custom_cart_id'];
        $bottle_id = $custom['bottle_design_id'];
        $oil_grams = $custom['oil_amount_grams'];
        $custom_price = $custom['custom_price'];
        
        // Insert custom perfume
        $custom_perfume_query = "INSERT INTO custom_perfumes (order_id, customer_profile_id, bottle_design_id, oil_amount_grams, custom_price) 
                                VALUES ($order_id, $profile_id, $bottle_id, $oil_grams, $custom_price)";
        mysqli_query($conn, $custom_perfume_query);
        
        $custom_id = mysqli_insert_id($conn);
        
        // Copy types from cart to order
        $types_query = "INSERT INTO custom_perfume_types (custom_id, type_id, amount_percent)
                       SELECT $custom_id, type_id, amount_percent
                       FROM custom_cart_types
                       WHERE custom_cart_id = $custom_cart_id";
        mysqli_query($conn, $types_query);
    }
    
    // Clear cart
    mysqli_query($conn, "DELETE FROM cart_items WHERE customer_profile_id = $profile_id");
    mysqli_query($conn, "DELETE FROM custom_cart_items WHERE customer_profile_id = $profile_id");
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'total_amount' => $total_amount
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);
?>
