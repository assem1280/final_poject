<?php
// Test gender data in database
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once 'perfdb/connect.php';

try {
    // Check genders table
    $stmt = $conn->query('SELECT * FROM genders ORDER BY gender_id');
    $genders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check products with their gender values
    $stmt2 = $conn->query('SELECT p.p_id, p.p_name, p.gender_id, g.gender_name 
                           FROM products p 
                           LEFT JOIN genders g ON p.gender_id = g.gender_id 
                           LIMIT 10');
    $products = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if any products have NULL gender
    $stmt3 = $conn->query('SELECT COUNT(*) as null_count FROM products WHERE gender_id IS NULL');
    $nullCount = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'genders_table' => $genders,
        'sample_products' => $products,
        'products_with_null_gender' => $nullCount['null_count']
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
