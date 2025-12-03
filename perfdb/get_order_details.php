<?php
header('Content-Type: application/json');
session_start();

// Verify order_id is provided
if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order_id parameter']);
    exit;
}

$order_id = intval($_GET['order_id']);

// Check if user is authenticated as employee (if session exists)
// Allow if: user is logged in AND user is employee, OR if no session validation needed yet
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'employee') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - employee access required']);
    exit;
}

try {
    // Include database connection
    include 'connect.php';
    
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }
    
    // Get order items (regular products)
    // Use the price saved in order_items (oi.price) rather than product current price
    $query_items = "SELECT oi.*, p.p_name, oi.price AS price, oi.volume_ml FROM order_items oi
                    LEFT JOIN products p ON oi.p_id = p.p_id
                    WHERE oi.order_id = :order_id";
    
    $stmt = $conn->prepare($query_items);
    $stmt->execute([':order_id' => $order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get custom perfumes with product details
    // Map bottle_design_id to bottle sizes (100ml=3, 50ml=1, 30ml=2, 20ml=? from create-script.js)
    $query_custom = "SELECT cp.custom_id, cp.order_id, cp.bottle_design_id, 
                            cp.oil_amount_grams, cp.custom_price,
                            GROUP_CONCAT(CONCAT(pt.type_name, ' (', cpt.amount_percent, '%)') SEPARATOR ', ') as types_detail,
                            CASE 
                                WHEN cp.bottle_design_id = 3 THEN 100
                                WHEN cp.bottle_design_id = 1 THEN 50
                                WHEN cp.bottle_design_id = 2 THEN 30
                                ELSE 50
                            END as bottle_size_ml
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
        'success' => true
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
