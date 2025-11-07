<?php
// Get all perfume types (scents) from database
header('Content-Type: application/json');
require_once 'connect.php';

$query = "SELECT type_id, type_name, description FROM perfume_types ORDER BY type_name ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Database query failed: ' . mysqli_error($conn)]);
    exit;
}

$types = [];
while ($row = mysqli_fetch_assoc($result)) {
    $types[] = [
        'id' => $row['type_id'],
        'name' => $row['type_name'],
        'description' => $row['description']
    ];
}

echo json_encode([
    'success' => true,
    'types' => $types,
    'count' => count($types)
]);

mysqli_close($conn);
?>
