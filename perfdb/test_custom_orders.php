<?php
// Test API to debug custom perfume data
header('Content-Type: application/json');
include 'connect.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 1;

try {
    // Get raw custom perfumes
    $query = "SELECT cp.custom_id, cp.order_id, cp.bottle_design_id, 
                     cp.oil_amount_grams, cp.custom_price
              FROM custom_perfumes cp
              WHERE cp.order_id = :order_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':order_id' => $order_id]);
    $customs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'order_id' => $order_id,
        'custom_perfumes_found' => count($customs),
        'custom_perfumes' => $customs,
        'test_queries' => []
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    // For each custom, get its types
    foreach ($customs as $custom) {
        $type_query = "SELECT cpt.type_id, cpt.amount_percent FROM custom_perfume_types cpt WHERE cpt.custom_id = :custom_id";
        $type_stmt = $conn->prepare($type_query);
        $type_stmt->execute([':custom_id' => $custom['custom_id']]);
        $types = $type_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n\n=== CUSTOM ID: {$custom['custom_id']} ===\n";
        echo "Types found: " . count($types) . "\n";
        foreach ($types as $t) {
            echo "  - Type ID: {$t['type_id']}, Percent: {$t['amount_percent']}\n";
            
            // Try to get type name
            $name_query = "SELECT type_name FROM perfume_types WHERE type_id = :type_id";
            $name_stmt = $conn->prepare($name_query);
            $name_stmt->execute([':type_id' => (int)$t['type_id']]);
            $name_result = $name_stmt->fetch(PDO::FETCH_ASSOC);
            echo "    Type Name: " . ($name_result ? $name_result['type_name'] : 'NOT FOUND') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
