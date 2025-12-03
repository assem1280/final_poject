<?php
require_once 'connect.php';

echo "=== Checking profiles table structure ===\n\n";

try {
    $result = $conn->query('DESCRIBE profiles');
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in profiles table:\n";
    foreach($columns as $col) {
        echo "  - " . $col['Field'] . " | Type: " . $col['Type'] . " | Null: " . $col['Null'] . "\n";
    }
    
    echo "\n=== Sample data from profiles table ===\n";
    $data = $conn->query('SELECT profile_id, first_name, last_name, email, phone, address FROM profiles LIMIT 3');
    $rows = $data->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "Sample records:\n";
        foreach($rows as $row) {
            echo "  ID: " . $row['profile_id'] . " | Name: " . $row['first_name'] . " " . $row['last_name'] . " | Email: " . $row['email'] . " | Phone: " . ($row['phone'] ?: 'NULL') . " | Address: " . ($row['address'] ?: 'NULL') . "\n";
        }
    } else {
        echo "No records found in profiles table\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
