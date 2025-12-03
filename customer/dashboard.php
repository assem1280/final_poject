<?php
// Customer Dashboard
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 86400); // 24 hours
session_start();

// Verify login - check if session exists and is valid
if (!isset($_SESSION['profile_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../indexed/login.html');
    exit;
}

// Refresh session cookie to keep it alive
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), $_COOKIE[session_name()], time() + 86400, '/');
}

require_once '../perfdb/connect.php';

// Get customer information
$profile_id = $_SESSION['profile_id'];

// Get orders
$orders_query = "SELECT * FROM orders WHERE customer_profile_id = :profile_id ORDER BY created_at DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->execute([':profile_id' => $profile_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total purchases
$total_spent = 0;
foreach ($orders as $order) {
    $total_spent += $order['total_amount'];
}

$total_orders = count($orders);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            direction: ltr;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
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
        
        .user-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .user-info h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-item label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item value {
            color: #333;
            font-weight: 600;
            font-size: 16px;
        }
        
        .statistics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            color: #667eea;
            font-size: 32px;
            font-weight: bold;
        }
        
        .orders-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #eee;
        }
        
        .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            color: #666;
        }
        
        .orders-table tr:hover {
            background: #f8f9fa;
        }
        
        .orders-table tbody tr {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .orders-table tbody tr:hover {
            background: #e8eef7 !important;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .order-number-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .order-number-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .modal-header h2 {
            color: #333;
            margin: 0;
            font-size: 22px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .order-details-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .order-details-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .order-details-item-info {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .order-details-item-info strong {
            color: #333;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error-message {
            color: #721c24;
            background: #f8d7da;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
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
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
                        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Ready Status Section */
        .ready-status-section {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
            text-align: center;
            border: 2px solid #1e8449;
        }
        
        .ready-status-section h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .ready-status-checkmark {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .ready-status-message {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .ready-status-note {
            font-size: 14px;
            font-style: italic;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        .pending-status-section {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .pending-status-section h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .statistics {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                font-size: 14px;
            }
            
            .orders-table th,
            .orders-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="header">
            <div>
                <h1>🛍️ Customer Dashboard</h1>
            </div>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
        
        <!-- User Information -->
        <div class="user-info">
            <h2>Account Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>First Name</label>
                    <value><?php echo htmlspecialchars($_SESSION['first_name']); ?></value>
                </div>
                <div class="info-item">
                    <label>Last Name</label>
                    <value><?php echo htmlspecialchars($_SESSION['last_name'] ?? 'Not specified'); ?></value>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <value><?php echo htmlspecialchars($_SESSION['email'] ?? '-'); ?></value>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <value><?php echo htmlspecialchars($_SESSION['phone'] ?? 'Not specified'); ?></value>
                </div>
                <div class="info-item">
                    <label>Address</label>
                    <value><?php echo htmlspecialchars($_SESSION['address'] ?? 'Not specified'); ?></value>
                </div>
                <div class="info-item">
                    <label>Account Type</label>
                    <value>Customer</value>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="statistics">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo $total_orders; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Spent</h3>
                <div class="value">$<?php echo number_format($total_spent, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Average Order</h3>
                <div class="value">$<?php echo $total_orders > 0 ? number_format($total_spent / $total_orders, 2) : '0.00'; ?></div>
            </div>
        </div>
        
        <!-- Orders -->
        <div class="orders-section">
            <h2>📦 Your Orders</h2>
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <h3>No Orders Yet</h3>
                    <p>Start shopping now!</p>
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr onclick="openOrderDetails(<?php echo $order['order_id']; ?>)">
                                <td><span class="order-number-link">#<?php echo $order['order_id']; ?></span></td>
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
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Current Shopping Cart -->
        <div class="orders-section" style="margin-top: 30px;">
            <h2>🛒 Current Shopping Cart</h2>
            <?php
            // Get current cart items
            $cart_query = "SELECT ci.*, p.p_name, p.price FROM cart_items ci
                          LEFT JOIN products p ON ci.product_id = p.p_id
                          WHERE ci.customer_profile_id = :profile_id
                          ORDER BY ci.cart_item_id DESC";
            $cart_stmt = $conn->prepare($cart_query);
            $cart_stmt->execute([':profile_id' => $profile_id]);
            $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get custom items
            $custom_query = "SELECT * FROM custom_cart_items 
                            WHERE customer_profile_id = :profile_id
                            ORDER BY custom_cart_id DESC";
            $custom_stmt = $conn->prepare($custom_query);
            $custom_stmt->execute([':profile_id' => $profile_id]);
            $custom_items = $custom_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $has_items = !empty($cart_items) || !empty($custom_items);
            
            if (!$has_items):
            ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <h3>Shopping Cart is Empty</h3>
                    <p><a href="../indexed/index.html" style="color: #667eea;">Start shopping now</a></p>
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Volume</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cart_total = 0;
                        foreach ($cart_items as $item): 
                            $basePrice = $item['price'] ?? 0;
                            $volume_ml = $item['volume_ml'] ?? 50;
                            $volumeAdjustment = ($volume_ml == 100) ? 50.00 : 0.00;
                            $pricePerUnit = $basePrice + $volumeAdjustment;
                            $item_total = $pricePerUnit * $item['quantity'];
                            $cart_total += $item_total;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['p_name'] ?? 'Product'); ?></td>
                                <td><?php echo $volume_ml; ?> ml</td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($pricePerUnit, 2); ?></td>
                                <td>$<?php echo number_format($item_total, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php foreach ($custom_items as $item): 
                            $cart_total += $item['custom_price'];
                        ?>
                            <tr>
                                <td>🎨 Custom Mix - <?php echo htmlspecialchars($item['bottle_design_id'] ?? 'Custom'); ?></td>
                                <td>1</td>
                                <td>$<?php echo number_format($item['custom_price'], 2); ?></td>
                                <td>$<?php echo number_format($item['custom_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px; text-align: right; padding: 15px; background: #f8f9fa; border-radius: 5px; font-weight: bold; font-size: 18px;">
                    Total: $<?php echo number_format($cart_total, 2); ?>
                </div>
                <div style="margin-top: 20px; text-align: center; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="../indexed/index.html" style="display: inline-block; background: #667eea; color: white; padding: 10px 30px; border-radius: 5px; text-decoration: none; font-weight: bold; transition: background 0.3s;">Continue Shopping</a>
                    <a href="../indexed/checkout-complete.html" style="display: inline-block; background: #27ae60; color: white; padding: 10px 30px; border-radius: 5px; text-decoration: none; font-weight: bold; transition: background 0.3s;">Order Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <button class="modal-close" onclick="closeOrderDetails()">&times;</button>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>
    
    <script>
        function openOrderDetails(orderId) {
            const modal = document.getElementById('orderModal');
            const modalBody = document.getElementById('modalBody');
            
            // Show loading state
            modalBody.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading details...</p>
                </div>
            `;
            modal.classList.add('show');
            
            // Fetch order status first
            fetch(`../customer/get_order_status.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(statusData => {
                    // Fetch order details
                    return fetch(`../perfdb/get_order_details.php?order_id=${orderId}`)
                        .then(response => response.json())
                        .then(data => {
                            // Combine status and data
                            data.status = statusData.status;
                            return data;
                        });
                })
                .then(data => {
                    if (data.success) {
                        let html = '';
                        
                        // Status Display Section
                        if (data.status === 'completed') {
                            html += `
                                <div class="ready-status-section">
                                    <div class="ready-status-checkmark">✅</div>
                                    <div class="ready-status-message">Your Product is Ready!</div>
                                    <div class="ready-status-note">
                                        📦 Please wait for our response within two days to arrange pickup or delivery.
                                    </div>
                                </div>
                            `;
                        } else {
                            html += `
                                <div class="pending-status-section">
                                    <h3>⏳ Order Processing</h3>
                                    <p>Your order is being prepared. We'll notify you when it's ready.</p>
                                </div>
                            `;
                        }
                        
                        // Regular items
                        if (data.items && data.items.length > 0) {
                            html += `<div style="margin-bottom: 20px;">
                                <h3 style="color: #333; margin-bottom: 15px;">🧴 Products</h3>`;
                            
                            data.items.forEach(item => {
                                html += `
                                    <div class="order-details-item">
                                        <div class="order-details-item-name">${item.p_name || 'Product'}</div>
                                        <div class="order-details-item-info">
                                            <strong>Quantity:</strong> ${item.quantity}<br>
                                            <strong>Price:</strong> $${parseFloat(item.price).toFixed(2)}<br>
                                            <strong>Bottle Size:</strong> ${item.volume_ml || 50} ml<br>
                                            <strong>Total:</strong> $${(item.quantity * item.price).toFixed(2)}
                                        </div>
                                    </div>
                                `;
                            });
                            
                            html += '</div>';
                        }
                        
                        // Custom items
                        if (data.custom && data.custom.length > 0) {
                            html += `<div style="margin-bottom: 20px;">
                                <h3 style="color: #333; margin-bottom: 15px;">🎨 Custom Mixes</h3>`;
                            
                            data.custom.forEach(item => {
                                // Prefer the computed bottle_size_ml (100/50/30) returned by the API
                                const bottleSize = item.bottle_size_ml || item.bottle_design_id || 'Custom';
                                html += `
                                    <div class="order-details-item">
                                        <div class="order-details-item-name">Custom Mix</div>
                                        <div class="order-details-item-info">
                                            <strong>Bottle Size:</strong> ${bottleSize} ml<br>
                                            <strong>Oil Amount:</strong> ${item.oil_amount_grams || 0} grams<br>
                                            ${item.types_detail ? '<strong>Ingredients:</strong> ' + item.types_detail + '<br>' : ''}
                                            <strong>Price:</strong> $${parseFloat(item.custom_price).toFixed(2)}
                                        </div>
                                    </div>
                                `;
                            });
                            
                            html += '</div>';
                        }
                        
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `<div class="error-message">❌ Error: ${data.error || 'Failed to load data'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `<div class="error-message">❌ Server connection error</div>`;
                });
        }
        
        function closeOrderDetails() {
            const modal = document.getElementById('orderModal');
            modal.classList.remove('show');
        }
        
        // Close modal when clicking outside of it
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target === modal) {
                closeOrderDetails();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeOrderDetails();
            }
        });
        
        function logout() {
            fetch('../perfdb/logout_api.php', { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(r => {
                    if (!r.ok) throw new Error('Network response was not ok');
                    return r.json();
                })
                .then(data => {
                    if (data && data.success) {
                        window.location.href = data.redirect || '../indexed/index.html';
                    } else {
                        alert('Logout failed: ' + (data.message || data.error || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Logout error:', err);
                    // fallback: redirect to home (this will effectively log out for many flows)
                    window.location.href = '../indexed/index.html';
                });
        }
            </script>
            <script src="/pefumeppp/assets/keep-session-alive.js"></script>
            </body>
</html>
