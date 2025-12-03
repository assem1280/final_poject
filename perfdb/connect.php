<?php
// Database connection configuration for perfume-db1
// XAMPP default settings

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'perfume-db1');

try {
    // Create PDO connection
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch(PDOException $e) {
    // Log error but don't die - let the page handle it
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Check if this is an API call (JSON response needed)
    if (strpos($_SERVER['REQUEST_URI'], '/perfdb/') !== false || 
        (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        // For API calls, return JSON error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection error']);
        exit;
    } elseif (strpos($_SERVER['PHP_SELF'], 'dashboard') !== false || strpos($_SERVER['PHP_SELF'], 'admin') !== false) {
        // For dashboard pages, show error message
        die("<div style='text-align:center; padding: 50px; font-family: Arial; color: #d32f2f;'>
                <h2>حدث خطأ في الاتصال بالسيرفر</h2>
                <p>تأكد من:</p>
                <ul style='text-align: right;'>
                    <li>تشغيل MySQL من XAMPP Control Panel</li>
                    <li>وجود قاعدة البيانات perfume-db1</li>
                </ul>
                <p>الرسالة الخطأ: " . htmlspecialchars($e->getMessage()) . "</p>
            </div>");
    } else {
        // For other pages, just log the error
        $conn = null;
    }
}
?>
