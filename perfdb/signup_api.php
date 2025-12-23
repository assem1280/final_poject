<?php
// Customer/User Registration API
// Allows registration of new customers or employees based on their role
header('Content-Type: application/json');
session_start();
require_once 'connect.php';
require_once __DIR__ . '/../email/EmailNotification.php';
require_once __DIR__ . '/../email/EmailTemplates.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Wrap entire logic in try-catch to ensure JSON response
try {
    // الحصول على البيانات المرسلة
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);

    // Debug logging
    error_log("Signup API received: " . $raw_input);

// التحقق من البيانات المطلوبة
$first_name = isset($input['first_name']) ? trim($input['first_name']) : '';
$last_name = isset($input['last_name']) ? trim($input['last_name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$address = isset($input['address']) ? trim($input['address']) : '';
$role = isset($input['role']) ? trim($input['role']) : 'customer'; // customer, employee, admin

// Debug log extracted values
error_log("Extracted phone: '" . $phone . "' | address: '" . $address . "'");

// التحقق من صحة البيانات
if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    throw new Exception('جميع الحقول المطلوبة');
}

// التحقق من صيغة البريد الإلكتروني
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('The email is incorrect');
}

// التحقق من طول كلمة المرور
if (strlen($password) < 6) {
    throw new Exception('The password must be at least 6 characters long');
}

// السماح فقط بأدوار معينة
$allowed_roles = ['customer', 'employee', 'admin'];
if (!in_array($role, $allowed_roles)) {
    $role = 'customer'; // الدور الافتراضي
}

// التحقق من عدم وجود بريد إلكتروني مكرر
$check_query = "SELECT profile_id FROM profiles WHERE email = :email";
$check_stmt = $conn->prepare($check_query);
$check_stmt->execute([':email' => $email]);

if ($check_stmt->rowCount() > 0) {
    throw new Exception('The email is already registered');
}

// التحقق من رقم الهاتف - يجب أن يكون 8 أرقام فقط
if (!preg_match('/^\d{8}$/', $phone)) {
    throw new Exception('The number is incorrect, please try again.');
}

// التحقق من عدم وجود رقم هاتف مكرر
$check_phone_query = "SELECT profile_id FROM profiles WHERE phone = :phone";
$check_phone_stmt = $conn->prepare($check_phone_query);
$check_phone_stmt->execute([':phone' => $phone]);

