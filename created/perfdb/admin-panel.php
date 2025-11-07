<?php
// تضمين ملف الاتصال
require_once 'connect.php';

// معالجة الإجراءات (إضافة، تعديل، حذف)
$message = '';
$messageType = '';

// إضافة عطر جديد
if (isset($_POST['add_perfume'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $brand = $conn->real_escape_string($_POST['brand']);
    $category = $conn->real_escape_string($_POST['category']);
    $price = floatval($_POST['price']);
    $image = $conn->real_escape_string($_POST['image']);
    
    $sql = "INSERT INTO perfumes (name, brand, category, price, image) VALUES ('$name', '$brand', '$category', $price, '$image')";
    
    if ($conn->query($sql)) {
        $message = "تم إضافة العطر بنجاح!";
        $messageType = "success";
    } else {
        $message = "خطأ: " . $conn->error;
        $messageType = "error";
    }
}

// حذف عطر
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM perfumes WHERE id = $id";
    
    if ($conn->query($sql)) {
        $message = "تم حذف العطر بنجاح!";
        $messageType = "success";
    } else {
        $message = "خطأ: " . $conn->error;
        $messageType = "error";
    }
}

// تعديل عطر
if (isset($_POST['update_perfume'])) {
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $brand = $conn->real_escape_string($_POST['brand']);
    $category = $conn->real_escape_string($_POST['category']);
    $price = floatval($_POST['price']);
    $image = $conn->real_escape_string($_POST['image']);
    
    $sql = "UPDATE perfumes SET name='$name', brand='$brand', category='$category', price=$price, image='$image' WHERE id=$id";
    
    if ($conn->query($sql)) {
        $message = "تم تحديث العطر بنجاح!";
        $messageType = "success";
    } else {
        $message = "خطأ: " . $conn->error;
        $messageType = "error";
    }
}

// جلب جميع العطور
$perfumes = $conn->query("SELECT * FROM perfumes ORDER BY id DESC");

// جلب الإحصائيات
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM perfumes")->fetch_assoc()['count'];
$stats['man'] = $conn->query("SELECT COUNT(*) as count FROM perfumes WHERE category='man'")->fetch_assoc()['count'];
$stats['woman'] = $conn->query("SELECT COUNT(*) as count FROM perfumes WHERE category='woman'")->fetch_assoc()['count'];
$stats['unisex'] = $conn->query("SELECT COUNT(*) as count FROM perfumes WHERE category='unisex'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم العطور - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            color: #fff;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%);
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
        }

        .header h1 {
            color: #000;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .header p {
            color: #333;
            font-size: 16px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #D4AF37;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
        }

        .stat-card h3 {
            color: #D4AF37;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 48px;
            font-weight: bold;
            color: #fff;
        }

        .message {
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background: rgba(76, 175, 80, 0.2);
            border: 2px solid #4CAF50;
            color: #4CAF50;
        }

        .message.error {
            background: rgba(244, 67, 54, 0.2);
            border: 2px solid #f44336;
            color: #f44336;
        }

        .section {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .section h2 {
            color: #D4AF37;
            margin-bottom: 20px;
            font-size: 24px;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            padding-bottom: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #D4AF37;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 12px 15px;
            border: 2px solid rgba(212, 175, 55, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #D4AF37;
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group select option {
            background: #1a1a1a;
            color: #fff;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%);
            color: #000;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #e91e63 100%);
            color: #fff;
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
        }

        .btn-edit {
            background: linear-gradient(135deg, #2196F3 0%, #03A9F4 100%);
            color: #fff;
            padding: 8px 15px;
            font-size: 14px;
            margin-left: 10px;
        }

        .btn-edit:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: rgba(212, 175, 55, 0.2);
            color: #D4AF37;
            padding: 15px;
            text-align: right;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.man {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
            border: 1px solid #2196F3;
        }

        .badge.woman {
            background: rgba(233, 30, 99, 0.2);
            color: #e91e63;
            border: 1px solid #e91e63;
        }

        .badge.unisex {
            background: rgba(156, 39, 176, 0.2);
            color: #9C27B0;
            border: 1px solid #9C27B0;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #1a1a1a;
            border: 2px solid #D4AF37;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
        }

        .modal-header h3 {
            color: #D4AF37;
            font-size: 24px;
        }

        .close-modal {
            background: none;
            border: none;
            color: #fff;
            font-size: 28px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            color: #D4AF37;
            transform: rotate(90deg);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .header h1 {
                font-size: 24px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌟 لوحة تحكم العطور 🌟</h1>
            <p>إدارة قاعدة بيانات العطور - Perfume Database Admin Panel</p>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- الإحصائيات -->
        <div class="stats">
            <div class="stat-card">
                <h3>إجمالي العطور</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <h3>رجالي</h3>
                <div class="number"><?php echo $stats['man']; ?></div>
            </div>
            <div class="stat-card">
                <h3>نسائي</h3>
                <div class="number"><?php echo $stats['woman']; ?></div>
            </div>
            <div class="stat-card">
                <h3>للجنسين</h3>
                <div class="number"><?php echo $stats['unisex']; ?></div>
            </div>
        </div>

        <!-- نموذج إضافة عطر جديد -->
        <div class="section">
            <h2>➕ إضافة عطر جديد</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>اسم العطر</label>
                        <input type="text" name="name" required placeholder="مثال: Blue de Chanel">
                    </div>
                    <div class="form-group">
                        <label>العلامة التجارية</label>
                        <select name="brand" required>
                            <option value="">اختر العلامة التجارية</option>
                            <option value="chanel">CHANEL</option>
                            <option value="dior">DIOR</option>
                            <option value="tomford">TOM FORD</option>
                            <option value="versace">VERSACE</option>
                            <option value="hermes">HERMÈS</option>
                            <option value="gucci">GUCCI</option>
                            <option value="pacorabanne">PACO RABANNE</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الفئة</label>
                        <select name="category" required>
                            <option value="">اختر الفئة</option>
                            <option value="man">رجالي (Man)</option>
                            <option value="woman">نسائي (Woman)</option>
                            <option value="unisex">للجنسين (Unisex)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>السعر ($)</label>
                        <input type="number" name="price" step="0.01" required placeholder="120.00">
                    </div>
                    <div class="form-group">
                        <label>اسم الصورة</label>
                        <input type="text" name="image" required placeholder="perfume1.jpg">
                    </div>
                </div>
                <button type="submit" name="add_perfume" class="btn btn-primary">إضافة العطر</button>
            </form>
        </div>

        <!-- جدول العطور -->
        <div class="section">
            <h2>📋 قائمة العطور</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>اسم العطر</th>
                            <th>العلامة التجارية</th>
                            <th>الفئة</th>
                            <th>السعر</th>
                            <th>الصورة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $perfumes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo strtoupper($row['brand']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['category']; ?>">
                                    <?php 
                                    echo $row['category'] == 'man' ? 'رجالي' : 
                                         ($row['category'] == 'woman' ? 'نسائي' : 'للجنسين'); 
                                    ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['image']); ?></td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-edit" onclick="editPerfume(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        تعديل
                                    </button>
                                    <a href="?delete=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('هل أنت متأكد من حذف هذا العطر؟')">
                                        حذف
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- نافذة التعديل -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ تعديل العطر</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label>اسم العطر</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label>العلامة التجارية</label>
                        <select name="brand" id="edit_brand" required>
                            <option value="chanel">CHANEL</option>
                            <option value="dior">DIOR</option>
                            <option value="tomford">TOM FORD</option>
                            <option value="versace">VERSACE</option>
                            <option value="hermes">HERMÈS</option>
                            <option value="gucci">GUCCI</option>
                            <option value="pacorabanne">PACO RABANNE</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الفئة</label>
                        <select name="category" id="edit_category" required>
                            <option value="man">رجالي (Man)</option>
                            <option value="woman">نسائي (Woman)</option>
                            <option value="unisex">للجنسين (Unisex)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>السعر ($)</label>
                        <input type="number" name="price" id="edit_price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>اسم الصورة</label>
                        <input type="text" name="image" id="edit_image" required>
                    </div>
                </div>
                <button type="submit" name="update_perfume" class="btn btn-primary">حفظ التعديلات</button>
            </form>
        </div>
    </div>

    <script>
        function editPerfume(perfume) {
            document.getElementById('edit_id').value = perfume.id;
            document.getElementById('edit_name').value = perfume.name;
            document.getElementById('edit_brand').value = perfume.brand;
            document.getElementById('edit_category').value = perfume.category;
            document.getElementById('edit_price').value = perfume.price;
            document.getElementById('edit_image').value = perfume.image;
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // إغلاق النافذة عند الضغط خارجها
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
