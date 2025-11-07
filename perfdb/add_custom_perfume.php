<?php
// Add custom perfume to cart
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$scents = isset($input['scents']) ? $input['scents'] : [];
$bottle_design_id = isset($input['bottle_design_id']) ? intval($input['bottle_design_id']) : 1;
$oil_amount_grams = isset($input['oil_amount_grams']) ? floatval($input['oil_amount_grams']) : 50;
$custom_price = isset($input['custom_price']) ? floatval($input['custom_price']) : 125.0;

if (empty($scents)) {
    echo json_encode(['success' => false, 'error' => 'No scents selected']);
    exit;
}

$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

if ($profile_id) {
    // User is logged in - save to custom_cart_items table
    $insert_query = "INSERT INTO custom_cart_items (customer_profile_id, bottle_design_id, oil_amount_grams, custom_price) 
                     VALUES ($profile_id, $bottle_design_id, $oil_amount_grams, $custom_price)";
    
    if (mysqli_query($conn, $insert_query)) {
        $custom_cart_id = mysqli_insert_id($conn);
        
        // Add scent types to custom_cart_types table
        foreach ($scents as $scent) {
            $type_id = intval($scent['type_id']);
            $amount_percent = floatval($scent['amount_percent']);
            
            $type_query = "INSERT INTO custom_cart_types (custom_cart_id, type_id, amount_percent) 
                          VALUES ($custom_cart_id, $type_id, $amount_percent)";
            mysqli_query($conn, $type_query);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Custom perfume added to cart',
            'custom_cart_id' => $custom_cart_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add custom perfume: ' . mysqli_error($conn)]);
    }
} else {
    // User not logged in - use session cart
    if (!isset($_SESSION['custom_cart'])) {
        $_SESSION['custom_cart'] = [];
    }
    
    $custom_item = [
        'bottle_design_id' => $bottle_design_id,
        'oil_amount_grams' => $oil_amount_grams,
        'custom_price' => $custom_price,
        'scents' => $scents
    ];
    
    $_SESSION['custom_cart'][] = $custom_item;
    
    echo json_encode(['success' => true, 'message' => 'Custom perfume added to cart (session)']);
}

mysqli_close($conn);
?>
