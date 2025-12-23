<?php
// API endpoint for fetching dashboard statistics with date range filter
session_start();

header('Content-Type: application/json');

// Verify admin access
if (!isset($_SESSION['profile_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'connect.php';

// Get date range parameters
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Build date condition for queries
$dateCondition = "";
$dateParams = [];

if ($dateFrom && $dateTo) {
    $dateCondition = " AND created_at >= :date_from AND created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)";
    $dateParams = [':date_from' => $dateFrom, ':date_to' => $dateTo];
} elseif ($dateFrom) {
    $dateCondition = " AND created_at >= :date_from";
    $dateParams = [':date_from' => $dateFrom];
} elseif ($dateTo) {
    $dateCondition = " AND created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)";
    $dateParams = [':date_to' => $dateTo];
}

try {
    // Total Orders in date range
    $total_orders_query = "SELECT COUNT(*) as total FROM orders WHERE 1=1" . $dateCondition;
    $total_orders_stmt = $conn->prepare($total_orders_query);
    $total_orders_stmt->execute($dateParams);
    $total_orders = $total_orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Revenue in date range
    $total_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE 1=1" . $dateCondition;
    $total_revenue_stmt = $conn->prepare($total_revenue_query);
    $total_revenue_stmt->execute($dateParams);
    $total_revenue = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

    // Pending Orders in date range
    $pending_orders_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'" . $dateCondition;
    $pending_orders_stmt = $conn->prepare($pending_orders_query);
    $pending_orders_stmt->execute($dateParams);
    $pending_orders = $pending_orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Completed Orders in date range
    $completed_orders_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'completed'" . $dateCondition;
    $completed_orders_stmt = $conn->prepare($completed_orders_query);
    $completed_orders_stmt->execute($dateParams);
    $completed_orders = $completed_orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // New Customers in date range (based on profile created_at)
    $customerDateCondition = str_replace('created_at', 'created_at', $dateCondition);
    $new_customers_query = "SELECT COUNT(*) as total FROM profiles WHERE role = 'customer'" . $customerDateCondition;
    $new_customers_stmt = $conn->prepare($new_customers_query);
    $new_customers_stmt->execute($dateParams);
    $new_customers = $new_customers_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Top Products in date range
    $top_products_query = "SELECT p.p_name, p.p_id, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue
                          FROM order_items oi
                          JOIN products p ON oi.p_id = p.p_id
                          JOIN orders o ON oi.order_id = o.order_id
                          WHERE 1=1" . str_replace('created_at', 'o.created_at', $dateCondition) . "
                          GROUP BY p.p_id, p.p_name
                          ORDER BY total_sold DESC
                          LIMIT 5";
    $top_products_stmt = $conn->prepare($top_products_query);
    $top_products_stmt->execute($dateParams);
    $top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Orders list in date range
    $orders_query = "SELECT o.order_id, o.customer_profile_id, o.total_amount, o.status, o.created_at, 
                     p.first_name, p.last_name, p.email, p.phone, p.address
                     FROM orders o 
                     JOIN profiles p ON o.customer_profile_id = p.profile_id 
                     WHERE 1=1" . str_replace('created_at', 'o.created_at', $dateCondition) . "
                     ORDER BY o.created_at DESC";
    $orders_stmt = $conn->prepare($orders_query);
    $orders_stmt->execute($dateParams);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_orders' => (int)$total_orders,
            'total_revenue' => (float)$total_revenue,
            'pending_orders' => (int)$pending_orders,
            'completed_orders' => (int)$completed_orders,
            'new_customers' => (int)$new_customers
        ],
        'top_products' => $top_products,
        'orders' => $orders,
        'date_range' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
