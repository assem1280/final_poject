<?php
// Get products from database based on gender and brand
header('Content-Type: application/json');
require_once 'connect.php';

// Get parameters from request
$gender_id = isset($_GET['gender_id']) ? intval($_GET['gender_id']) : 0;
$brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;

try {
    // Build SQL query with JOIN to get brand_name and gender_name
    $query = "SELECT 
        p.p_id, 
        p.p_name, 
        p.brand_id, 
        p.gender_id, 
        p.stock, 
        p.price, 
        p.description,
        p.image_url,
        b.brand_name,
        g.gender_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    LEFT JOIN genders g ON p.gender_id = g.gender_id
    WHERE 1=1";

    if ($gender_id > 0) {
        $query .= " AND p.gender_id = :gender_id";
    }

    if ($brand_id > 0) {
        $query .= " AND p.brand_id = :brand_id";
    }

    // Order by p_id
    $query .= " ORDER BY p.p_id";

    // Prepare statement
    $stmt = $conn->prepare($query);

    // Bind parameters
    if ($gender_id > 0) {
        $stmt->bindParam(':gender_id', $gender_id, PDO::PARAM_INT);
    }
    if ($brand_id > 0) {
        $stmt->bindParam(':brand_id', $brand_id, PDO::PARAM_INT);
    }

    // Execute query
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all products
    $products = [];
    foreach ($results as $row) {
        $products[] = [
            'p_id' => $row['p_id'],
            'p_name' => $row['p_name'],
            'brand_id' => $row['brand_id'],
            'brand_name' => $row['brand_name'],
            'gender_id' => $row['gender_id'],
            'gender_name' => $row['gender_name'],
            'price' => $row['price'],
            'description' => isset($row['description']) ? $row['description'] : '',
            'stock' => isset($row['stock']) ? $row['stock'] : 0,
            'image_url' => isset($row['image_url']) ? $row['image_url'] : 'perfumes/default-perfume.jpg'
        ];
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
