<?php
// Populate perfume_types table from products
header('Content-Type: application/json');
include 'connect.php';

try {
    // Get all products
    $query = "SELECT p_id, p_name FROM products ORDER BY p_id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'message' => 'Starting to populate perfume_types table',
        'total_products' => count($products),
        'products_sample' => array_slice($products, 0, 5)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    // Clear existing perfume_types to avoid duplicates
    $conn->exec("TRUNCATE TABLE perfume_types");
    
    // Insert each product as a perfume type
    $insert_query = "INSERT INTO perfume_types (type_id, type_name, description) VALUES (:type_id, :type_name, :description)";
    $insert_stmt = $conn->prepare($insert_query);
    
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    foreach ($products as $product) {
        try {
            $insert_stmt->execute([
                ':type_id' => $product['p_id'],
                ':type_name' => $product['p_name'],
                ':description' => 'Perfume ingredient from product ' . $product['p_id']
            ]);
            $success_count++;
        } catch (Exception $e) {
            $error_count++;
            $errors[] = "Product {$product['p_id']}: {$e->getMessage()}";
        }
    }
    
    echo "\n\n=== RESULTS ===\n";
    echo "Successfully inserted: $success_count records\n";
    echo "Errors: $error_count\n";
    
    if ($error_count > 0) {
        echo "\nFirst 5 errors:\n";
        foreach (array_slice($errors, 0, 5) as $error) {
            echo "- $error\n";
        }
    }
    
    // Verify the insert
    $verify_query = "SELECT COUNT(*) as total FROM perfume_types";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->execute();
    $result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nFinal perfume_types count: " . $result['total'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
