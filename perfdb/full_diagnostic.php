<?php
// Comprehensive diagnostic script
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'connect.php';

$diagnostic = [];

// ===== 1. SESSION INFORMATION =====
$diagnostic['session'] = [
    'profile_id' => $_SESSION['profile_id'] ?? null,
    'email' => $_SESSION['email'] ?? null,
    'logged_in' => isset($_SESSION['profile_id']) ? true : false,
    'session_id' => session_id(),
    'all_keys' => array_keys($_SESSION)
];

$profile_id = $_SESSION['profile_id'] ?? null;

// ===== 2. CHECK CART IN DATABASE =====
$diagnostic['db_cart'] = [];
if ($profile_id) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE customer_profile_id = :profile_id");
        $stmt->execute([':profile_id' => $profile_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $diagnostic['db_cart']['count'] = $result['count'];
        
        // Get details of items
        $stmt2 = $conn->prepare("SELECT cart_item_id, product_id, quantity, volume_ml FROM cart_items WHERE customer_profile_id = :profile_id LIMIT 5");
        $stmt2->execute([':profile_id' => $profile_id]);
        $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $diagnostic['db_cart']['items'] = $items;
    } catch (Exception $e) {
        $diagnostic['db_cart']['error'] = $e->getMessage();
    }
} else {
    $diagnostic['db_cart']['error'] = 'No profile_id in session';
}

// ===== 3. CHECK SESSION CART (for non-logged-in) =====
$diagnostic['session_cart'] = [
    'has_cart_data' => isset($_SESSION['cart']),
    'cart_items_count' => isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0,
    'cart_data' => $_SESSION['cart'] ?? []
];

// ===== 4. CHECK CUSTOM CART =====
$diagnostic['custom_cart'] = [];
if ($profile_id) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM custom_cart_items WHERE customer_profile_id = :profile_id");
        $stmt->execute([':profile_id' => $profile_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $diagnostic['custom_cart']['db_count'] = $result['count'];
    } catch (Exception $e) {
        $diagnostic['custom_cart']['error'] = $e->getMessage();
    }
} else {
    $diagnostic['custom_cart']['error'] = 'No profile_id in session';
}

// ===== 5. TEST GET_CART.PHP RESPONSE =====
$diagnostic['get_cart_test'] = [];
try {
    // Simulate what get_cart.php would return
    if (!$profile_id) {
        // Session cart
        $session_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $diagnostic['get_cart_test']['mode'] = 'session_cart';
        $diagnostic['get_cart_test']['count'] = count($session_cart);
    } else {
        // Database cart
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE customer_profile_id = :profile_id");
        $stmt->execute([':profile_id' => $profile_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $diagnostic['get_cart_test']['mode'] = 'database_cart';
        $diagnostic['get_cart_test']['count'] = $result['count'];
    }
} catch (Exception $e) {
    $diagnostic['get_cart_test']['error'] = $e->getMessage();
}

// ===== 6. TEST TOTAL CALCULATION =====
$diagnostic['total_calculation'] = [];
if ($profile_id) {
    try {
        $total_query = "SELECT SUM(
            p.price * ci.quantity + 
            (CASE WHEN COALESCE(ci.volume_ml, 50) = 100 THEN 50.00 * ci.quantity ELSE 0 END)
        ) as cart_total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.p_id
        WHERE ci.customer_profile_id = :profile_id";
        
        $stmt = $conn->prepare($total_query);
        $stmt->execute([':profile_id' => $profile_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $diagnostic['total_calculation']['cart_total'] = $result['cart_total'];
        $diagnostic['total_calculation']['is_zero'] = $result['cart_total'] === null || $result['cart_total'] == 0;
        
        // Also get custom total
        $custom_query = "SELECT SUM(custom_price) as custom_total FROM custom_cart_items WHERE customer_profile_id = :profile_id";
        $stmt2 = $conn->prepare($custom_query);
        $stmt2->execute([':profile_id' => $profile_id]);
        $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $diagnostic['total_calculation']['custom_total'] = $result2['custom_total'];
        $diagnostic['total_calculation']['combined_total'] = ($result['cart_total'] ?? 0) + ($result2['custom_total'] ?? 0);
    } catch (Exception $e) {
        $diagnostic['total_calculation']['error'] = $e->getMessage();
    }
}

// ===== 7. CHECK PRODUCTS TABLE =====
$diagnostic['products'] = [];
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $diagnostic['products']['total_count'] = $result['count'];
} catch (Exception $e) {
    $diagnostic['products']['error'] = $e->getMessage();
}

// ===== 8. DIAGNOSIS SUMMARY =====
$diagnostic['summary'] = [];
$diagnostic['summary']['user_logged_in'] = isset($profile_id) && $profile_id;
$diagnostic['summary']['has_db_items'] = isset($diagnostic['db_cart']['count']) && $diagnostic['db_cart']['count'] > 0;
$diagnostic['summary']['has_session_items'] = isset($_SESSION['cart']) && count($_SESSION['cart']) > 0;
$diagnostic['summary']['has_any_items'] = $diagnostic['summary']['has_db_items'] || $diagnostic['summary']['has_session_items'];
$diagnostic['summary']['total_is_zero'] = $diagnostic['total_calculation']['combined_total'] ?? 0 == 0;

echo json_encode($diagnostic, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
