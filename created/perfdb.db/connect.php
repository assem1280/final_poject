<?php
// db.php - mysqli OOP with error handling and charset
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // ضع كلمة المرور لو عندك
$db   = 'perfume-db';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    // أثناء التطوير يمكنك إظهار الخطأ؛ في الإنتاج أعرض رسالة عامة أو سجّل الخطأ فقط
    die('Connection failed: ' . $conn->connect_error);
}

// ضبط الترميز لدعم العربية و Unicode
$conn->set_charset('utf8mb4');