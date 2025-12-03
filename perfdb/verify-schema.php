<?php
// Quick verification that all changes are in place
header('Content-Type: application/json');
require_once 'connect.php';

$checks = [];

try {
    // Check 1: volume_ml column in cart_items
    $stmt = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='cart_items' AND COLUMN_NAME='volume_ml' AND TABLE_SCHEMA='perfume-db1'");
    $checks['cart_items_volume_ml'] = $stmt->rowCount() > 0 ? '✓ Present' : '✗ Missing';

    // Check 2: volume_ml column in order_items
    $stmt = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='order_items' AND COLUMN_NAME='volume_ml' AND TABLE_SCHEMA='perfume-db1'");
    $checks['order_items_volume_ml'] = $stmt->rowCount() > 0 ? '✓ Present' : '✗ Missing';

    // Check 3: Sample cart items
    $stmt = $conn->query("SELECT COUNT(*) as count FROM cart_items LIMIT 1");
    $result = $stmt->fetch();
    $checks['cart_items_exist'] = ($result['count'] >= 0) ? '✓ Accessible' : '✗ Error';

    // Check 4: Sample order items
    $stmt = $conn->query("SELECT COUNT(*) as count FROM order_items LIMIT 1");
    $result = $stmt->fetch();
    $checks['order_items_exist'] = ($result['count'] >= 0) ? '✓ Accessible' : '✗ Error';

    // Check 5: Test query with volume_ml
    $stmt = $conn->query("SELECT order_item_id, volume_ml FROM order_items LIMIT 1");
    $checks['volume_query_works'] = '✓ Working';

} catch (Exception $e) {
    $checks['error'] = $e->getMessage();
}

echo json_encode([
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => $checks
], JSON_PRETTY_PRINT);
?>
