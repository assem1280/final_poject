<?php
// Populate genders table
require_once 'perfdb/connect.php';

try {
    // First check if genders table exists and create if not
    $conn->exec("CREATE TABLE IF NOT EXISTS genders (
        gender_id INT PRIMARY KEY AUTO_INCREMENT,
        gender_name VARCHAR(50) NOT NULL
    )");
    
    // Check if genders already exist
    $stmt = $conn->query("SELECT COUNT(*) as count FROM genders");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($count['count'] == 0) {
        // Insert gender values
        $conn->exec("INSERT INTO genders (gender_id, gender_name) VALUES 
            (1, 'Man'),
            (2, 'Woman'),
            (3, 'Unisex')");
        
        echo "Success! Genders table populated with 3 records.<br>";
    } else {
        echo "Genders table already has " . $count['count'] . " records.<br>";
    }
    
    // Display the genders
    $stmt = $conn->query("SELECT * FROM genders");
    $genders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($genders);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
