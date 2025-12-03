<?php
// Add product to cart
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$product_id = isset($input['product_id']) ? intval($input['product_id']) : 0;
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;
$volume = isset($input['size']) ? intval($input['size']) : 50; // 'size' from frontend, store as volume_ml

// Validate volume - must be 50 or 100
if ($volume !== 50 && $volume !== 100) {
    $volume = 50; // default to 50ml
}

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

// Check product stock from products table
try {
    $stockQuery = "SELECT stock FROM products WHERE p_id = :product_id LIMIT 1";
    $stockStmt = $conn->prepare($stockQuery);
    $stockStmt->execute([':product_id' => $product_id]);
    $productRow = $stockStmt->fetch(PDO::FETCH_ASSOC);
    $availableStock = $productRow ? intval($productRow['stock']) : 0;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

if ($availableStock <= 0) {
    echo json_encode(['success' => false, 'error' => 'That quantity is not available.']);
    exit;
}

// Check if user is logged in
$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;

if ($profile_id) {
    // User is logged in - save to database (cart_items table)
    try {
        // Check existing quantity in user's cart for this product+volume
        $existQuery = "SELECT COALESCE(SUM(quantity),0) AS current_qty FROM cart_items WHERE customer_profile_id = :profile_id AND product_id = :product_id AND volume_ml = :volume";
        $existStmt = $conn->prepare($existQuery);
        $existStmt->execute([':profile_id' => $profile_id, ':product_id' => $product_id, ':volume' => $volume]);
        $existRow = $existStmt->fetch(PDO::FETCH_ASSOC);
        $currentInCart = $existRow ? intval($existRow['current_qty']) : 0;

        if (($currentInCart + $quantity) > $availableStock) {
            echo json_encode(['success' => false, 'error' => 'That quantity is not available.']);
            exit;
        }

        // Add volume check to the WHERE clause for exact item matching
        $check_query = "SELECT cart_item_id FROM cart_items WHERE customer_profile_id = :profile_id AND product_id = :product_id AND volume_ml = :volume";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([':profile_id' => $profile_id, ':product_id' => $product_id, ':volume' => $volume]);
        
        if ($check_stmt->rowCount() > 0) {
            // Update quantity for same product and volume
            $update_query = "UPDATE cart_items SET quantity = quantity + :quantity WHERE customer_profile_id = :profile_id AND product_id = :product_id AND volume_ml = :volume";
            $update_stmt = $conn->prepare($update_query);
            $result = $update_stmt->execute([':quantity' => $quantity, ':profile_id' => $profile_id, ':product_id' => $product_id, ':volume' => $volume]);
        } else {
            // Insert new item with volume
            $insert_query = "INSERT INTO cart_items (customer_profile_id, product_id, quantity, volume_ml) VALUES (:profile_id, :product_id, :quantity, :volume)";
            $insert_stmt = $conn->prepare($insert_query);
            $result = $insert_stmt->execute([':profile_id' => $profile_id, ':product_id' => $product_id, ':quantity' => $quantity, ':volume' => $volume]);
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Product added to cart']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add to cart']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    // User not logged in - use session cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Use composite key to track product ID and volume separately
    $cartKey = $product_id . '_' . $volume;
    
    $existingQty = isset($_SESSION['cart'][$cartKey]) ? intval($_SESSION['cart'][$cartKey]) : 0;
    if (($existingQty + $quantity) > $availableStock) {
        echo json_encode(['success' => false, 'error' => 'That quantity is not available.']);
        exit;
    }

    if (isset($_SESSION['cart'][$cartKey])) {
        $_SESSION['cart'][$cartKey] += $quantity;
    } else {
        $_SESSION['cart'][$cartKey] = $quantity;
    }

    echo json_encode(['success' => true, 'message' => 'Product added to cart (session)']);
}
?>

