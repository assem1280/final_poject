<?php
// Get all product genders (categories) from database
header('Content-Type: application/json');
require_once 'connect.php';

$query = "SELECT gender_id, gender_name FROM product_genders ORDER BY gender_id ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Database query failed: ' . mysqli_error($conn)]);
    exit;
}

$genders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $genders[] = [
        'id' => $row['gender_id'],
        'name' => $row['gender_name']
    ];
}

echo json_encode([
    'success' => true,
    'genders' => $genders,
    'count' => count($genders)
]);

mysqli_close($conn);
?>
