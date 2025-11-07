<?php
// Get all brands from database
header('Content-Type: application/json');
require_once 'connect.php';

$query = "SELECT brand_id, brand_name, country, description FROM brands ORDER BY brand_name ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Database query failed: ' . mysqli_error($conn)]);
    exit;
}

$brands = [];
while ($row = mysqli_fetch_assoc($result)) {
    $brands[] = [
        'id' => $row['brand_id'],
        'name' => $row['brand_name'],
        'country' => $row['country'],
        'description' => $row['description']
    ];
}

echo json_encode([
    'success' => true,
    'brands' => $brands,
    'count' => count($brands)
]);

mysqli_close($conn);
?>
