<?php
// Comprehensive test for signup phone and address issue
header('Content-Type: application/json');
require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Show current state if GET request
    try {
        $result = $conn->query("DESCRIBE profiles");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        $col_names = array_map(function($c) { return $c['Field']; }, $columns);
        
        // Get last 3 records
        $data = $conn->query("SELECT profile_id, first_name, email, phone, address FROM profiles ORDER BY profile_id DESC LIMIT 3");
        $records = $data->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'columns' => $col_names,
            'has_phone' => in_array('phone', $col_names),
            'has_address' => in_array('address', $col_names),
            'last_records' => $records
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Handle POST for testing signup
$input = json_decode(file_get_contents('php://input'), true);

try {
    $phone = $input['phone'] ?? '';
    $address = $input['address'] ?? '';
    $email = $input['email'] ?? ('test' . time() . '@example.com');
    
    // Test 1: Check columns exist
    $result = $conn->query("DESCRIBE profiles");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    $col_names = array_map(function($c) { return $c['Field']; }, $columns);
    
    // Test 2: Insert with phone and address
    $insert_query = "INSERT INTO profiles 
        (first_name, last_name, email, password, phone, address, role, created_at) 
        VALUES 
        (:first_name, :last_name, :email, :password, :phone, :address, :role, NOW())";
    
    $stmt = $conn->prepare($insert_query);
    $result = $stmt->execute([
        ':first_name' => 'Test',
        ':last_name' => 'User',
        ':email' => $email,
        ':password' => password_hash('test123', PASSWORD_BCRYPT),
        ':phone' => $phone,
        ':address' => $address,
        ':role' => 'customer'
    ]);
    
    $new_id = $conn->lastInsertId();
    
    // Test 3: Verify what was saved
    $verify = $conn->prepare("SELECT * FROM profiles WHERE profile_id = :id");
    $verify->execute([':id' => $new_id]);
    $saved = $verify->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'columns_in_table' => $col_names,
        'has_phone_column' => in_array('phone', $col_names),
        'has_address_column' => in_array('address', $col_names),
        'inserted_id' => $new_id,
        'test_phone_sent' => $phone,
        'test_address_sent' => $address,
        'saved_phone' => $saved['phone'] ?? 'COLUMN NOT FOUND',
        'saved_address' => $saved['address'] ?? 'COLUMN NOT FOUND',
        'all_saved_data' => $saved
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
