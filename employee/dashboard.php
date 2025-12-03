<?php
// Employee Dashboard
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 86400); // 24 hours
session_start();

// Verify login - check if session exists and is valid
if (!isset($_SESSION['profile_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../indexed/login.html');
    exit;
}

// Refresh session cookie to keep it alive
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), $_COOKIE[session_name()], time() + 86400, '/');
}

require_once '../perfdb/connect.php';

$profile_id = $_SESSION['profile_id'];

// System Statistics
// Total Orders
$total_orders_query = "SELECT COUNT(*) as total FROM orders";
$total_orders_stmt = $conn->prepare($total_orders_query);
$total_orders_stmt->execute();
$total_orders_result = $total_orders_stmt->fetch(PDO::FETCH_ASSOC);
$total_orders = $total_orders_result['total'];

// Total Revenue
$total_revenue_query = "SELECT SUM(total_amount) as revenue FROM orders";
$total_revenue_stmt = $conn->prepare($total_revenue_query);
$total_revenue_stmt->execute();
$total_revenue_result = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC);
$total_revenue = $total_revenue_result['revenue'] ?? 0;

// Total Customers
$total_customers_query = "SELECT COUNT(*) as total FROM profiles WHERE role = 'customer'";
$total_customers_stmt = $conn->prepare($total_customers_query);
$total_customers_stmt->execute();
$total_customers_result = $total_customers_stmt->fetch(PDO::FETCH_ASSOC);
$total_customers = $total_customers_result['total'];

// Pending Orders
$pending_orders_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
$pending_orders_stmt = $conn->prepare($pending_orders_query);
$pending_orders_stmt->execute();
$pending_orders_result = $pending_orders_stmt->fetch(PDO::FETCH_ASSOC);
$pending_orders = $pending_orders_result['total'];

// Completed Orders
$completed_orders_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'completed'";
$completed_orders_stmt = $conn->prepare($completed_orders_query);
$completed_orders_stmt->execute();
$completed_orders_result = $completed_orders_stmt->fetch(PDO::FETCH_ASSOC);
$completed_orders = $completed_orders_result['total'];

// Last 20 orders with details
$orders_query = "SELECT o.order_id, o.customer_profile_id, o.total_amount, o.status, o.created_at, 
                 p.first_name, p.last_name, p.email, p.phone, p.address
                 FROM orders o 
                 JOIN profiles p ON o.customer_profile_id = p.profile_id 
                 ORDER BY o.created_at DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->execute();
