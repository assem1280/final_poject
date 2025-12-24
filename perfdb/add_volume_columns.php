<?php
// Database schema migration for volume_ml columns
require_once 'connect.php';

try {
    echo "<h2>Database Schema Migration</h2>";
    
    // Check and add volume_ml to cart_items
    try {
        $stmt = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='cart_items' AND COLUMN_NAME='volume_ml' AND TABLE_SCHEMA='" . 'perfume-db1' . "'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo "Adding volume_ml column to cart_items...<br>";
            $conn->exec("ALTER TABLE cart_items ADD COLUMN volume_ml INT DEFAULT 50 AFTER quantity");
            echo "✓ Added volume_ml to cart_items<br>";
        } else {
            echo "✓ volume_ml already exists in cart_items<br>";
        }
    } catch (Exception $e) {
        echo "• cart_items: " . $e->getMessage() . "<br>";
    }
    
    // Check and add volume_ml to order_items
    try {
        $stmt = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='order_items' AND COLUMN_NAME='volume_ml' AND TABLE_SCHEMA='" . 'perfume-db1' . "'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo "Adding volume_ml column to order_items...<br>";
            $conn->exec("ALTER TABLE order_items ADD COLUMN volume_ml INT DEFAULT 50 AFTER quantity");
            echo "✓ Added volume_ml to order_items<br>";
        } else {
            echo "✓ volume_ml already exists in order_items<br>";
        }
    } catch (Exception $e) {
        echo "• order_items: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
    echo "<strong>✓ Database schema migration complete!</strong>";
    echo "<p>The volume_ml columns have been added to both cart_items and order_items tables.</p>";
    echo "<p>You can now <a href='../employee/dashboard.php'>return to the employee dashboard</a> or <a href='../docs/index.html'>back to home</a></p>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
