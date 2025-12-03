<?php
// Simple test to verify API works
header('Content-Type: text/plain');

echo "=== API Test ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Check database connection
echo "Test 1: Database Connection\n";
try {
    require_once 'perfdb/connect.php';
    echo "✓ Database connected successfully\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: Check products table
echo "Test 2: Products Table\n";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Product count: " . $result['count'] . "\n\n";
} catch (Exception $e) {
    echo "✗ Error querying products: " . $e->getMessage() . "\n\n";
}

// Test 3: Check get_products.php API
echo "Test 3: API Response\n";
$url = 'http://localhost/pefumeppp/perfdb/get_products.php';
$response = file_get_contents($url);
echo "Response:\n";
echo $response . "\n\n";

// Test 4: Decode and verify
echo "Test 4: JSON Parsing\n";
try {
    $data = json_decode($response, true);
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Product Count: " . ($data['products'] ? count($data['products']) : 0) . "\n";
} catch (Exception $e) {
    echo "✗ JSON parse error: " . $e->getMessage() . "\n";
}
?>
