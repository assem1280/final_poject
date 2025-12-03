<?php
// Test Connection File - للتحقق من الاتصال بقاعدة البيانات

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'database_status' => [],
    'errors' => []
];

// 1. اختبر اتصال MySQL
try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=perfume-db1;charset=utf8mb4",
        'root',
        '',
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
    $response['database_status']['connection'] = '✅ متصل بنجاح';
    $response['database_status']['database'] = 'perfume-db1';
    $response['database_status']['user'] = 'root';
    
    // 2. تحقق من الجداول
    $tables_query = "SHOW TABLES";
    $tables_stmt = $conn->query($tables_query);
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $response['database_status']['tables'] = $tables;
    
    // 3. تحقق من عدد الـ profiles
    $profiles_count = $conn->query("SELECT COUNT(*) FROM profiles")->fetchColumn();
    $response['database_status']['profiles_count'] = $profiles_count;
    
    // 4. تحقق من الحقول في جدول profiles
    $columns_query = "DESCRIBE profiles";
    $columns_stmt = $conn->query($columns_query);
    $columns = $columns_stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['database_status']['profiles_columns'] = $columns;
    
    $response['success'] = true;
    
} catch(PDOException $e) {
    $response['success'] = false;
    $response['errors'][] = 'Database Error: ' . $e->getMessage();
} catch(Exception $e) {
    $response['success'] = false;
    $response['errors'][] = 'Error: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
