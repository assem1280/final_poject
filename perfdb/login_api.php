<?php
// Login API
// التحقق من بيانات المستخدم وإنشء جلسة
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'طريقة الطلب غير صحيحة']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'البريد والرمز مطلوبان']);
    exit;
}

try {
    // البحث عن المستخدم بالبريد الإلكتروني
    $query = "SELECT profile_id, first_name, last_name, email, password, role, phone, address FROM profiles WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'البريد الإلكتروني أو الرمز غير صحيح']);
        exit;
    }
    
    // التحقق من صحة كلمة المرور
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'البريد الإلكتروني أو الرمز غير صحيح']);
        exit;
    }
    
    // حفظ البيانات في الجلسة
    $_SESSION['profile_id'] = $user['profile_id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['address'] = $user['address'];
    
    // Migrate session cart to database after login
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        try {
            foreach ($_SESSION['cart'] as $key => $quantity) {
                // Parse composite key: "product_id_volume"
                $parts = explode('_', $key);
                if (count($parts) === 2) {
                    $product_id = intval($parts[0]);
                    $volume_ml = intval($parts[1]);
                } else {
                    // Fallback for old format (just product_id)
                    $product_id = intval($key);
                    $volume_ml = 50; // Default to 50ml
                }
                
                // Check if product with this volume already in user's cart
                $check_query = "SELECT cart_item_id FROM cart_items 
                               WHERE customer_profile_id = :profile_id 
                               AND product_id = :product_id 
                               AND volume_ml = :volume_ml";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->execute([
                    ':profile_id' => $user['profile_id'],
                    ':product_id' => $product_id,
                    ':volume_ml' => $volume_ml
                ]);
                
                if ($check_stmt->rowCount() > 0) {
                    // Update quantity if product+volume exists
                    $update_query = "UPDATE cart_items 
                                    SET quantity = quantity + :qty 
                                    WHERE customer_profile_id = :profile_id 
                                    AND product_id = :product_id 
                                    AND volume_ml = :volume_ml";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->execute([
                        ':qty' => $quantity,
                        ':profile_id' => $user['profile_id'],
                        ':product_id' => $product_id,
                        ':volume_ml' => $volume_ml
                    ]);
                } else {
                    // Add new product to cart
                    $insert_query = "INSERT INTO cart_items 
                                    (customer_profile_id, product_id, quantity, volume_ml) 
                                    VALUES (:profile_id, :product_id, :qty, :volume_ml)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->execute([
                        ':profile_id' => $user['profile_id'],
                        ':product_id' => $product_id,
                        ':qty' => $quantity,
                        ':volume_ml' => $volume_ml
                    ]);
                }
            }
            // Clear session cart after migration
            unset($_SESSION['cart']);
        } catch (PDOException $e) {
            error_log("Cart migration error: " . $e->getMessage());
        }
    }
    
    // Migrate custom perfume cart to database after login
    // Support both legacy key `session_custom` and new `custom_cart` used by create page
    $guestCustomKey = null;
    if (isset($_SESSION['session_custom']) && !empty($_SESSION['session_custom'])) {
        $guestCustomKey = 'session_custom';
    } elseif (isset($_SESSION['custom_cart']) && !empty($_SESSION['custom_cart'])) {
        $guestCustomKey = 'custom_cart';
    }

    if ($guestCustomKey) {
        try {
            foreach ($_SESSION[$guestCustomKey] as $custom_item) {
                // Normalize fields from either structure
                $bottle_id = isset($custom_item['bottle_design_id']) ? $custom_item['bottle_design_id'] : (isset($custom_item['bottle_id']) ? $custom_item['bottle_id'] : 1);
                $oil_grams = isset($custom_item['oil_amount_grams']) ? $custom_item['oil_amount_grams'] : (isset($custom_item['total_ml']) ? $custom_item['total_ml'] : 0);
                $price = isset($custom_item['custom_price']) ? $custom_item['custom_price'] : (isset($custom_item['total_price']) ? $custom_item['total_price'] : 0);

                // Insert custom perfume into database
                $insert_query = "INSERT INTO custom_cart_items 
                                (customer_profile_id, bottle_design_id, oil_amount_grams, custom_price) 
                                VALUES (:profile_id, :bottle_id, :oil_grams, :price)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->execute([
                    ':profile_id' => $user['profile_id'],
                    ':bottle_id' => $bottle_id,
                    ':oil_grams' => $oil_grams,
                    ':price' => $price
                ]);

                $custom_id = $conn->lastInsertId();

                // Copy the types if they exist (support both 'types' or 'products' structures)
                if (isset($custom_item['types']) && !empty($custom_item['types'])) {
                    foreach ($custom_item['types'] as $type_item) {
                        $type_insert = "INSERT INTO custom_cart_types 
                                       (custom_cart_id, type_id, amount_percent) 
                                       VALUES (:cart_id, :type_id, :percent)";
                        $type_stmt = $conn->prepare($type_insert);
                        $type_stmt->execute([
                            ':cart_id' => $custom_id,
                            ':type_id' => $type_item['type_id'],
                            ':percent' => $type_item['amount_percent']
                        ]);
                    }
                } elseif (isset($custom_item['products']) && !empty($custom_item['products']) && $oil_grams > 0) {
                    foreach ($custom_item['products'] as $p) {
                        $type_id = isset($p['product_id']) ? intval($p['product_id']) : (isset($p['p_id']) ? intval($p['p_id']) : 0);
                        $ml = isset($p['ml']) ? floatval($p['ml']) : 0;
                        $percent = ($oil_grams > 0) ? (($ml / $oil_grams) * 100) : 0;
                        $type_insert = "INSERT INTO custom_cart_types 
                                       (custom_cart_id, type_id, amount_percent) 
                                       VALUES (:cart_id, :type_id, :percent)";
                        $type_stmt = $conn->prepare($type_insert);
                        $type_stmt->execute([
                            ':cart_id' => $custom_id,
                            ':type_id' => $type_id,
                            ':percent' => $percent
                        ]);
                    }
                }
            }
            // Clear session custom cart after migration
            unset($_SESSION[$guestCustomKey]);
        } catch (PDOException $e) {
            error_log("Custom cart migration error: " . $e->getMessage());
        }
    }
    
    // تحديد الصفحة المناسبة بناءً على الدور
    $redirect_url = 'index.html';
    if ($user['role'] === 'admin') {
        $redirect_url = '../admin/dashboard.php';
    } elseif ($user['role'] === 'employee') {
        $redirect_url = '../employee/dashboard.php';
    } elseif ($user['role'] === 'customer') {
        $redirect_url = '../customer/dashboard.php';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => [
            'profile_id' => $user['profile_id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role']
        ],
        'redirect' => $redirect_url
    ]);
    
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'حدث خطأ في الخادم: ' . $e->getMessage()]);
}
?>
