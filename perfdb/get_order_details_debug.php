<?php
// Debug version - temporarily remove auth check for testing
header('Content-Type: application/json');

try {
    // Include database connection
    include 'connect.php';
    
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }
    
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 1;
    
    // Get order items (regular products)
    $query_items = "SELECT oi.*, p.p_name, p.price FROM order_items oi
                    LEFT JOIN products p ON oi.p_id = p.p_id
                    WHERE oi.order_id = :order_id";
    
    $stmt = $conn->prepare($query_items);
    $stmt->execute([':order_id' => $order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get custom perfumes with type details
    $query_custom = "SELECT cp.custom_id, cp.order_id, cp.bottle_design_id, 
                            cp.oil_amount_grams, cp.custom_price,
                            GROUP_CONCAT(CONCAT(pt.type_name, '|', cpt.amount_percent) SEPARATOR ',') as types_detail
                     FROM custom_perfumes cp
                     LEFT JOIN custom_perfume_types cpt ON cp.custom_id = cpt.custom_id
                     LEFT JOIN perfume_types pt ON cpt.type_id = pt.type_id
                     WHERE cp.order_id = :order_id
                     GROUP BY cp.custom_id";
    
    $stmt = $conn->prepare($query_custom);
    $stmt->execute([':order_id' => $order_id]);
    $custom = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'items' => $items,
        'custom' => $custom,
        'success' => true,
        'order_id' => $order_id
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
