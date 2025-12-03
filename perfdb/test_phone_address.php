<?php
header('Content-Type: application/json');
require_once 'connect.php';

try {
    // Check if phone and address columns exist
    $result = $conn->query("DESCRIBE profiles");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $has_phone = false;
    $has_address = false;
    
    foreach($columns as $col) {
        if ($col['Field'] === 'phone') $has_phone = true;
        if ($col['Field'] === 'address') $has_address = true;
    }
    
    echo json_encode([
        'phone_exists' => $has_phone,
        'address_exists' => $has_address,
        'all_columns' => array_map(function($c) { return $c['Field']; }, $columns)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
