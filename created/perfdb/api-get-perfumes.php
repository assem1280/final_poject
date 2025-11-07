<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// تضمين ملف الاتصال
require_once 'connect.php';

// الحصول على المعاملات
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$brand = isset($_GET['brand']) ? $conn->real_escape_string($_GET['brand']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;

// بناء الاستعلام
$sql = "SELECT * FROM perfumes WHERE 1=1";

if (!empty($category)) {
    $sql .= " AND category = '$category'";
}

if (!empty($brand)) {
    $sql .= " AND brand = '$brand'";
}

$sql .= " ORDER BY id DESC";

if ($limit > 0) {
    $sql .= " LIMIT $limit";
}

// تنفيذ الاستعلام
$result = $conn->query($sql);

if ($result) {
    $perfumes = [];
    while ($row = $result->fetch_assoc()) {
        $perfumes[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'brand' => $row['brand'],
            'category' => $row['category'],
            'price' => floatval($row['price']),
            'image' => $row['image']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($perfumes),
        'data' => $perfumes
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
