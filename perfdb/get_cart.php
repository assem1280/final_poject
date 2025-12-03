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

    $cart_items = [];
    $total = 0;

    // If session cart has items (assoc array with composite keys like "product_id_volume"), fetch product details
    if (!empty($session_cart)) {
        try {
            // Extract unique product IDs from composite keys
            $productIds = [];
            $cartItemsMap = []; // Map composite keys to quantities and volumes
            
            foreach ($session_cart as $key => $qty) {
                $parts = explode('_', $key);
                if (count($parts) === 2) {
                    $pid = intval($parts[0]);
                    $volume = intval($parts[1]);
                    $productIds[] = $pid;
                    $cartItemsMap[$key] = ['product_id' => $pid, 'volume' => $volume, 'qty' => $qty];
                }
            }
            
            if (!empty($productIds)) {
                $ids_list = implode(',', array_unique($productIds));
                $q = "SELECT p_id, p_name, price, stock, description, image_url FROM products WHERE p_id IN ($ids_list)";
                
                $stmt = $conn->query($q);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Create a product lookup map
                $productMap = [];
                foreach ($rows as $row) {
                    $productMap[$row['p_id']] = $row;
                }
                
                // Process each cart item with volume info
                foreach ($cartItemsMap as $cartKey => $itemInfo) {
                    $pid = $itemInfo['product_id'];
                    $volume = $itemInfo['volume'];
                    $qty = $itemInfo['qty'];
                    
                    if (isset($productMap[$pid])) {
                        $row = $productMap[$pid];
                        
                        // Calculate price with volume adjustment
                        $basePrice = $row['price'];
                        $volumeAdjustment = $volume === 100 ? 50.00 : 0.00; // 100ml adds $50
                        $pricePerUnit = $basePrice + $volumeAdjustment;
                        $subtotal = $pricePerUnit * $qty;
                        $total += $subtotal;
                        
                        $cart_items[] = [
                            'cart_item_id' => null,
                            'product_id' => $pid,
                            'name' => $row['p_name'],
                            'brand' => null,
                            'gender' => null,
                            'price' => $pricePerUnit,
                            'quantity' => $qty,
                            'volume_ml' => $volume,
                            'stock' => $row['stock'],
                            'subtotal' => $subtotal,
                            'description' => $row['description'],
                            'image_url' => $row['image_url']
                        ];
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Error fetching session cart items: " . $e->getMessage());
        }
    }

    // Map session custom cart entries into a consistent custom_items structure
    $custom_items = [];
    if (!empty($custom_cart) && is_array($custom_cart)) {
        foreach ($custom_cart as $idx => $cc) {
            // Each session custom item may already include products[], total_price and breakdown
            $price = isset($cc['total_price']) ? floatval($cc['total_price']) : 0;
            $total += $price;

            $custom_items[] = [
                'session_index' => $idx,
                'bottle_design_id' => isset($cc['bottle_design_id']) ? $cc['bottle_design_id'] : null,
                'bottle_size' => isset($cc['bottle_size']) ? $cc['bottle_size'] : null,
                'bottle_image' => isset($cc['bottle_image']) ? $cc['bottle_image'] : '/pefumeppp/images/Untitled_design-removebg-preview.png',
                'products' => isset($cc['products']) ? $cc['products'] : [],
                'total_ml' => isset($cc['total_ml']) ? $cc['total_ml'] : null,
                'price' => $price,
                'breakdown' => isset($cc['breakdown']) ? $cc['breakdown'] : null
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'user_logged_in' => false,
        'first_name' => '',
        'cart_items' => $cart_items,
        'custom_items' => $custom_items,
        'session_cart' => $session_cart,
        'session_custom' => $custom_cart,
        'total' => $total,
        'item_count' => count($cart_items) + count($custom_items)
    ]);
    exit;
}

// Get regular products from cart
try {
    $cart_query = "SELECT ci.cart_item_id, ci.quantity, ci.added_at,
                          p.p_id, p.p_name, p.price, p.stock, p.description,
                          COALESCE(ci.volume_ml, 50) as volume_ml,
                          b.brand_name, g.gender_name
                   FROM cart_items ci
                   JOIN products p ON ci.product_id = p.p_id
                   LEFT JOIN brands b ON p.brand_id = b.brand_id
                   LEFT JOIN product_genders g ON p.gender_id = g.gender_id
                   WHERE ci.customer_profile_id = :profile_id";

    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->execute([':profile_id' => $profile_id]);
    $rows = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cart_items = [];
    $total = 0;

    foreach ($rows as $row) {
        $basePrice = $row['price'];
        $volume_ml = $row['volume_ml'];
        
        // Calculate price per unit with volume adjustment
        $volumeAdjustment = ($volume_ml === 100 || $volume_ml == 100) ? 50.00 : 0.00;
        $pricePerUnit = $basePrice + $volumeAdjustment;
        
        $subtotal = $pricePerUnit * $row['quantity'];
        $total += $subtotal;
        
        $cart_items[] = [
            'cart_item_id' => $row['cart_item_id'],
            'product_id' => $row['p_id'],
            'name' => $row['p_name'],
            'brand' => $row['brand_name'],
            'gender' => $row['gender_name'],
            'price' => $pricePerUnit,
            'quantity' => $row['quantity'],
            'volume_ml' => $volume_ml,
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
                     WHERE cci.customer_profile_id = :profile_id";

    $custom_stmt = $conn->prepare($custom_query);
    $custom_stmt->execute([':profile_id' => $profile_id]);
    $custom_rows = $custom_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $custom_items = [];

    foreach ($custom_rows as $row) {
        $custom_cart_id = $row['custom_cart_id'];
        
        // Get scent types for this custom perfume
        $types_query = "SELECT pt.type_name, cct.amount_percent
                        FROM custom_cart_types cct
                        JOIN perfume_types pt ON cct.type_id = pt.type_id
                        WHERE cct.custom_cart_id = :custom_cart_id";
        
        $types_stmt = $conn->prepare($types_query);
        $types_stmt->execute([':custom_cart_id' => $custom_cart_id]);
        $type_rows = $types_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $scents = [];
        
        foreach ($type_rows as $type_row) {
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
        'user_logged_in' => true,
        'first_name' => isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '',
        'cart_items' => $cart_items,
        'custom_items' => $custom_items,
        'total' => $total,
        'item_count' => count($cart_items) + count($custom_items)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