$recent_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get order items
function getOrderItems($conn, $order_id) {
    $query = "SELECT oi.*, p.p_name, p.price, oi.volume_ml FROM order_items oi
              LEFT JOIN products p ON oi.p_id = p.p_id
              WHERE oi.order_id = :order_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':order_id' => $order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get custom perfumes
function getCustomPerfumes($conn, $order_id) {
    $query = "SELECT cp.*, 
              GROUP_CONCAT(CONCAT(cpt.type_id, ':', cpt.amount_percent) SEPARATOR ',') as types
              FROM custom_perfumes cp
              LEFT JOIN custom_perfume_types cpt ON cp.custom_id = cpt.custom_id
              WHERE cp.order_id = :order_id
              GROUP BY cp.custom_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':order_id' => $order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            direction: ltr;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .header-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .user-display {
            color: #666;
        }
        
        .user-display strong {
            color: #333;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        .statistics {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-right: 3px solid #3498db;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 6px;
            font-weight: 600;
        }
        
        .stat-card .value {
            color: #333;
            font-size: 20px;
            font-weight: bold;
        }
        
        .stat-card.orange {
            border-right-color: #f39c12;
        }
        
        .stat-card.orange .value {
            color: #f39c12;
        }
        
        .stat-card.green {
            border-right-color: #27ae60;
        }
        
        .stat-card.green .value {
            color: #27ae60;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .orders-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .orders-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table thead {
            background: #f8f9fa;
        }
        
        .orders-table th {
            color: #333;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }
        
        .orders-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .customer-link {
            color: #3498db;
            text-decoration: none;
            cursor: pointer;
        }
        
        /* Additional styles for expandable rows */
        .orders-table tbody tr.order-row {
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .orders-table tbody tr.order-row:hover {
            background: #f0f7ff;
        }
        
        .expand-icon {
            display: inline-block;
            transition: transform 0.3s;
        }
        
        .expand-icon.expanded {
            transform: rotate(180deg);
        }
        
        .details-row {
            display: none;
        }
        
        .details-row.show {
            display: table-row;
        }
        
        .details-content {
            padding: 15px 20px;
            background: #f9f9f9;
        }
        
        .order-details-box {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #3498db;
        }
        
        .detail-section {
            margin-bottom: 20px;
        }
        
        .detail-section h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .customer-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            font-size: 13px;
        }
        
        .customer-info-item {
            background: #f0f0f0;
            padding: 8px;
            border-radius: 4px;
        }
        
        .customer-info-label {
            font-weight: 600;
            color: #555;
        }
        
        .customer-info-value {
            color: #333;
        }
        
        .items-list {
            background: white;
            border-radius: 6px;
        }
        
        .item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 500;
            color: #333;
        }
        
        .item-qty {
            color: #666;
            font-size: 12px;
        }
        
        .item-price {
            color: #27ae60;
            font-weight: 600;
        }
        
        .custom-item {
            background: #fff8f0;
            border-right: 3px solid #f39c12;
        }
        
        .custom-item .item-name::before {
            content: '🎨 ';
        }
        
        /* Status Update Section */
        .status-update-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 8px;
            color: white;
            margin-top: 20px;
        }
        
        .status-update-section h4 {
            color: white;
            margin-bottom: 15px;
            font-size: 16px;
            text-transform: uppercase;
        }
        
        .status-control {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #FFD700;
        }
        
        .checkbox-wrapper label {
            cursor: pointer;
            font-weight: 500;
            user-select: none;
        }
        
        .update-status-btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .update-status-btn:hover {
            background: #FFD700;
            color: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .update-status-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .status-loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .statistics {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .orders-table {
                font-size: 14px;
            }
            
            .orders-table th,
            .orders-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="header">
            <h1>👨‍💼 Employee Dashboard</h1>
            <div class="header-info">
                <div class="user-display">
                    Hello, <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong>
                </div>
                <button class="logout-btn" onclick="logout()">Logout</button>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="statistics">
            <div class="stat-card" style="cursor: pointer;" onclick="showAllOrders()">
                <h3>Total Orders</h3>
                <div class="value"><?php echo $total_orders; ?></div>
            </div>
            <div class="stat-card" style="border-right-color: #e74c3c; cursor: pointer;" onclick="filterByStatus('pending')">
                <h3>Pending Orders</h3>
                <div class="value" style="color: #e74c3c;"><?php echo $pending_orders; ?></div>
            </div>
            <div class="stat-card" style="border-right-color: #27ae60; cursor: pointer;" onclick="filterByStatus('completed')">
                <h3>Completed Orders</h3>
                <div class="value" style="color: #27ae60;"><?php echo $completed_orders; ?></div>
            </div>
            <div class="stat-card orange">
                <h3>Total Revenue</h3>
                <div class="value">$<?php echo number_format($total_revenue, 2); ?></div>
            </div>
            <div class="stat-card green">
                <h3>Total Customers</h3>
                <div class="value"><?php echo $total_customers; ?></div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="orders-section">
            <h2>📦 Recent Orders</h2>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr class="order-row" onclick="toggleOrderDetails(<?php echo $order['order_id']; ?>)">
                            <td>
                                <span class="expand-icon" id="icon-<?php echo $order['order_id']; ?>">▼</span>
                                #<?php echo $order['order_id']; ?>
                            </td>
                            <td>
                                <span class="customer-link">
                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                        $status_en = [
                                            'pending' => 'Pending',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled'
                                        ];
                                        echo $status_en[$order['status']] ?? $order['status'];
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="details-row" id="details-<?php echo $order['order_id']; ?>">
                            <td colspan="5">
                                <div class="details-content">
                                    <div class="order-details-box">
                                        <!-- Customer Information -->
                                        <div class="detail-section">
                                            <h4>📞 Contact Information</h4>
                                            <div class="customer-info">
                                                <div class="customer-info-item">
                                                    <div class="customer-info-label">Email:</div>
                                                    <div class="customer-info-value"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div class="customer-info-item">
                                                    <div class="customer-info-label">Phone:</div>
                                                    <div class="customer-info-value"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div class="customer-info-item">
                                                    <div class="customer-info-label">Address:</div>
                                                    <div class="customer-info-value" style="grid-column: 1/-1;"><?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Ordered Products -->
                                        <div class="detail-section">
                                            <h4>🛍️ Ordered Products</h4>
                                            <div class="items-list" id="items-<?php echo $order['order_id']; ?>">
                                                <div style="padding: 10px; color: #999; text-align: center;">
                                                    Loading...
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Custom Mix -->
                                        <div class="detail-section" id="custom-section-<?php echo $order['order_id']; ?>" style="display: none;">
                                            <h4>🎨 Custom Mix</h4>
                                            <div class="items-list" id="custom-<?php echo $order['order_id']; ?>">
                                                <div style="padding: 10px; color: #999; text-align: center;">
                                                    Loading...
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Update Section -->
                                        <div class="status-update-section">
                                            <h4>✅ Mark as Ready</h4>
                                            <div class="status-control">
                                                <div class="checkbox-wrapper">
                                                    <input type="checkbox" id="complete-<?php echo $order['order_id']; ?>" 
                                                           <?php echo $order['status'] === 'completed' ? 'checked' : ''; ?>>
                                                    <label for="complete-<?php echo $order['order_id']; ?>">Mark product as ready for pickup</label>
                                                </div>
                                                <button class="update-status-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>)">
                                                    Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Track which orders are expanded
        const expandedOrders = {};

        function toggleOrderDetails(orderId) {
            const detailsRow = document.getElementById(`details-${orderId}`);
            const icon = document.getElementById(`icon-${orderId}`);
            
            if (!expandedOrders[orderId]) {
                // Expand - fetch and show details
                expandedOrders[orderId] = true;
                detailsRow.classList.add('show');
                icon.classList.add('expanded');
                
                fetchOrderDetails(orderId);
            } else {
                // Collapse
                expandedOrders[orderId] = false;
                detailsRow.classList.remove('show');
                icon.classList.remove('expanded');
            }
        }

        function fetchOrderDetails(orderId) {
            // Fetch order items
            fetch(`../perfdb/get_order_details.php?order_id=${orderId}`)
                .then(r => {
                    if (!r.ok) {
                        if (r.status === 401) {
                            throw new Error('Session expired - please login again');
                        }
                        if (r.status === 403) {
                            throw new Error('Access denied - employee access required');
                        }
                        throw new Error(`HTTP ${r.status}`);
                    }
                    return r.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    if (data.items) {
                        displayOrderItems(orderId, data.items);
                    } else {
                        document.getElementById(`items-${orderId}`).innerHTML = 
                            '<div style="padding: 10px; color: #999;">No products</div>';
                    }
                    
                    if (data.custom && data.custom.length > 0) {
                        displayCustomItems(orderId, data.custom);
                    }
                })
                .catch(err => {
                    console.error('Error fetching order details:', err);
                    const itemsContainer = document.getElementById(`items-${orderId}`);
                    if (itemsContainer) {
                        itemsContainer.innerHTML = 
                            '<div style="padding: 10px; color: #e74c3c;">❌ Error: ' + err.message + '</div>';
                    }
                });
        }

        function displayOrderItems(orderId, items) {
            const container = document.getElementById(`items-${orderId}`);
            
            if (!items || items.length === 0) {
                container.innerHTML = '<div style="padding: 10px; color: #999;">No products</div>';
                return;
            }
            
            let html = '';
            items.forEach(item => {
                const lineTotal = (item.quantity * item.price).toFixed(2);
                const volume = item.volume_ml ? `${item.volume_ml}ml` : '50ml';
                html += `
                    <div class="item">
                        <div style="flex: 1;">
                            <div class="item-name">${htmlEscape(item.p_name || 'Unknown Product')}</div>
                            <div class="item-qty">Size: ${volume} | Qty: ${item.quantity} | Price: $${parseFloat(item.price).toFixed(2)}</div>
                        </div>
                        <div class="item-price">$${lineTotal}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function displayCustomItems(orderId, customItems) {
            const section = document.getElementById(`custom-section-${orderId}`);
            const container = document.getElementById(`custom-${orderId}`);
            
            if (!customItems || customItems.length === 0) {
                section.style.display = 'none';
                return;
            }
            
            section.style.display = 'block';
            
            let html = '';
            customItems.forEach(item => {
                const totalMl = parseFloat(item.oil_amount_grams) || 0;
                
                // Parse types_detail: "type_name|percent,type_name|percent,..."
                let ingredientsHtml = '';
                if (item.types_detail && item.types_detail.trim()) {
                    const typesList = item.types_detail.split(',').filter(t => t.trim()).map(t => {
                        const [typeName, percent] = t.split('|');
                        const ml = Math.round((parseFloat(percent) / 100) * totalMl * 100) / 100;
                        return `<span style="background: #e8f4f8; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin: 3px; display: inline-block; border-left: 3px solid #3498db;">
                            <strong>${htmlEscape(typeName || 'Unknown')}</strong>: ${percent}% (${ml}ml)
                        </span>`;
                    }).join('');
                    ingredientsHtml = `<div style="margin-top: 8px; padding: 8px; background: white; border-radius: 4px;">
                        <div style="font-size: 12px; font-weight: 600; color: #333; margin-bottom: 5px;">Ingredients Used:</div>
                        <div>${typesList}</div>
                    </div>`;
                } else {
                    ingredientsHtml = '<div style="margin-top: 8px; color: #999; font-size: 12px;">No specific ingredients</div>';
                }
                
                html += `
                    <div class="item custom-item">
                        <div style="flex: 1;">
                            <div class="item-name">🎨 Bottle Design: ${item.bottle_design_id || 'N/A'}</div>
                            <div class="item-qty">Total Size: <strong>${totalMl}ml</strong></div>
                            ${ingredientsHtml}
                        </div>
                        <div class="item-price">$${parseFloat(item.custom_price).toFixed(2)}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function htmlEscape(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function updateOrderStatus(orderId) {
            const checkbox = document.getElementById(`complete-${orderId}`);
            const btn = event.target;
            
            const status = checkbox.checked ? 'completed' : 'pending';
            
            // Disable button and show loading state
            btn.disabled = true;
            btn.innerHTML = '<span class="status-loading"></span> Updating...';
            
            fetch('../perfdb/update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    order_id: orderId,
                    status: status
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Find and update status badge in the main table row
                    const orderRow = document.querySelector(`tr[onclick*="toggleOrderDetails(${orderId})"]`);
                    if (orderRow) {
                        const statusBadge = orderRow.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = status === 'completed' ? 'Completed' : 'Pending';
                            statusBadge.className = `status-badge status-${status}`;
                        }
                    }
                    
                    btn.innerHTML = '✅ Updated!';
                    btn.disabled = false;
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        btn.innerHTML = 'Update Status';
                    }, 2000);
                    
                    // Show success message
                    alert(`✅ Order #${orderId} marked as ${status === 'completed' ? 'Ready!' : 'Pending'}`);
                } else {
                    throw new Error(data.error || 'Failed to update status');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                console.log('Full error:', err.toString());
                alert('❌ Error updating status: ' + err.message + '\n\nمشكلة في تحديث الحالة. الرجاء تسجيل الدخول مرة أخرى والمحاولة.\n\n' + err.message);
                btn.innerHTML = 'Update Status';
                btn.disabled = false;
                checkbox.checked = !checkbox.checked;
            });
        }

        function logout() {
            fetch('../perfdb/logout_api.php', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert('Logout failed: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Logout error:', err);
                    alert('Error logging out. Redirecting...');
                    window.location.href = '../indexed/index.html';
                });
        }

        // Filter orders by status
        function filterByStatus(status) {
            const rows = document.querySelectorAll('tbody tr.order-row');
            
            rows.forEach(row => {
                const statusBadge = row.querySelector('.status-badge');
                if (statusBadge) {
                    const rowStatus = statusBadge.textContent.toLowerCase().trim();
                    const statusMap = {
                        'pending': 'pending',
                        'completed': 'completed',
                        'cancelled': 'cancelled'
                    };
                    
                    if (rowStatus === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            // Show count
            const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
            console.log(`Showing ${visibleRows.length} ${status} orders`);
        }

        // Show all orders
        function showAllOrders() {
            const rows = document.querySelectorAll('tbody tr.order-row');
            rows.forEach(row => {
                row.style.display = '';
            });
            console.log(`Showing all ${rows.length} orders`);
        }
    </script>
    </script>
    <script src="/pefumeppp/assets/keep-session-alive.js"></script>
</body>
</html>
