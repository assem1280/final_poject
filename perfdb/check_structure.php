<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        table { border-collapse: collapse; width: 100%; background: white; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
<h1>Database Structure Check</h1>";

try {
    // 1. Check table structure
    echo "<div class='section'><h2>Profiles Table Structure:</h2>";
    $result = $conn->query('DESCRIBE profiles');
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $phone_found = false;
    $address_found = false;
    
    foreach($columns as $col) {
        if ($col['Field'] === 'phone') $phone_found = true;
        if ($col['Field'] === 'address') $address_found = true;
        
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Summary:</h3>";
    echo "<p>Phone column: <span class='" . ($phone_found ? 'success' : 'error') . "'>" . ($phone_found ? '✓ FOUND' : '✗ NOT FOUND - NEED TO ADD') . "</span></p>";
    echo "<p>Address column: <span class='" . ($address_found ? 'success' : 'error') . "'>" . ($address_found ? '✓ FOUND' : '✗ NOT FOUND - NEED TO ADD') . "</span></p>";
    echo "</div>";
    
    // 2. Check last 5 records
    echo "<div class='section'><h2>Last 5 Records in Profiles:</h2>";
    $data = $conn->query('SELECT profile_id, first_name, last_name, email, phone, address FROM profiles ORDER BY profile_id DESC LIMIT 5');
    $rows = $data->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th><th>Address</th></tr>";
        foreach($rows as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['profile_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . ($row['phone'] ? htmlspecialchars($row['phone']) : '<span class=\"error\">EMPTY</span>') . "</td>";
            echo "<td>" . ($row['address'] ? htmlspecialchars($row['address']) : '<span class=\"error\">EMPTY</span>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No records found</p>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
