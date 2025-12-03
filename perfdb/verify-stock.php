<?php
// Verification script to check stock deduction logic
header('Content-Type: application/json');
require_once 'connect.php';

try {
    // Get current stock for products
    $query = "SELECT p_id, p_name, stock FROM products ORDER BY p_id LIMIT 20";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent orders to show stock deduction happened
    $order_query = "SELECT o.order_id, o.customer_profile_id, o.total_amount, o.status, o.created_at,
                           SUM(oi.quantity) as items_count
                    FROM orders o
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    GROUP BY o.order_id
                    ORDER BY o.created_at DESC
                    LIMIT 10";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->execute();
    $orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent order items to show what was purchased
    $items_query = "SELECT oi.order_id, oi.p_id, p.p_name, oi.quantity, oi.volume_ml, oi.price, oi.created_at
                    FROM order_items oi
                    JOIN products p ON oi.p_id = p.p_id
                    ORDER BY oi.created_at DESC
                    LIMIT 20";
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->execute();
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'verification' => [
            'current_stock' => $products,
            'recent_orders' => $orders,
            'recent_order_items' => $items,
            'stock_deduction_logic' => 'Implemented in checkout.php: UPDATE products SET stock = stock - :qty WHERE p_id = :p_id',
            'verification_timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
