<?php
// Debug script to check custom perfume data
include 'connect.php';

echo "=== CHECKING CUSTOM PERFUME DATA ===\n\n";

// Check if perfume_types table exists
echo "1. Checking perfume_types table structure:\n";
$result = $conn->query('DESCRIBE perfume_types');
if ($result) {
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "   - {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "   ERROR: perfume_types table not found\n";
}

echo "\n2. Checking custom_perfumes table structure:\n";
$result = $conn->query('DESCRIBE custom_perfumes');
if ($result) {
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "   - {$row['Field']}: {$row['Type']}\n";
    }
}

echo "\n3. Checking custom_perfume_types table structure:\n";
$result = $conn->query('DESCRIBE custom_perfume_types');
if ($result) {
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "   - {$row['Field']}: {$row['Type']}\n";
    }
}

echo "\n4. Sample data from custom_perfumes:\n";
$result = $conn->query('SELECT * FROM custom_perfumes LIMIT 3');
if ($result) {
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($rows) . " records\n";
    foreach ($rows as $row) {
        echo "   - Order: {$row['order_id']}, Custom ID: {$row['custom_id']}, Design: {$row['bottle_design_id']}, ML: {$row['oil_amount_grams']}\n";
    }
}

echo "\n5. Sample data from custom_perfume_types:\n";
$result = $conn->query('SELECT * FROM custom_perfume_types LIMIT 5');
if ($result) {
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($rows) . " records\n";
    foreach ($rows as $row) {
        echo "   - Custom ID: {$row['custom_id']}, Type ID: {$row['type_id']}, Percent: {$row['amount_percent']}\n";
    }
}

echo "\n6. Sample data from perfume_types:\n";
$result = $conn->query('SELECT * FROM perfume_types LIMIT 5');
if ($result) {
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($rows) . " records\n";
    foreach ($rows as $row) {
        echo "   - ID: {$row['type_id']}, Name: " . (isset($row['type_name']) ? $row['type_name'] : 'NO type_name field') . "\n";
    }
} else {
    echo "   ERROR querying perfume_types\n";
}

echo "\n7. Testing the JOIN query:\n";
$query = "SELECT cp.custom_id, cp.order_id, cp.bottle_design_id, 
                cp.oil_amount_grams, cp.custom_price,
                GROUP_CONCAT(CONCAT(pt.type_name, '|', cpt.amount_percent) SEPARATOR ',') as types_detail
         FROM custom_perfumes cp
         LEFT JOIN custom_perfume_types cpt ON cp.custom_id = cpt.custom_id
         LEFT JOIN perfume_types pt ON cpt.type_id = pt.type_id
         WHERE cp.order_id = 1
         GROUP BY cp.custom_id";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute([':order_id' => 1]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($rows) . " custom items for order 1\n";
    foreach ($rows as $row) {
        echo "   - Custom: {$row['custom_id']}, Types: {$row['types_detail']}\n";
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

?>
