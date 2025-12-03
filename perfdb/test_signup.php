<?php
header('Content-Type: application/json');
require_once 'connect.php';

// Test signup directly
$test_data = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test' . time() . '@example.com',
    'password' => password_hash('test123', PASSWORD_BCRYPT),
    'phone' => '+966501234567',
    'address' => 'Test Address, Riyadh',
    'role' => 'customer'
];

try {
    // Try to insert
    $insert_query = "INSERT INTO profiles 
        (first_name, last_name, email, password, role, phone, address, created_at) 
        VALUES 
        (:first_name, :last_name, :email, :password, :role, :phone, :address, NOW())";
    
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->execute($test_data);
    
    $profile_id = $conn->lastInsertId();
    
    // Now retrieve it to verify
    $select_query = "SELECT * FROM profiles WHERE profile_id = :id";
    $select_stmt = $conn->prepare($select_query);
    $select_stmt->execute([':id' => $profile_id]);
    $result = $select_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test record created successfully',
        'profile_id' => $profile_id,
        'data_inserted' => $test_data,
        'data_retrieved' => $result,
        'phone_value' => $result['phone'] ?? 'NOT FOUND',
        'address_value' => $result['address'] ?? 'NOT FOUND'
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
}
?>