if ($check_phone_stmt->rowCount() > 0) {
    throw new Exception('The number is incorrect, please try again.');
}
    
    // تشفير كلمة المرور
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Debug: log the exact values being inserted
    error_log("About to insert - Phone: '$phone' | Address: '$address'");
    
    // إدراج المستخدم الجديد
    $insert_query = "INSERT INTO profiles 
        (first_name, last_name, email, password, role, phone, address, created_at) 
        VALUES 
        (:first_name, :last_name, :email, :password, :role, :phone, :address, NOW())";
    
    $insert_stmt = $conn->prepare($insert_query);
    
    $execute_params = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'password' => $hashed_password,
        'role' => $role,
        'phone' => $phone,
        'address' => $address
    ];
    
    error_log("Execute params: " . json_encode($execute_params));
    
    $insert_stmt->execute($execute_params);
    
    $profile_id = $conn->lastInsertId();
    
    // Auto-login the new user
    $_SESSION['profile_id'] = $profile_id;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    $_SESSION['phone'] = $phone;
    $_SESSION['address'] = $address;
    
    // IMPORTANT: Migrate session cart items to database
    // When user adds items as guest, they're in $_SESSION['cart'] with composite keys like "product_id_volume"
    // Now that we have a profile_id, move them to cart_items table
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
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
                
                // Check if item already exists in cart_items for this profile
                $check_query = "SELECT cart_item_id FROM cart_items 
                              WHERE customer_profile_id = :profile_id AND product_id = :product_id AND volume_ml = :volume_ml";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->execute([':profile_id' => $profile_id, ':product_id' => $product_id, ':volume_ml' => $volume_ml]);
                
                if ($check_stmt->rowCount() > 0) {
                    // Update existing item
                    $update_query = "UPDATE cart_items SET quantity = quantity + :quantity 
                                    WHERE customer_profile_id = :profile_id AND product_id = :product_id AND volume_ml = :volume_ml";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->execute([':profile_id' => $profile_id, ':product_id' => $product_id, ':quantity' => $quantity, ':volume_ml' => $volume_ml]);
                } else {
                    // Insert new item with correct volume
                    $insert_query = "INSERT INTO cart_items (customer_profile_id, product_id, quantity, volume_ml) 
                                    VALUES (:profile_id, :product_id, :quantity, :volume_ml)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->execute([':profile_id' => $profile_id, ':product_id' => $product_id, ':quantity' => $quantity, ':volume_ml' => $volume_ml]);
                }
            }
            error_log("Migrated " . count($_SESSION['cart']) . " items from session cart to database");
        } catch (Exception $e) {
            error_log("Error migrating cart: " . $e->getMessage());
            // Don't fail signup if migration fails
        }
    }
    
    // Also migrate custom cart items if any
    if (isset($_SESSION['custom_cart']) && is_array($_SESSION['custom_cart']) && !empty($_SESSION['custom_cart'])) {
        try {
            foreach ($_SESSION['custom_cart'] as $custom_item) {
                // Extract values from session cart
                $bottle_design_id = $custom_item['bottle_design_id'] ?? null;
                $bottle_size = $custom_item['bottle_size'] ?? 50; // Default to 50ml if not specified
                $total_ml = $custom_item['total_ml'] ?? 0;
                $total_price = $custom_item['total_price'] ?? 0;
                
                // Insert custom cart item with proper bottle_size reference
                $insert_query = "INSERT INTO custom_cart_items 
                                (customer_profile_id, bottle_design_id, oil_amount_grams, custom_price) 
                                VALUES (:profile_id, :bottle_id, :oil_grams, :price)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->execute([
                    ':profile_id' => $profile_id,
                    ':bottle_id' => $bottle_design_id,
                    ':oil_grams' => $total_ml, // Store total_ml (fragrance volume)
                    ':price' => $total_price
                ]);
                
                $custom_id = $conn->lastInsertId();
                
                // Store bottle_size info - we'll save it as a record in a custom_cart_bottles table if needed
                // For now, since bottle_design_id is set, we can retrieve size from bottle_designs table
                
                // Migrate scent types/products with proper percentages
                if (isset($custom_item['products']) && is_array($custom_item['products'])) {
                    foreach ($custom_item['products'] as $product) {
                        $product_id = $product['product_id'] ?? null;
                        $ml = $product['ml'] ?? 0;
                        $total_ml_calc = $custom_item['total_ml'] ?? 1; // Avoid division by zero
                        $amount_percent = ($total_ml_calc > 0) ? (($ml / $total_ml_calc) * 100) : 0;
                        
                        $type_query = "INSERT INTO custom_cart_types (custom_cart_id, type_id, amount_percent) 
                                      VALUES (:custom_id, :type_id, :percent)";
                        $type_stmt = $conn->prepare($type_query);
                        $type_stmt->execute([
                            ':custom_id' => $custom_id, 
                            ':type_id' => $product_id, 
                            ':percent' => $amount_percent
                        ]);
                    }
                }
            }
            error_log("Migrated " . count($_SESSION['custom_cart']) . " custom items from session cart to database");
        } catch (Exception $e) {
            error_log("Error migrating custom cart: " . $e->getMessage());
            // Don't fail signup if migration fails
        }
    }
    
    // Send welcome email to new user
    $emailSent = false;
    try {
        error_log("[Signup] Attempting to send welcome email to: {$email}");
        $customerName = trim($first_name . ' ' . $last_name);
        $emailTemplate = EmailTemplates::welcomeEmail($customerName, $email);
        
        $emailer = new EmailNotification();
        $emailSent = $emailer->send(
            $email,
            $customerName,
            $emailTemplate['subject'],
            $emailTemplate['html'],
            $emailTemplate['text']
        );
        
        if ($emailSent) {
            error_log("[Signup] Welcome email sent successfully to: {$email}");
        } else {
            error_log("[Signup] Failed to send welcome email: " . $emailer->getLastError());
        }
    } catch (Exception $emailError) {
        error_log("[Signup] Email error: " . $emailError->getMessage());
        // Don't fail signup if email fails
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'profile_id' => $profile_id,
        'auto_login' => true,
        'role' => $role,
        'redirect' => 'login.html',
        'email_sent' => $emailSent
    ]);
    
} catch (PDOException $e) {
    error_log("Signup Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Unexpected Signup Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Unexpected error: ' . $e->getMessage()]);
}
?>
