<?php
// Checkout - Create order from cart
header('Content-Type: application/json');
session_start();
require_once 'connect.php';
require_once __DIR__ . '/../email/OrderNotificationService.php';

$input = json_decode(file_get_contents('php://input'), true);

$payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : 'cash';
$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

if (!$profile_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in', 'redirect' => '/pefumeppp/indexed/login.html']);
    exit;
}

try {
    // Start transaction using PDO
    $conn->beginTransaction();
    
    // First check if cart has any items (regular or custom)
    $check_query = "SELECT 
                    (SELECT COUNT(*) FROM cart_items WHERE customer_profile_id = :profile_id1) as regular_count,
                    (SELECT COUNT(*) FROM custom_cart_items WHERE customer_profile_id = :profile_id2) as custom_count";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([':profile_id1' => $profile_id, ':profile_id2' => $profile_id]);
    $check_row = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_items = ($check_row['regular_count'] ?? 0) + ($check_row['custom_count'] ?? 0);
    
    if ($total_items == 0) {
        throw new Exception('Cart is empty');
    }
    
    // Calculate total from cart - account for volume pricing
    // Note: volume_ml defaults to 50 if NULL, this ensures backward compatibility
    $total_query = "SELECT SUM(
                        p.price * ci.quantity + 
                        (CASE WHEN COALESCE(ci.volume_ml, 50) = 100 THEN 50.00 * ci.quantity ELSE 0 END)
                    ) as cart_total
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.p_id
                    WHERE ci.customer_profile_id = :profile_id";
    
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->execute([':profile_id' => $profile_id]);
    $total_row = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $cart_total = $total_row['cart_total'] ? $total_row['cart_total'] : 0;
    
    // Add custom perfumes total
    $custom_total_query = "SELECT SUM(custom_price) as custom_total
                          FROM custom_cart_items
                          WHERE customer_profile_id = :profile_id";
    
    $custom_stmt = $conn->prepare($custom_total_query);
    $custom_stmt->execute([':profile_id' => $profile_id]);
    $custom_row = $custom_stmt->fetch(PDO::FETCH_ASSOC);
    $custom_total = $custom_row['custom_total'] ? $custom_row['custom_total'] : 0;
    
    $total_amount = $cart_total + $custom_total;
    
    if ($total_amount <= 0) {
        throw new Exception('Cart is empty');
    }
    
    // Create order
    $order_query = "INSERT INTO orders (customer_profile_id, total_amount, payment_method, status) 
                    VALUES (:profile_id, :total_amount, :payment_method, 'pending')";
    
    $order_stmt = $conn->prepare($order_query);
    try {
        error_log("[checkout] Executing order insert with params: profile_id={$profile_id}, total_amount={$total_amount}, payment_method={$payment_method}");
        $order_stmt->execute([
            ':profile_id' => $profile_id,
            ':total_amount' => $total_amount,
            ':payment_method' => $payment_method
        ]);
    } catch (PDOException $e) {
        error_log('[checkout] order_stmt execute failed: ' . $e->getMessage());
        throw $e;
    }
    
    $order_id = $conn->lastInsertId();
    
    // Move cart items to order_items
    $items_query = "SELECT ci.product_id, ci.quantity, p.price, COALESCE(ci.volume_ml, 50) as volume_ml
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.p_id
                    WHERE ci.customer_profile_id = :profile_id";
    
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->execute([':profile_id' => $profile_id]);
    $items_result = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items_result as $item) {
        $p_id = $item['product_id'];
        $qty = $item['quantity'];
        $price = $item['price'];
        $volume_ml = $item['volume_ml'];
        
        // Adjust price for 100ml bottles (+$50)
        $adjusted_price = $price;
        if ($volume_ml === 100) {
            $adjusted_price = $price + 50.00;
        }
        
        $order_item_query = "INSERT INTO order_items (order_id, p_id, quantity, price, volume_ml) 
                            VALUES (:order_id, :p_id, :quantity, :price, :volume_ml)";
        $order_item_stmt = $conn->prepare($order_item_query);
        try {
            error_log("[checkout] Inserting order_item for order_id={$order_id}, p_id={$p_id}, qty={$qty}, price={$adjusted_price}, volume_ml={$volume_ml}");
            $order_item_stmt->execute([
                ':order_id' => $order_id,
                ':p_id' => $p_id,
                ':quantity' => $qty,
                ':price' => $adjusted_price,
                ':volume_ml' => $volume_ml
            ]);
        } catch (PDOException $e) {
            error_log('[checkout] order_item execute failed: ' . $e->getMessage());
            throw $e;
        }
        
        // Decrease product stock after creating order item
        $update_stock_query = "UPDATE products SET stock = stock - :qty WHERE p_id = :p_id";
        $update_stock_stmt = $conn->prepare($update_stock_query);
        try {
            error_log("[checkout] Decreasing stock for p_id={$p_id} by qty={$qty}");
            $update_stock_stmt->execute([
                ':qty' => $qty,
                ':p_id' => $p_id
            ]);
        } catch (PDOException $e) {
            error_log('[checkout] update_stock execute failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    // Move custom perfumes to custom_perfumes table
    $custom_query = "SELECT custom_cart_id, bottle_design_id, oil_amount_grams, custom_price
                     FROM custom_cart_items
                     WHERE customer_profile_id = :profile_id";
    
    $custom_stmt = $conn->prepare($custom_query);
    $custom_stmt->execute([':profile_id' => $profile_id]);
    $custom_items = $custom_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($custom_items as $custom) {
        $custom_cart_id = $custom['custom_cart_id'];
        $bottle_id = $custom['bottle_design_id'];
        $oil_grams = $custom['oil_amount_grams'];
        $custom_price = $custom['custom_price'];
        
        // Insert custom perfume
        $custom_perfume_query = "INSERT INTO custom_perfumes (order_id, customer_profile_id, bottle_design_id, oil_amount_grams, custom_price) 
                                VALUES (:order_id, :profile_id, :bottle_id, :oil_grams, :custom_price)";
        $custom_perfume_stmt = $conn->prepare($custom_perfume_query);
        try {
            error_log("[checkout] Inserting custom_perfume order_id={$order_id}, profile_id={$profile_id}, bottle_id={$bottle_id}, oil_grams={$oil_grams}, custom_price={$custom_price}");
            $custom_perfume_stmt->execute([
                ':order_id' => $order_id,
                ':profile_id' => $profile_id,
                ':bottle_id' => $bottle_id,
                ':oil_grams' => $oil_grams,
                ':custom_price' => $custom_price
            ]);
        } catch (PDOException $e) {
            error_log('[checkout] custom_perfume execute failed: ' . $e->getMessage());
            throw $e;
        }
        
        $custom_id = $conn->lastInsertId();
        
        // Copy types from cart to order
        $types_query = "INSERT INTO custom_perfume_types (custom_id, type_id, amount_percent)
                       SELECT ?, type_id, amount_percent
                       FROM custom_cart_types
                       WHERE custom_cart_id = ?";
        $types_stmt = $conn->prepare($types_query);
        try {
            error_log("[checkout] Copying custom types: custom_id={$custom_id}, custom_cart_id={$custom_cart_id}");
            $types_stmt->execute([
                $custom_id,
                $custom_cart_id
            ]);
        } catch (PDOException $e) {
            error_log('[checkout] types_stmt execute failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    // Clear cart
    $clear_cart_query = "DELETE FROM cart_items WHERE customer_profile_id = :profile_id";
    $clear_cart_stmt = $conn->prepare($clear_cart_query);
    $clear_cart_stmt->execute([':profile_id' => $profile_id]);
    
    $clear_custom_query = "DELETE FROM custom_cart_items WHERE customer_profile_id = :profile_id";
    $clear_custom_stmt = $conn->prepare($clear_custom_query);
    $clear_custom_stmt->execute([':profile_id' => $profile_id]);
    
    // Commit transaction
    $conn->commit();
    
    // Send order confirmation email (PENDING status)
    try {
        $emailSent = sendOrderNotification($order_id, 'pending', $conn);
        error_log("[checkout] Order email notification sent: " . ($emailSent ? 'YES' : 'NO'));
    } catch (Exception $emailError) {
        // Don't fail the order if email fails, just log it
        error_log("[checkout] Email notification error: " . $emailError->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'total_amount' => $total_amount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Log concise error for server logs
    error_log('[checkout] Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    // Return minimal error response
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

