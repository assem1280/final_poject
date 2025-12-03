<?php
// Update product details (name, price, stock, and optionally image)
header('Content-Type: application/json');
session_start();

// Verify admin authentication
if (!isset($_SESSION['profile_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied - admin only']);
    exit;
}

require_once 'connect.php';

try {
    // Validate input
    if (!isset($_POST['p_id']) || !isset($_POST['p_name']) || !isset($_POST['price']) || !isset($_POST['stock'])) {
        throw new Exception('Missing required fields');
    }
    
    $p_id = intval($_POST['p_id']);
    $p_name = trim($_POST['p_name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    
    // Validate data
    if (empty($p_name)) {
        throw new Exception('Product name cannot be empty');
    }
    if ($price < 0) {
        throw new Exception('Price cannot be negative');
    }
    if ($stock < 0) {
        throw new Exception('Stock cannot be negative');
    }
    
    // Handle image upload if provided
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Validate file
        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds maximum of 5MB');
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = '../images/perfumes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'perfume_' . $p_id . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload image file');
        }
        
        // Delete old image if it exists and is not the default
        $getOldImageQuery = "SELECT image_url FROM products WHERE p_id = :p_id";
        $getOldImageStmt = $conn->prepare($getOldImageQuery);
        $getOldImageStmt->execute([':p_id' => $p_id]);
        $oldImageRow = $getOldImageStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oldImageRow && !empty($oldImageRow['image_url'])) {
            $oldImagePath = '../' . $oldImageRow['image_url'];
            if (file_exists($oldImagePath) && $oldImageRow['image_url'] !== 'images/perfumes/default-perfume.jpg') {
                unlink($oldImagePath);
            }
        }
        
        // Store relative path for database
        $imageUrl = 'images/perfumes/' . $fileName;
    }
    
    // Update product
    if ($imageUrl) {
        $query = "UPDATE products SET p_name = :name, price = :price, stock = :stock, image_url = :image_url WHERE p_id = :p_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':name' => $p_name,
            ':price' => $price,
            ':stock' => $stock,
            ':image_url' => $imageUrl,
            ':p_id' => $p_id
        ]);
    } else {
        $query = "UPDATE products SET p_name = :name, price = :price, stock = :stock WHERE p_id = :p_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':name' => $p_name,
            ':price' => $price,
            ':stock' => $stock,
            ':p_id' => $p_id
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
