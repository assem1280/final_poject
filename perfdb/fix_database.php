<?php
header('Content-Type: application/json');
require_once 'connect.php';

$response = ['success' => false, 'message' => ''];

try {
    // First, check if phone and address columns exist
    $result = $conn->query('DESCRIBE profiles');
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $phone_exists = false;
    $address_exists = false;
    
    foreach($columns as $col) {
        if ($col['Field'] === 'phone') $phone_exists = true;
        if ($col['Field'] === 'address') $address_exists = true;
    }
    
    // If columns don't exist, add them
    if (!$phone_exists) {
        $conn->exec("ALTER TABLE profiles ADD COLUMN phone VARCHAR(20) NULL AFTER password");
        $response['message'] .= "✓ Added phone column\n";
    }
    
    if (!$address_exists) {
        $conn->exec("ALTER TABLE profiles ADD COLUMN address TEXT NULL AFTER phone");
        $response['message'] .= "✓ Added address column\n";
    }
    
    if ($phone_exists && $address_exists) {
        $response['success'] = true;
        $response['message'] = "✓ All required columns already exist!";
    } else {
        $response['success'] = true;
        $response['message'] .= "✓ Database updated successfully!";
    }
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
    $response['error_code'] = $e->getCode();
}

echo json_encode($response);
?>
