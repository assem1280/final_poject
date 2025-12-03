<?php
// Test script to verify cart functionality
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

// Simulate a logged-in user (for testing)
$_SESSION['profile_id'] = 1; // Test user ID
$_SESSION['email'] = 'test@example.com';

$test_results = [];

// Test 1: Check cart_items table structure
$test_results['test_1_table_structure'] = [];
try {
    $result = $conn->query("DESCRIBE cart_items");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $test_results['test_1_table_structure']['has_volume_ml'] = in_array('volume_ml', $columns);
    $test_results['test_1_table_structure']['all_columns'] = $columns;
    $test_results['test_1_table_structure']['status'] = 'PASS';
} catch (Exception $e) {
    $test_results['test_1_table_structure']['error'] = $e->getMessage();
    $test_results['test_1_table_structure']['status'] = 'FAIL';
}

// Test 2: Check if we can fetch cart with volume calculation
$test_results['test_2_fetch_cart'] = [];
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE customer_profile_id = :profile_id");
    $stmt->execute([':profile_id' => $_SESSION['profile_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $test_results['test_2_fetch_cart']['item_count'] = $result['count'];
    
    if ($result['count'] > 0) {
        // Fetch items with volume calculation
        $stmt2 = $conn->prepare("SELECT ci.*, COALESCE(ci.volume_ml, 50) as actual_volume FROM cart_items ci WHERE customer_profile_id = :profile_id");
        $stmt2->execute([':profile_id' => $_SESSION['profile_id']]);
        $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $test_results['test_2_fetch_cart']['items'] = $items;
    }
    $test_results['test_2_fetch_cart']['status'] = 'PASS';
} catch (Exception $e) {
    $test_results['test_2_fetch_cart']['error'] = $e->getMessage();
    $test_results['test_2_fetch_cart']['status'] = 'FAIL';
}

// Test 3: Test total calculation with COALESCE
$test_results['test_3_total_calculation'] = [];
try {
    $total_query = "SELECT SUM(
                        p.price * ci.quantity + 
                        (CASE WHEN COALESCE(ci.volume_ml, 50) = 100 THEN 50.00 * ci.quantity ELSE 0 END)
                    ) as cart_total
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.p_id
                    WHERE ci.customer_profile_id = :profile_id";
    
    $stmt = $conn->prepare($total_query);
    $stmt->execute([':profile_id' => $_SESSION['profile_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $test_results['test_3_total_calculation']['total'] = $result['cart_total'];
    $test_results['test_3_total_calculation']['status'] = 'PASS';
} catch (Exception $e) {
    $test_results['test_3_total_calculation']['error'] = $e->getMessage();
    $test_results['test_3_total_calculation']['status'] = 'FAIL';
}

// Test 4: Check if checkout would work
$test_results['test_4_checkout_readiness'] = [];
try {
    // Check if cart has items
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE customer_profile_id = :profile_id");
    $stmt->execute([':profile_id' => $_SESSION['profile_id']]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($count['count'] == 0) {
        $test_results['test_4_checkout_readiness']['ready'] = false;
        $test_results['test_4_checkout_readiness']['reason'] = 'Cart is empty';
    } else {
        $test_results['test_4_checkout_readiness']['ready'] = true;
        $test_results['test_4_checkout_readiness']['item_count'] = $count['count'];
    }
    $test_results['test_4_checkout_readiness']['status'] = 'PASS';
} catch (Exception $e) {
    $test_results['test_4_checkout_readiness']['error'] = $e->getMessage();
    $test_results['test_4_checkout_readiness']['status'] = 'FAIL';
}

echo json_encode([
    'success' => true,
    'tests' => $test_results,
    'session_profile_id' => $_SESSION['profile_id'] ?? null
], JSON_PRETTY_PRINT);
?>
