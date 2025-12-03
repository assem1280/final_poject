<?php
// Remove item from cart
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$cart_item_id = isset($input['cart_item_id']) ? intval($input['cart_item_id']) : 0;
$custom_cart_id = isset($input['custom_cart_id']) ? intval($input['custom_cart_id']) : 0;

$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

// If user not logged in, allow removing items from the session cart
if (!$profile_id) {
    // remove by product_id and volume from session cart
    $product_id = isset($input['product_id']) ? intval($input['product_id']) : 0;
    $volume = isset($input['volume']) ? intval($input['volume']) : 50;
    $session_custom_index = isset($input['session_custom_index']) ? intval($input['session_custom_index']) : null;

    if ($product_id > 0) {
        // Use composite key to remove specific volume
        $cartKey = $product_id . '_' . $volume;
        if (isset($_SESSION['cart'][$cartKey])) {
            unset($_SESSION['cart'][$cartKey]);
            echo json_encode(['success' => true, 'message' => 'Item removed from session cart']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Item not found in session cart']);
        }
        exit;
    }

    if ($session_custom_index !== null) {
        if (isset($_SESSION['custom_cart']) && isset($_SESSION['custom_cart'][$session_custom_index])) {
            array_splice($_SESSION['custom_cart'], $session_custom_index, 1);
            echo json_encode(['success' => true, 'message' => 'Custom item removed from session cart']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Custom item not found in session']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

if ($cart_item_id > 0) {
    // Remove regular product from cart
    try {
        $delete_query = "DELETE FROM cart_items 
                         WHERE cart_item_id = :cart_item_id 
                         AND customer_profile_id = :profile_id";
        
        $delete_stmt = $conn->prepare($delete_query);
        $result = $delete_stmt->execute([':cart_item_id' => $cart_item_id, ':profile_id' => $profile_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove item']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($custom_cart_id > 0) {
    // Remove custom perfume from cart (types will be deleted automatically by CASCADE)
    try {
        $delete_query = "DELETE FROM custom_cart_items 
                         WHERE custom_cart_id = :custom_cart_id 
                         AND customer_profile_id = :profile_id";
        
        $delete_stmt = $conn->prepare($delete_query);
        $result = $delete_stmt->execute([':custom_cart_id' => $custom_cart_id, ':profile_id' => $profile_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Custom perfume removed from cart']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove custom perfume']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
}
?>
