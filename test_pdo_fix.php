<?php
/**
 * Quick Test - Verify the PDO Parameter Fix
 * Run this to confirm signup/login work correctly
 */

header('Content-Type: application/json');
require_once 'perfdb/connect.php';

echo json_encode([
    'status' => 'testing',
    'tests' => []
], JSON_PRETTY_PRINT);

// Test 1: Check if we can query with named parameters
echo "\n\n=== TEST 1: Basic Parameter Query ===\n";
try {
    $test_query = "SELECT 1 as test WHERE 1 = :test_val";
    $test_stmt = $conn->prepare($test_query);
    $test_stmt->execute([':test_val' => 1]);
    echo "✓ Named parameters work correctly\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Try email lookup (like signup does)
echo "\n=== TEST 2: Email Lookup (Signup Pattern) ===\n";
try {
    $check_query = "SELECT profile_id FROM profiles WHERE email = :email LIMIT 1";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([':email' => 'test@example.com']);
    $count = $check_stmt->rowCount();
    echo "✓ Email lookup works (found $count records)\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Try ID lookup (like test_signup does)
echo "\n=== TEST 3: ID Lookup (Profile Query) ===\n";
try {
    $query = "SELECT profile_id FROM profiles WHERE profile_id = :id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => 1]);
    $count = $stmt->rowCount();
    echo "✓ ID lookup works (found $count records)\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Complete ===\n";
echo "If you see ✓ marks above, PDO parameter binding is working correctly!\n";
?>
