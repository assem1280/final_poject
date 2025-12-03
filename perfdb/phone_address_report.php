<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'connect.php';

echo "<!DOCTYPE html>
<html dir='rtl'>
<head>
    <meta charset='utf-8'>
    <title>Phone & Address Status Report</title>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { 
            background: white; 
            padding: 20px; 
            border-radius: 8px 8px 0 0;
            border-bottom: 3px solid #667eea;
        }
        .section { 
            background: white; 
            padding: 20px; 
            margin: 10px 0;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
        }
        th { 
            background: #667eea; 
            color: white; 
            padding: 12px; 
            text-align: right;
        }
        td { 
            padding: 10px 12px; 
            border-bottom: 1px solid #eee;
        }
        tr:hover { background: #f9f9f9; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .status-good { background: #e8f5e9; }
        .status-bad { background: #ffebee; }
        .summary { 
            background: #f0f4ff; 
            border-right: 4px solid #667eea; 
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
        h2 { color: #667eea; }
        h3 { color: #555; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; overflow-x: auto; }
    </style>
</head>
<body dir='rtl'>
<div class='container'>
    <div class='header'>
        <h1>📋 تقرير حالة حقول الهاتف والعنوان</h1>
        <p>Phone and Address Fields Status Report</p>
    </div>";

try {
    // Check database structure
    echo "<div class='section'>
            <h2>1️⃣ حالة قاعدة البيانات | Database Structure</h2>";
    
    $result = $conn->query('DESCRIBE profiles');
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $phone_found = false;
    $address_found = false;
    
    foreach($columns as $col) {
        if ($col['Field'] === 'phone') $phone_found = true;
        if ($col['Field'] === 'address') $address_found = true;
    }
    
    echo "<div class='summary'>";
    echo "<p>📱 Phone Column: <span class='" . ($phone_found ? 'success' : 'error') . "'>" . ($phone_found ? '✅ موجود | FOUND' : '❌ غير موجود | NOT FOUND') . "</span></p>";
    echo "<p>📍 Address Column: <span class='" . ($address_found ? 'success' : 'error') . "'>" . ($address_found ? '✅ موجود | FOUND' : '❌ غير موجود | NOT FOUND') . "</span></p>";
    echo "</div>";
    echo "</div>";
    
    // Show recent records
    echo "<div class='section'>
            <h2>2️⃣ آخر 10 حسابات مسجلة | Last 10 Registered Accounts</h2>";
    
    $data = $conn->query('SELECT profile_id, first_name, email, phone, address, created_at FROM profiles ORDER BY profile_id DESC LIMIT 10');
    $rows = $data->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>
            <tr>
                <th>الهاتف | Phone</th>
                <th>العنوان | Address</th>
                <th>البريد | Email</th>
                <th>الاسم | Name</th>
                <th>المعرف | ID</th>
            </tr>";
    
    foreach($rows as $row) {
        $phone_status = $row['phone'] ? 'status-good' : 'status-bad';
        $address_status = $row['address'] ? 'status-good' : 'status-bad';
        
        echo "<tr>";
        echo "<td class='$phone_status'>" . ($row['phone'] ? htmlspecialchars($row['phone']) : '<span class=\"error\">فارغ | EMPTY</span>') . "</td>";
        echo "<td class='$address_status'>" . ($row['address'] ? htmlspecialchars($row['address']) : '<span class=\"error\">فارغ | EMPTY</span>') . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
        echo "<td><strong>" . $row['profile_id'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Statistics
    echo "<div class='section'>
            <h2>3️⃣ الإحصائيات | Statistics</h2>";
    
    $stats = $conn->query('SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN phone IS NOT NULL AND phone != \"\" THEN 1 ELSE 0 END) as with_phone,
        SUM(CASE WHEN address IS NOT NULL AND address != \"\" THEN 1 ELSE 0 END) as with_address
        FROM profiles')->fetch(PDO::FETCH_ASSOC);
    
    $phone_percent = $stats['total'] > 0 ? round(($stats['with_phone'] / $stats['total']) * 100) : 0;
    $address_percent = $stats['total'] > 0 ? round(($stats['with_address'] / $stats['total']) * 100) : 0;
    
    echo "<div class='summary'>";
    echo "<p>✅ إجمالي الحسابات | Total Accounts: <strong>" . $stats['total'] . "</strong></p>";
    echo "<p>📱 حسابات بها هاتف | Accounts with Phone: <strong>" . $stats['with_phone'] . "</strong> (<span class='" . ($phone_percent >= 90 ? 'success' : 'warning') . "'>" . $phone_percent . "%</span>)</p>";
    echo "<p>📍 حسابات بها عنوان | Accounts with Address: <strong>" . $stats['with_address'] . "</strong> (<span class='" . ($address_percent >= 90 ? 'success' : 'warning') . "'>" . $address_percent . "%</span>)</p>";
    echo "</div>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='section'><p class='error'>⚠️ Database Error: " . htmlspecialchars($e->getMessage()) . "</p></div>";
}

echo "</div>
</body>
</html>";
?>
