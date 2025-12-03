<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'connect.php';

echo "<h2>Database Debug Information</h2>";

try {
    // 1. Check table structure
    echo "<h3>1. Profiles Table Structure:</h3>";
    $result = $conn->query('DESCRIBE profiles');
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . ($col['Key'] ?? '') . "</td>";
        echo "<td>" . ($col['Default'] ?? '') . "</td>";
        echo "<td>" . ($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Check if phone and address columns exist
    echo "<h3>2. Column Check:</h3>";
    $phone_exists = false;
    $address_exists = false;
    
    foreach($columns as $col) {
        if ($col['Field'] === 'phone') $phone_exists = true;
        if ($col['Field'] === 'address') $address_exists = true;
    }
    
    echo "<p><strong>Phone column exists:</strong> " . ($phone_exists ? "✅ YES" : "❌ NO") . "</p>";
    echo "<p><strong>Address column exists:</strong> " . ($address_exists ? "✅ YES" : "❌ NO") . "</p>";
    
    // 3. Show all records with all fields
    echo "<h3>3. Sample Data:</h3>";
    $data = $conn->query('SELECT * FROM profiles ORDER BY created_at DESC LIMIT 5');
    $rows = $data->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach(array_keys($rows[0]) as $key) {
            echo "<th>" . $key . "</th>";
        }
        echo "</tr>";
        
        foreach($rows as $row) {
            echo "<tr>";
            foreach($row as $val) {
                echo "<td>" . ($val ?? '<em style="color:red;">NULL</em>') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No records found</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
