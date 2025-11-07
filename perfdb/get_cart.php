<?php
// Get cart items for logged-in user
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

if (!$profile_id) {
    // Return session cart if not logged in
    $session_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $custom_cart = isset($_SESSION['custom_cart']) ? $_SESSION['custom_cart'] : [];
    
    echo json_encode([
        'success' => true,
        'cart_items' => [],
        'custom_items' => [],
        'session_cart' => $session_cart,
        'session_custom' => $custom_cart,
        'total' => 0
    ]);
    exit;
}

// Get regular products from cart
$cart_query = "SELECT ci.cart_item_id, ci.quantity, ci.added_at,
                      p.p_id, p.p_name, p.price, p.stock, p.description,
                      b.brand_name, g.gender_name
               FROM cart_items ci
               JOIN products p ON ci.product_id = p.p_id
               LEFT JOIN brands b ON p.brand_id = b.brand_id
               LEFT JOIN product_genders g ON p.gender_id = g.gender_id
               WHERE ci.customer_profile_id = $profile_id";

$cart_result = mysqli_query($conn, $cart_query);
$cart_items = [];
$total = 0;

while ($row = mysqli_fetch_assoc($cart_result)) {
    $subtotal = $row['price'] * $row['quantity'];
    $total += $subtotal;
    
    $cart_items[] = [
        'cart_item_id' => $row['cart_item_id'],
        'product_id' => $row['p_id'],
        'name' => $row['p_name'],
        'brand' => $row['brand_name'],
        'gender' => $row['gender_name'],
        'price' => $row['price'],
        'quantity' => $row['quantity'],
        'stock' => $row['stock'],
        'subtotal' => $subtotal,
        'description' => $row['description']
    ];
}

// Get custom perfumes from cart
$custom_query = "SELECT cci.custom_cart_id, cci.oil_amount_grams, cci.custom_price, cci.created_at,
                        bd.design_name, bd.image_url
                 FROM custom_cart_items cci
                 LEFT JOIN bottle_designs bd ON cci.bottle_design_id = bd.design_id
                 WHERE cci.customer_profile_id = $profile_id";

$custom_result = mysqli_query($conn, $custom_query);
$custom_items = [];

while ($row = mysqli_fetch_assoc($custom_result)) {
    $custom_cart_id = $row['custom_cart_id'];
    
    // Get scent types for this custom perfume
    $types_query = "SELECT pt.type_name, cct.amount_percent
                    FROM custom_cart_types cct
                    JOIN perfume_types pt ON cct.type_id = pt.type_id
                    WHERE cct.custom_cart_id = $custom_cart_id";
    
    $types_result = mysqli_query($conn, $types_query);
    $scents = [];
    
    while ($type_row = mysqli_fetch_assoc($types_result)) {
        $scents[] = [
            'name' => $type_row['type_name'],
            'percent' => $type_row['amount_percent']
        ];
    }
    
    $total += $row['custom_price'];
    
    $custom_items[] = [
        'custom_cart_id' => $custom_cart_id,
        'bottle_design' => $row['design_name'],
        'bottle_image' => $row['image_url'],
        'oil_amount_grams' => $row['oil_amount_grams'],
        'price' => $row['custom_price'],
        'scents' => $scents
    ];
}

echo json_encode([
    'success' => true,
    'cart_items' => $cart_items,
    'custom_items' => $custom_items,
    'total' => $total,
    'item_count' => count($cart_items) + count($custom_items)
]);

mysqli_close($conn);
?>
