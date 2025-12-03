<?php
// Diagnostic script to check cart issues
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once 'connect.php';

echo "<h2>🔍 Cart Diagnosis Report</h2>";

// 1. Check cart_items table structure
echo "<h3>1. Cart Items Table Structure:</h3>";
try {
    $result = $conn->query("DESCRIBE cart_items");
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($rows as $row) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 2. Check session
echo "<h3>2. Current Session:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// 3. Check if user has profile_id
$profile_id = isset($_SESSION['profile_id']) ? $_SESSION['profile_id'] : null;
echo "<h3>3. Profile ID: " . ($profile_id ? "✅ $profile_id" : "❌ NOT SET") . "</h3>";

// 4. Check cart items in database for this user
if ($profile_id) {
    echo "<h3>4. Cart Items in Database:</h3>";
    try {
        $stmt = $conn->prepare("SELECT * FROM cart_items WHERE customer_profile_id = :profile_id");
        $stmt->execute([':profile_id' => $profile_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            echo "❌ No items in cart for profile_id $profile_id";
        } else {
            echo "✅ Found " . count($items) . " items in cart";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>cart_item_id</th><th>product_id</th><th>quantity</th><th>volume_ml</th><th>added_at</th></tr>";
            foreach ($items as $item) {
                echo "<tr>";
                foreach ($item as $val) {
                    echo "<td>" . htmlspecialchars($val) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    
    // 5. Check checkout total calculation
    echo "<h3>5. Checkout Total Calculation:</h3>";
    try {
        $total_query = "SELECT SUM(
                            p.price * ci.quantity + 
                            (CASE WHEN ci.volume_ml = 100 THEN 50.00 * ci.quantity ELSE 0 END)
                        ) as cart_total
                        FROM cart_items ci
                        JOIN products p ON ci.product_id = p.p_id
                        WHERE ci.customer_profile_id = :profile_id";
        
        $total_stmt = $conn->prepare($total_query);
        $total_stmt->execute([':profile_id' => $profile_id]);
        $total_row = $total_stmt->fetch(PDO::FETCH_ASSOC);
        $cart_total = $total_row['cart_total'];
        
        echo "<p>Cart Total: " . ($cart_total ? "<strong>$$cart_total</strong>" : "<strong>$0 (EMPTY!)</strong>") . "</p>";
        
        // Also check custom items
        $custom_total_query = "SELECT SUM(custom_price) as custom_total FROM custom_cart_items WHERE customer_profile_id = :profile_id";
        $custom_stmt = $conn->prepare($custom_total_query);
        $custom_stmt->execute([':profile_id' => $profile_id]);
        $custom_row = $custom_stmt->fetch(PDO::FETCH_ASSOC);
        $custom_total = $custom_row['custom_total'] ? $custom_row['custom_total'] : 0;
        
        echo "<p>Custom Total: <strong>$$custom_total</strong></p>";
        echo "<p>COMBINED TOTAL: <strong>$" . ($cart_total + $custom_total) . "</strong></p>";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// 6. Check custom cart items
echo "<h3>6. Custom Cart Items:</h3>";
if ($profile_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM custom_cart_items WHERE customer_profile_id = :profile_id");
        $stmt->execute([':profile_id' => $profile_id]);
        $custom = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Count: " . count($custom) . "</p>";
        if (!empty($custom)) {
            echo "<pre>";
            print_r($custom);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// 7. Check session cart for non-logged-in users
echo "<h3>7. Session Cart:</h3>";
$session_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$session_custom = isset($_SESSION['custom_cart']) ? $_SESSION['custom_cart'] : [];
echo "<p>Session cart items: " . count($session_cart) . "</p>";
echo "<p>Session custom items: " . count($session_custom) . "</p>";
if (!empty($session_cart)) {
    echo "<pre>";
    print_r($session_cart);
    echo "</pre>";
}
?>
