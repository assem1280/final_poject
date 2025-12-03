<?php
// Add custom mix of products to cart
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Pricing constants
define('PRICE_PER_10ML', 2.5);
$bottle_prices = [
    100 => 6.0,
    50 => 4.0,
    30 => 3.0
];

$selected_products = isset($input['products']) ? $input['products'] : [];
$bottle_size = isset($input['bottle_size']) ? intval($input['bottle_size']) : 50;
$bottle_design_id = isset($input['bottle_design_id']) ? intval($input['bottle_design_id']) : 1;
$bottle_image = isset($input['bottle_image']) ? $input['bottle_image'] : '/pefumeppp/images/Untitled_design-removebg-preview.png';

// Convert relative paths to absolute paths for consistency
if (strpos($bottle_image, '../images/') === 0) {
    $bottle_image = '/pefumeppp/images/' . basename($bottle_image);
}

// Validation
if (empty($selected_products)) {
    echo json_encode(['success' => false, 'error' => 'Please select at least one product']);
    exit;
}

if (count($selected_products) > 3) {
    echo json_encode(['success' => false, 'error' => 'Maximum 3 products allowed']);
    exit;
}

if (!isset($bottle_prices[$bottle_size])) {
    echo json_encode(['success' => false, 'error' => 'Invalid bottle size']);
    exit;
}

// Calculate total ml and validate
$total_ml = 0;
foreach ($selected_products as $product) {
    $ml = floatval($product['ml']);
    if ($ml <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product amount']);
        exit;
    }
    $total_ml += $ml;
}

if ($total_ml > $bottle_size) {
    echo json_encode(['success' => false, 'error' => "Total amount ($total_ml ml) exceeds bottle capacity ($bottle_size ml)"]);
    exit;
}

// Calculate price
// Price = (total_ml / 10) * $2.5 + bottle_cost
$products_cost = ($total_ml / 10) * PRICE_PER_10ML;
$bottle_cost = $bottle_prices[$bottle_size];
$total_price = $products_cost + $bottle_cost;

$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

try {
    if ($profile_id) {
        // User is logged in - save to custom_cart_items using PDO
        $insert_query = "INSERT INTO custom_cart_items (customer_profile_id, bottle_design_id, oil_amount_grams, custom_price) 
                         VALUES (:profile_id, :bottle_id, :total_ml, :total_price)";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->execute([
            ':profile_id' => $profile_id,
            ':bottle_id' => $bottle_design_id,
            ':total_ml' => $total_ml,
            ':total_price' => $total_price
        ]);
        
        $custom_cart_id = $conn->lastInsertId();

        // Save each product in the mix to custom_cart_types
        foreach ($selected_products as $product) {
            $product_id = intval($product['product_id']);
            $ml = floatval($product['ml']);
            $percentage = ($ml / $total_ml) * 100;

            $type_query = "INSERT INTO custom_cart_types (custom_cart_id, type_id, amount_percent) 
                          VALUES (:custom_cart_id, :type_id, :amount_percent)";
            $type_stmt = $conn->prepare($type_query);
            $type_stmt->execute([
                ':custom_cart_id' => $custom_cart_id,
                ':type_id' => $product_id,
                ':amount_percent' => $percentage
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Custom mix added to cart',
            'custom_cart_id' => $custom_cart_id,
            'total_price' => $total_price,
            'breakdown' => [
                'products_cost' => $products_cost,
                'bottle_cost' => $bottle_cost,
                'total_ml' => $total_ml
            ]
        ]);
    } else {
        // User not logged in - use session cart
        if (!isset($_SESSION['custom_cart'])) {
            $_SESSION['custom_cart'] = [];
        }
        
        $custom_item = [
            'bottle_design_id' => $bottle_design_id,
            'bottle_size' => $bottle_size,
            'bottle_image' => $bottle_image,
            'products' => $selected_products,
            'total_ml' => $total_ml,
            'total_price' => $total_price,
            'breakdown' => [
                'products_cost' => $products_cost,
                'bottle_cost' => $bottle_cost
            ]
        ];
        
        $_SESSION['custom_cart'][] = $custom_item;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Custom mix added to cart (session)',
            'total_price' => $total_price,
            'breakdown' => [
                'products_cost' => $products_cost,
                'bottle_cost' => $bottle_cost,
                'total_ml' => $total_ml
            ]
        ]);
    }
} catch (PDOException $e) {
    error_log("Add custom mix error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>

