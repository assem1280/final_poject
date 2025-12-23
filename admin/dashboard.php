<?php
// Admin Dashboard
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 86400); // 24 hours
session_start();

// Verify login - check if session exists and is valid
if (!isset($_SESSION['profile_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

// Total Products
$total_products_query = "SELECT COUNT(*) as total FROM products";
$total_products_stmt = $conn->prepare($total_products_query);
$total_products_stmt->execute();
$total_products_result = $total_products_stmt->fetch(PDO::FETCH_ASSOC);
$total_products = $total_products_result['total'] ?? 0;

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

// All Users
$users_query = "SELECT profile_id, first_name, last_name, email, role, phone, created_at FROM profiles ORDER BY created_at DESC";
$users_stmt = $conn->prepare($users_query);
$users_stmt->execute();
$all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Last 10 Orders with Full Customer Information
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
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/png" href="../images/Untitled_design-removebg-preview.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            padding: 20px;
            direction: ltr;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #ffffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 1);
            border: #e0c42aff solid 2px;
        }
        
        .header h1 {
            color: #000000ff;
            font-size: 28px;
        }
        
        .header-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .user-display {
            font-size: 16px;
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
        
        /* Date Range Filter Styles */
        .date-filter-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: #e0c42aff solid 2px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
        }
        
        .date-filter-container label {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .date-filter-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .date-filter-btn {
            padding: 8px 16px;
            border: 2px solid #e0c42aff;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            color: #333;
        }
        
        .date-filter-btn:hover {
            background: #fff8dc;
        }
        
        .date-filter-btn.active {
            background: #e0c42aff;
            color: #000;
        }
        
        .custom-date-inputs {
            display: none;
            align-items: center;
            gap: 10px;
            margin-left: 10px;
        }
        
        .custom-date-inputs.show {
            display: flex;
        }
        
        .custom-date-inputs input[type="date"] {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 13px;
            transition: border-color 0.3s;
        }
        
        .custom-date-inputs input[type="date"]:focus {
            outline: none;
            border-color: #e0c42aff;
        }
        
        .apply-date-btn {
            padding: 8px 16px;
            background: #e0c42aff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .apply-date-btn:hover {
            background: #c9ad26;
        }
        
        .date-range-display {
            margin-left: auto;
            font-size: 13px;
            color: #666;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 5px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .loading-spinner-large {
            width: 50px;
            height: 50px;
            border: 4px solid #e0e0e0;
            border-top-color: #e0c42aff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .statistics {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: #e0c42aff solid 2px;
        }
        
        .stat-card h3 {
            color: #000000ff;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            color: #000000ff;
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-card.purple {
            border-top-color: #e0c42aff;
        }
        
        .stat-card.purple .value {
            color: #000000ff;
        }
        
        .stat-card.orange {
            border-top-color: #e0c42aff;
        }
        
        .stat-card.orange .value {
            color: #000000ff;
        }
        
        .stat-card.green {
            border-top-color: #e0c42aff;
        }
        
        .stat-card.green .value {
            color: #000000ff;
        }
        
        .stat-card.red {
            border-top-color: #e0c42aff;
        }
        
        .stat-card.red .value {
            color: #000000ff;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: #e0c42aff solid 2px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0c42aff;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .data-table thead {
            background: #f8f9fa;
            border-bottom: #e0c42aff solid 2px;
        }
        
        .data-table th {
            color: #333;
            font-weight: 600;
            padding: 12px;
            text-align: right;
            border-bottom: #e0c42aff solid 2px;
            background: #ffffffff;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            color: #000000ff;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
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
        
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .role-admin {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .role-employee {
            background: #f3e5f5;
            color: #6a1b9a;
        }
        
        .role-customer {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .user-count {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .user-count h4 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .user-count .number {
            font-size: 28px;
            font-weight: bold;
            color: #e0c42aff;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0c42aff;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #000000ff;
            border-bottom-color: #e0c42aff;
        }
        
        /* Additional styles for expandable rows */
        .data-table tbody tr.order-row {
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .data-table tbody tr.order-row:hover {
            background: #f0f7ff;
        }
        
        .expand-icon {
            display: inline-block;
            transition: transform 0.3s;
            margin-left: 8px;
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
            border-left: 4px solid #e0c42aff;
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
        
        /* Products Modal Styles */
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
        
        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 1000px;
            width: 90%;
            max-height: 85vh;
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
            border-bottom: 2px solid #e0c42aff;
            padding-bottom: 15px;
        }
        
        .modal-header h2 {
            color: #333;
            margin: 0;
            font-size: 24px;
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
        
        .modal-search {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .modal-search input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .modal-search input:focus {
            outline: none;
            border-color: #e0c42aff;
        }
        
        .modal-search button {
            background: #e0c42aff;
            color: #000000;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .modal-search button:hover {
            background: #c9ad26;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
            overflow: hidden;
        }
        
        .product-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 10px;
            background: #e0e0e0;
        }
        
        .product-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            border-color: #9b59b6;
        }
        
        .product-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .product-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
            flex: 1;
        }
        
        .product-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            margin-right: 10px;
        }
        
        .product-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .product-info-item {
            background: white;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        
        .product-info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .product-info-value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .product-info-value.stock-high {
            color: #27ae60;
        }
        
        .product-info-value.stock-low {
            color: #f39c12;
        }
        
        .product-info-value.stock-out {
            color: #e74c3c;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 12px;
        }
        
        .btn-edit {
            background: #e0c42aff;
            color: #000000;
        }
        
        .btn-edit:hover {
            background: #c9ad26;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #e0c42aff;
            box-shadow: 0 0 0 3px rgba(224, 196, 42, 0.1);
        }
        
        .image-preview {
            width: 100%;
            max-width: 200px;
            height: 200px;
            border: 2px dashed #e0c42aff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fffef0;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 8px 16px;
            background: #e0c42aff;
            color: #000000;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-align: center;
            transition: background 0.3s;
        }
        
        .file-input-label:hover {
            background: #c9ad26;
        }
        
        .form-group input[type="file"] {
            display: none;
        }
        
        .file-name-display {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .edit-form {
            display: none;
            background: white;
            border: 2px solid #e0c42aff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
        }
        
        .edit-form.show {
            display: block;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-save {
            flex: 1;
            background: #27ae60;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-save:hover {
            background: #229954;
        }
        
        .btn-cancel {
            flex: 1;
            background: #95a5a6;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-cancel:hover {
            background: #7f8c8d;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #e0c42aff;
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
        
        .stock-status {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .stock-status.in-stock {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-status.low-stock {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-status.out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
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
            
            .date-filter-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-filter-buttons {
                width: 100%;
                justify-content: flex-start;
            }
            
            .date-filter-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            
            .custom-date-inputs {
                flex-wrap: wrap;
                margin-left: 0;
                width: 100%;
            }
            
            .date-range-display {
                margin-left: 0;
                width: 100%;
                text-align: center;
            }
            
            .data-table {
                font-size: 12px;
            }
            
            .data-table th,
            .data-table td {
                padding: 8px;
            }
        }
        
        /* Development Tools Section */
        .dev-tools-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            border: 2px solid #e74c3c;
        }
        
        .dev-tools-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            color: white;
        }
        
        .dev-tools-header h2 {
            color: #e74c3c;
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dev-tools-header .toggle-icon {
            color: white;
            font-size: 20px;
            transition: transform 0.3s;
        }
        
        .dev-tools-header .toggle-icon.expanded {
            transform: rotate(180deg);
        }
        
        .dev-tools-content {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        .dev-tools-content.show {
            display: block;
        }
        
        .dev-warning {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #fff;
        }
        
        .dev-warning h4 {
            color: #e74c3c;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .dev-warning p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .dev-tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .dev-tool-card {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .dev-tool-card h4 {
            color: white;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .dev-tool-card p {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            margin: 0 0 15px 0;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
        }
        
        .btn-danger:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Reset confirmation modal */
        .reset-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .reset-modal.show {
            display: flex;
        }
        
        .reset-modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .reset-modal-content h3 {
            color: #e74c3c;
            margin: 0 0 20px 0;
            font-size: 24px;
        }
        
        .reset-modal-content p {
            color: #333;
            margin: 0 0 20px 0;
            line-height: 1.6;
        }
        
        .reset-modal-content .confirmation-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .reset-modal-content .confirmation-input:focus {
            outline: none;
            border-color: #e74c3c;
        }
        
        .reset-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- رأس الصفحة -->
        <div class="header">
            <h1> Admin Dashboard</h1>
            <div class="header-info">
                <div class="user-display">
                    Hello, <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong>
                </div>
                <button class="logout-btn" onclick="logout()">Logout</button>
            </div>
        </div>
        
        <!-- Date Range Filter -->
        <div class="date-filter-container">
            <label>📅 Date Range:</label>
            <div class="date-filter-buttons">
                <button class="date-filter-btn" data-range="today" onclick="setDateRange('today')">Today</button>
                <button class="date-filter-btn active" data-range="7days" onclick="setDateRange('7days')">Last 7 Days</button>
                <button class="date-filter-btn" data-range="30days" onclick="setDateRange('30days')">Last 30 Days</button>
                <button class="date-filter-btn" data-range="this-month" onclick="setDateRange('this-month')">This Month</button>
                <button class="date-filter-btn" data-range="prev-month" onclick="setDateRange('prev-month')">Previous Month</button>
                <button class="date-filter-btn" data-range="custom" onclick="setDateRange('custom')">Custom</button>
            </div>
            <div class="custom-date-inputs" id="customDateInputs">
                <input type="date" id="dateFrom" />
                <span>to</span>
                <input type="date" id="dateTo" />
                <button class="apply-date-btn" onclick="applyCustomDateRange()">Apply</button>
            </div>
            <div class="date-range-display" id="dateRangeDisplay">
                Showing: Last 7 Days
            </div>
        </div>
        
        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner-large"></div>
        </div>
        
        <!-- Main Statistics -->
        <div class="statistics">
            <div class="stat-card" style="cursor: pointer;" onclick="showAllOrders()">
                <h3>🛒 Total Orders</h3>
                <div class="value" id="statTotalOrders"><?php echo $total_orders; ?></div>
            </div>
            <div class="stat-card" style="border-right-color:  #e0c42aff; cursor: pointer;" onclick="filterByStatus('pending')">
                <h3>⏳ Pending Orders</h3>
                <div class="value" id="statPendingOrders" style="color: #e74c3c;"><?php echo $pending_orders; ?></div>
            </div>
            <div class="stat-card" style="border-right-color:  #e0c42aff; cursor: pointer;" onclick="filterByStatus('completed')">
                <h3>✅ Completed Orders</h3>
                <div class="value" id="statCompletedOrders" style="color: #27ae60;"><?php echo $completed_orders; ?></div>
            </div>
            <div class="stat-card orange">
                <h3>Total Revenue</h3>
                <div class="value" id="statTotalRevenue">$<?php echo number_format($total_revenue, 2); ?></div>
            </div>
            <div class="stat-card purple" onclick="openProductsModal()" style="cursor: pointer;">
                <h3>Total Products</h3>
                <div class="value"><?php echo $total_products; ?></div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content-grid">
            <!-- Recent Orders -->
            <div class="section">
                <h2>📦 Recent Orders</h2>
                <div class="table-responsive">
                    <table class="data-table">
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
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
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
                                                        <div style="padding: 10px; color: #000000ff; text-align: center;">
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
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- User Statistics -->
            <div class="section">
                <h2>👥 User Statistics</h2>
                <?php
                    $admins = count(array_filter($all_users, fn($u) => $u['role'] === 'admin'));
                    $employees = count(array_filter($all_users, fn($u) => $u['role'] === 'employee'));
                    $customers = count(array_filter($all_users, fn($u) => $u['role'] === 'customer'));
                ?>
                <div class="user-count" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="showUsersByRole('customer')" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                    <h4>👤 Customers</h4>
                    <div class="number" style="color: #2e7d32;"><?php echo $customers; ?></div>
                </div>
                <div class="user-count" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="showUsersByRole('employee')" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                    <h4>👷 Employees</h4>
                    <div class="number" style="color: #6a1b9a;"><?php echo $employees; ?></div>
                </div>
                <div class="user-count" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="showUsersByRole('admin')" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                    <h4>👑 Admins</h4>
                    <div class="number" style="color: #1565c0;"><?php echo $admins; ?></div>
                </div>
            </div>
        </div>
        
        <!-- All Users -->
        <div class="section">
            <h2>👨‍💼 All Users</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php 
                                            $role_en = [
                                                'admin' => 'Admin',
                                                'employee' => 'Employee',
                                                'customer' => 'Customer'
                                            ];
                                            echo $role_en[$user['role']] ?? $user['role'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['phone']) ?: '-'; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Products Management Modal -->
    <div id="productsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📦 Product Management</h2>
                <button class="modal-close" onclick="closeProductsModal()">×</button>
            </div>
            <div class="modal-search">
                <input type="text" id="productSearch" placeholder="Search products by name, brand..." onkeyup="filterProducts()">
                <button onclick="clearProductSearch()">Clear</button>
            </div>
            <div id="productsContainer" class="products-grid">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading products...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Users by Role Modal -->
    <div id="usersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="usersModalTitle">👥 Users</h2>
                <button class="modal-close" onclick="closeUsersModal()">×</button>
            </div>
            <div class="modal-search">
                <input type="text" id="userSearch" placeholder="Search users by name, email..." onkeyup="filterUsers()">
                <button onclick="clearUserSearch()">Clear</button>
            </div>
            <div class="table-responsive">
                <table class="data-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Development Tools Section -->
    <div class="dev-tools-section">
        <div class="dev-tools-header" onclick="toggleDevTools()">
            <h2>🛠️ Development Tools</h2>
            <span class="toggle-icon" id="devToolsToggle">▼</span>
        </div>
        <div class="dev-tools-content" id="devToolsContent">
            <div class="dev-warning">
                <h4>⚠️ Warning: Development Use Only</h4>
                <p>These tools are for development and testing purposes only. They perform destructive operations that cannot be undone. Do NOT use in production environments!</p>
            </div>
            <div class="dev-tools-grid">
                <div class="dev-tool-card">
                    <h4>🗑️ Reset All Orders</h4>
                    <p>Deletes all orders, order items, and custom perfumes. Resets auto-increment IDs for clean testing.</p>
                    <button class="btn-danger" onclick="showResetModal()">
                        🗑️ Reset All Orders
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reset Confirmation Modal -->
    <div class="reset-modal" id="resetModal">
        <div class="reset-modal-content">
            <h3>⚠️ Confirm Reset All Orders</h3>
            <p>
                This will permanently delete:<br>
                • All orders<br>
                • All order items<br>
                • All custom perfumes and their components<br><br>
                <strong>This action cannot be undone!</strong>
            </p>
            <p>Type <strong>DELETE ALL ORDERS</strong> to confirm:</p>
            <input type="text" class="confirmation-input" id="resetConfirmInput" placeholder="Type confirmation here...">
            <div class="reset-modal-buttons">
                <button class="btn-cancel" onclick="closeResetModal()">Cancel</button>
                <button class="btn-danger" id="resetOrdersBtn" onclick="confirmResetOrders()">
                    🗑️ Reset All Orders
                </button>
            </div>
        </div>
    </div>
    </div>
    
    <script>
        // Date Range Filter Variables
        let currentDateRange = '7days';
        let currentDateFrom = null;
        let currentDateTo = null;
        
        // Initialize date filter on page load
        document.addEventListener('DOMContentLoaded', function() {
            setDateRange('7days');
        });
        
        function setDateRange(range) {
            currentDateRange = range;
            const today = new Date();
            let dateFrom, dateTo;
            
            // Update active button
            document.querySelectorAll('.date-filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.range === range) {
                    btn.classList.add('active');
                }
            });
            
            // Show/hide custom date inputs
            const customInputs = document.getElementById('customDateInputs');
            if (range === 'custom') {
                customInputs.classList.add('show');
                return; // Don't fetch yet, wait for Apply button
            } else {
                customInputs.classList.remove('show');
            }
            
            // Calculate date range
            switch(range) {
                case 'today':
                    dateFrom = formatDate(today);
                    dateTo = formatDate(today);
                    updateDateRangeDisplay('Today');
                    break;
                case '7days':
                    dateFrom = formatDate(new Date(today.getTime() - 6 * 24 * 60 * 60 * 1000));
                    dateTo = formatDate(today);
                    updateDateRangeDisplay('Last 7 Days');
                    break;
                case '30days':
                    dateFrom = formatDate(new Date(today.getTime() - 29 * 24 * 60 * 60 * 1000));
                    dateTo = formatDate(today);
                    updateDateRangeDisplay('Last 30 Days');
                    break;
                case 'this-month':
                    dateFrom = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                    dateTo = formatDate(today);
                    updateDateRangeDisplay('This Month');
                    break;
                case 'prev-month':
                    const prevMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastDayPrevMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                    dateFrom = formatDate(prevMonth);
                    dateTo = formatDate(lastDayPrevMonth);
                    updateDateRangeDisplay('Previous Month');
                    break;
            }
            
            currentDateFrom = dateFrom;
            currentDateTo = dateTo;
            fetchFilteredStats(dateFrom, dateTo);
        }
        
        function applyCustomDateRange() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            if (!dateFrom || !dateTo) {
                alert('Please select both From and To dates');
                return;
            }
            
            if (new Date(dateFrom) > new Date(dateTo)) {
                alert('From date cannot be after To date');
                return;
            }
            
            currentDateFrom = dateFrom;
            currentDateTo = dateTo;
            updateDateRangeDisplay(`${dateFrom} to ${dateTo}`);
            fetchFilteredStats(dateFrom, dateTo);
        }
        
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        function updateDateRangeDisplay(text) {
            document.getElementById('dateRangeDisplay').textContent = `Showing: ${text}`;
        }
        
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('show');
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('show');
        }
        
        function fetchFilteredStats(dateFrom, dateTo) {
            showLoading();
            
            fetch(`../perfdb/get_dashboard_stats.php?date_from=${dateFrom}&date_to=${dateTo}`)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateStatCards(data.stats);
                        updateOrdersTable(data.orders);
                    } else {
                        console.error('Error fetching stats:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                })
                .finally(() => {
                    hideLoading();
                });
        }
        
        function updateStatCards(stats) {
            document.getElementById('statTotalOrders').textContent = stats.total_orders;
            document.getElementById('statPendingOrders').textContent = stats.pending_orders;
            document.getElementById('statCompletedOrders').textContent = stats.completed_orders;
            document.getElementById('statTotalRevenue').textContent = '$' + parseFloat(stats.total_revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        function updateOrdersTable(orders) {
            const tbody = document.querySelector('.section .data-table tbody');
            if (!tbody) return;
            
            if (!orders || orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: #999;">No orders found in this date range</td></tr>';
                return;
            }
            
            let html = '';
            orders.forEach(order => {
                const statusClass = order.status === 'pending' ? 'status-pending' : 
                                   order.status === 'completed' ? 'status-completed' : 'status-cancelled';
                const statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                const orderDate = new Date(order.created_at).toLocaleDateString();
                
                html += `
                    <tr class="order-row" onclick="toggleOrderDetails(${order.order_id})">
                        <td>
                            <span class="expand-icon" id="icon-${order.order_id}">▼</span>
                            #${order.order_id}
                        </td>
                        <td>${escapeHtml(order.first_name + ' ' + order.last_name)}</td>
                        <td>${orderDate}</td>
                        <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    </tr>
                    <tr class="details-row" id="details-${order.order_id}">
                        <td colspan="5">
                            <div class="details-content">
                                <div class="order-details-box">
                                    <div class="detail-section">
                                        <h4>📋 Customer Information</h4>
                                        <div class="customer-info">
                                            <div class="customer-info-item">
                                                <span class="customer-info-label">Name:</span>
                                                <span class="customer-info-value">${escapeHtml(order.first_name + ' ' + order.last_name)}</span>
                                            </div>
                                            <div class="customer-info-item">
                                                <span class="customer-info-label">Email:</span>
                                                <span class="customer-info-value">${escapeHtml(order.email || '-')}</span>
                                            </div>
                                            <div class="customer-info-item">
                                                <span class="customer-info-label">Phone:</span>
                                                <span class="customer-info-value">${escapeHtml(order.phone || '-')}</span>
                                            </div>
                                            <div class="customer-info-item">
                                                <span class="customer-info-label">Address:</span>
                                                <span class="customer-info-value">${escapeHtml(order.address || '-')}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="detail-section">
                                        <h4>📦 Order Items</h4>
                                        <div class="items-list" id="items-${order.order_id}">
                                            <div class="loading">Loading items...</div>
                                        </div>
                                    </div>
                                    <div class="detail-section" id="custom-section-${order.order_id}" style="display: none;">
                                        <h4>🎨 Custom Mix</h4>
                                        <div class="items-list" id="custom-${order.order_id}">
                                            <div style="padding: 10px; color: #999; text-align: center;">Loading...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
        
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
                            throw new Error('Access denied - admin access required');
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
            
            // Safety check - if container doesn't exist, log error and return
            if (!container) {
                console.error(`Container not found: items-${orderId}`);
                return;
            }
            
            if (!items || items.length === 0) {
                container.innerHTML = '<div style="padding: 10px; color: #999;">No products</div>';
                return;
            }
            
            let html = '';
            items.forEach(item => {
                const lineTotal = (item.quantity * item.price).toFixed(2);
                const volume = item.volume_ml ? `${item.volume_ml}ml` : '50ml';
                const gender = item.gender_name ? ` | ${item.gender_name}` : '';
                const brand = item.brand_name ? ` (${item.brand_name})` : '';
                html += `
                    <div class="item">
                        <div style="flex: 1;">
                            <div class="item-name">${htmlEscape(item.p_name || 'Unknown Product')}${brand}</div>
                            <div class="item-qty">Size: ${volume} | Qty: ${item.quantity} | Price: $${parseFloat(item.price).toFixed(2)}${gender}</div>
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
            
            // Safety check - if elements don't exist, log error and return
            if (!section || !container) {
                console.error(`Custom section not found: custom-section-${orderId} or custom-${orderId}`);
                return;
            }
            
            if (!customItems || customItems.length === 0) {
                section.style.display = 'none';
                return;
            }
            
            section.style.display = 'block';
            
            let html = '';
            customItems.forEach(item => {
                const totalMl = parseFloat(item.oil_amount_grams) || 0;
                
                // Parse types_detail: "product_name - gender (percent%), ..."
                let ingredientsHtml = '';
                if (item.types_detail && item.types_detail.trim()) {
                    const typesList = item.types_detail.split(',').filter(t => t.trim()).map(t => {
                        // Format: "Product Name - Gender (XX%)"
                        const parts = t.trim();
                        return `<span style="background: #e8f4f8; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin: 3px; display: inline-block; border-left: 3px solid #3498db;">
                            <strong>${htmlEscape(parts)}</strong>
                        </span>`;
                    }).join('');
                    ingredientsHtml = `<div style="margin-top: 8px; padding: 8px; background: white; border-radius: 4px;">
                        <div style="font-size: 12px; font-weight: 600; color: #333; margin-bottom: 5px;">Used Ingredients:</div>
                        <div>${typesList}</div>
                    </div>`;
                } else {
                    ingredientsHtml = '<div style="margin-top: 8px; color: #999; font-size: 12px;">No specific ingredients</div>';
                }
                
                html += `
                    <div class="item custom-item">
                        <div style="flex: 1;">
                            <div class="item-name">Bottle Design: ${item.bottle_design_id || 'N/A'}</div>
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
        
        // Products Management Functions
        function openProductsModal() {
            const modal = document.getElementById('productsModal');
            modal.classList.add('show');
            loadProducts();
        }
        
        function closeProductsModal() {
            const modal = document.getElementById('productsModal');
            modal.classList.remove('show');
        }
        
        // Users Modal Functions
        const allUsersData = <?php echo json_encode($all_users); ?>;
        
        function showUsersByRole(role) {
            const modal = document.getElementById('usersModal');
            const titleEl = document.getElementById('usersModalTitle');
            const tbody = document.getElementById('usersTableBody');
            
            // Set title with appropriate icon
            const roleIcons = {
                'customer': '👤 Customers',
                'employee': '👷 Employees',
                'admin': '👑 Admins'
            };
            titleEl.textContent = roleIcons[role] || '👥 Users';
            
            // Filter users by role
            const filteredUsers = allUsersData.filter(u => u.role === role);
            
            // Display users
            displayUsersInModal(filteredUsers);
            
            // Show modal
            modal.classList.add('show');
        }
        
        function displayUsersInModal(users) {
            const tbody = document.getElementById('usersTableBody');
            
            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #999;">No users found</td></tr>';
                return;
            }
            
            let html = '';
            users.forEach(user => {
                const name = (user.first_name || '') + ' ' + (user.last_name || '');
                const email = user.email || '-';
                const phone = user.phone || '-';
                const createdDate = user.created_at ? new Date(user.created_at).toLocaleDateString() : '-';
                
                html += `
                    <tr>
                        <td>${escapeHtml(name.trim())}</td>
                        <td>${escapeHtml(email)}</td>
                        <td>${escapeHtml(phone)}</td>
                        <td>${createdDate}</td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function closeUsersModal() {
            const modal = document.getElementById('usersModal');
            modal.classList.remove('show');
        }
        
        function filterUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
        
        function clearUserSearch() {
            document.getElementById('userSearch').value = '';
            filterUsers();
        }
        
        function loadProducts() {
            const container = document.getElementById('productsContainer');
            container.innerHTML = `<div class="loading-spinner"><div class="spinner"></div><p>Loading products...</p></div>`;
            
            fetch('../perfdb/get_products.php')
                .then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    return r.json();
                })
                .then(data => {
                    if (data.success && data.products) {
                        displayProducts(data.products);
                    } else {
                        container.innerHTML = '<div style="padding: 20px; color: #e74c3c;">❌ Error: No products</div>';
                    }
                })
                .catch(err => {
                    console.error('Error loading products:', err);
                    container.innerHTML = `<div style="padding: 20px; color: #e74c3c;">❌ Error: ${err.message}</div>`;
                });
        }
        
        function displayProducts(products) {
            const container = document.getElementById('productsContainer');
            
            if (!products || products.length === 0) {
                container.innerHTML = '<div style="padding: 20px; color: #999;">No products</div>';
                return;
            }
            
            let html = '';
            products.forEach(product => {
                const stockStatus = product.stock > 20 ? 'in-stock' : 
                                   product.stock > 0 ? 'low-stock' : 'out-of-stock';
                const stockText = product.stock > 20 ? 'In Stock' : 
                                 product.stock > 0 ? 'Low Stock' : 'Out of Stock';
                
                // Handle image URL
                const imageUrl = product.image_url && product.image_url.trim() !== '' 
                    ? `../${product.image_url}` 
                    : '../images/perfumes/default-perfume.jpg';
                
                html += `
                    <div class="product-card">
                        <img src="${imageUrl}" alt="${htmlEscape(product.p_name)}" class="product-image" onerror="this.src='../images/perfumes/default-perfume.jpg'">
                        
                        <div class="product-card-header">
                            <div class="product-name">${htmlEscape(product.p_name)}</div>
                            <div class="product-badge">${product.brand_name || 'Not specified'}</div>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-info-item">
                                <div class="product-info-label">Price</div>
                                <div class="product-info-value">$${parseFloat(product.price).toFixed(2)}</div>
                            </div>
                            <div class="product-info-item">
                                <div class="product-info-label">Stock</div>
                                <div class="product-info-value ${product.stock > 20 ? 'stock-high' : product.stock > 0 ? 'stock-low' : 'stock-out'}">
                                    ${product.stock}
                                </div>
                            </div>
                        </div>
                        
                        <div class="stock-status ${stockStatus}">${stockText}</div>
                        
                        <div class="product-actions">
                            <button class="btn btn-edit" onclick="toggleEditForm(${product.p_id})">✏️ Edit</button>
                            <button class="btn btn-delete" onclick="deleteProduct(${product.p_id})">🗑️ Delete</button>
                        </div>
                        
                        <div class="edit-form" id="edit-form-${product.p_id}">
                            <h4 style="margin-top: 0; color: #333;">Edit Product</h4>
                            
                            <div class="image-preview" id="preview-${product.p_id}">
                                <img src="${imageUrl}" alt="${htmlEscape(product.p_name)}" onerror="this.src='../images/perfumes/default-perfume.jpg'">
                            </div>
                            
                            <form onsubmit="updateProduct(event, ${product.p_id})">
                                <div class="form-group">
                                    <label>Product Photo</label>
                                    <div class="file-input-wrapper">
                                        <label class="file-input-label" for="image-${product.p_id}">
                                            📷 Select Photo
                                        </label>
                                        <input type="file" id="image-${product.p_id}" accept="image/*" onchange="previewImage(event, ${product.p_id})">
                                    </div>
                                    <div class="file-name-display" id="file-name-${product.p_id}">No photo selected</div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Product Name</label>
                                    <input type="text" id="name-${product.p_id}" value="${htmlEscape(product.p_name)}" required>
                                </div>
                                <div class="form-group">
                                    <label>Price ($)</label>
                                    <input type="number" id="price-${product.p_id}" value="${product.price}" step="0.01" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Stock Quantity</label>
                                    <input type="number" id="stock-${product.p_id}" value="${product.stock}" min="0" required>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-save">💾 Save</button>
                                    <button type="button" class="btn-cancel" onclick="toggleEditForm(${product.p_id})">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Store all products for filtering
        let allProducts = [];
        
        // Original display function reference
        const displayProductsOriginal = displayProducts;
        
        function filterProducts() {
            const searchTerm = document.getElementById('productSearch').value.toLowerCase().trim();
            
            if (!searchTerm) {
                displayProductsOriginal(allProducts);
                return;
            }
            
            const filtered = allProducts.filter(product => {
                const name = (product.p_name || '').toLowerCase();
                const brand = (product.brand_name || '').toLowerCase();
                return name.includes(searchTerm) || brand.includes(searchTerm);
            });
            
            displayProductsOriginal(filtered);
        }
        
        function clearProductSearch() {
            document.getElementById('productSearch').value = '';
            displayProductsOriginal(allProducts);
        }
        
        // Store products when loading
        const originalLoadProducts = loadProducts;
        function loadProducts() {
            const container = document.getElementById('productsContainer');
            container.innerHTML = `<div class="loading-spinner"><div class="spinner"></div><p>Loading products...</p></div>`;
            
            fetch('../perfdb/get_products.php')
                .then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    return r.json();
                })
                .then(data => {
                    if (data.success && data.products) {
                        allProducts = data.products;
                        displayProductsOriginal(data.products);
                    } else {
                        container.innerHTML = '<div style="padding: 20px; color: #e74c3c;">❌ Error: No products</div>';
                    }
                })
                .catch(err => {
                    console.error('Error loading products:', err);
                    container.innerHTML = `<div style="padding: 20px; color: #e74c3c;">❌ Error: ${err.message}</div>`;
                });
        }
        
        function toggleEditForm(productId) {
            const form = document.getElementById(`edit-form-${productId}`);
            form.classList.toggle('show');
        }
        
        function previewImage(event, productId) {
            const file = event.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image');
                    return;
                }
                
                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size is too large. Maximum is 5MB');
                    return;
                }
                
                // Show filename
                document.getElementById(`file-name-${productId}`).textContent = file.name;
                
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(`preview-${productId}`);
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
                };
                reader.readAsDataURL(file);
            }
        }
        
        function updateProduct(event, productId) {
            event.preventDefault();
            
            const name = document.getElementById(`name-${productId}`).value;
            const price = document.getElementById(`price-${productId}`).value;
            const stock = document.getElementById(`stock-${productId}`).value;
            const imageInput = document.getElementById(`image-${productId}`);
            
            if (!name || !price || stock === '') {
                alert('Please fill in all fields');
                return;
            }
            
            const formData = new FormData();
            formData.append('p_id', productId);
            formData.append('p_name', name);
            formData.append('price', price);
            formData.append('stock', stock);
            
            // Add image file if selected
            if (imageInput.files.length > 0) {
                formData.append('image', imageInput.files[0]);
            }
            
            fetch('../perfdb/update_product.php', {
                method: 'POST',
                body: formData
            })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    alert('✅ Product updated successfully');
                    loadProducts(); // Reload products
                } else {
                    alert('❌ Error: ' + (data.error || 'Update failed'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('❌ Error: ' + err.message);
            });
        }
        
        function deleteProduct(productId) {
            if (!confirm('Are you sure you want to delete this product? This action cannot be undone!')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('p_id', productId);
            
            fetch('../perfdb/delete_product.php', {
                method: 'POST',
                body: formData
            })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    alert('✅ Product deleted successfully');
                    loadProducts(); // Reload products
                } else {
                    alert('❌ Error: ' + (data.error || 'Delete failed'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('❌ Error: ' + err.message);
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const productsModal = document.getElementById('productsModal');
            const usersModal = document.getElementById('usersModal');
            if (event.target == productsModal) {
                closeProductsModal();
            }
            if (event.target == usersModal) {
                closeUsersModal();
            }
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
        
        // ============================================
        // DEVELOPMENT TOOLS - Reset Orders Feature
        // ============================================
        
        function toggleDevTools() {
            const content = document.getElementById('devToolsContent');
            const icon = document.getElementById('devToolsToggle');
            content.classList.toggle('show');
            icon.classList.toggle('expanded');
        }
        
        function showResetModal() {
            document.getElementById('resetModal').classList.add('show');
            document.getElementById('resetConfirmInput').value = '';
            document.getElementById('resetConfirmInput').focus();
        }
        
        function closeResetModal() {
            document.getElementById('resetModal').classList.remove('show');
        }
        
        function confirmResetOrders() {
            const input = document.getElementById('resetConfirmInput').value;
            
            if (input !== 'DELETE ALL ORDERS') {
                alert('❌ Please type "DELETE ALL ORDERS" exactly to confirm.');
                return;
            }
            
            const btn = document.getElementById('resetOrdersBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Resetting...';
            
            fetch('../perfdb/reset_orders_dev.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ confirm: 'RESET_ALL_ORDERS' })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeResetModal();
                    
                    let message = '✅ All orders have been reset successfully!\n\n';
                    message += 'Deleted records:\n';
                    for (const [table, count] of Object.entries(data.deleted_records)) {
                        message += `  • ${table}: ${count} records\n`;
                    }
                    message += `\nTimestamp: ${data.timestamp}`;
                    
                    alert(message);
                    
                    // Reload page to show updated stats
                    window.location.reload();
                } else {
                    alert('❌ Error: ' + (data.error || 'Reset failed'));
                    btn.disabled = false;
                    btn.innerHTML = '🗑️ Reset All Orders';
                }
            })
            .catch(err => {
                console.error('Reset error:', err);
                alert('❌ Error: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '🗑️ Reset All Orders';
            });
        }
        
        // Close reset modal when clicking outside
        document.getElementById('resetModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeResetModal();
            }
        });
    </script>
    <script src="/pefumeppp/assets/keep-session-alive.js"></script>
</body>
</html>
