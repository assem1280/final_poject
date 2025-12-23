<?php
// API endpoint for fetching employee dashboard statistics with date range filter
// Employee can only access Today and Yesterday data
session_start();

header('Content-Type: application/json');

// Verify employee access
if (!isset($_SESSION['profile_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Employee access required']);
    exit;
}

require_once 'connect.php';

$employee_id = $_SESSION['profile_id'];

// Get date range parameter - restricted to 'today' or 'yesterday' only
$dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : 'today';

// Validate and restrict date range options for employees
if (!in_array($dateRange, ['today', 'yesterday'])) {
    $dateRange = 'today'; // Default to today if invalid option
}

// Calculate date range
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

switch($dateRange) {
    case 'yesterday':
        $dateFrom = $yesterday;
        $dateTo = $yesterday;
        break;
    case 'today':
    default:
        $dateFrom = $today;
        $dateTo = $today;
        break;
}

try {
    // Total Orders in date range
    $total_orders_query = "SELECT COUNT(*) as total FROM orders 
                          WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
    $total_orders_stmt = $conn->prepare($total_orders_query);
    $total_orders_stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
    $total_orders = $total_orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Revenue in date range
    $total_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders 
                           WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
    $total_revenue_stmt = $conn->prepare($total_revenue_query);
    $total_revenue_stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
    $total_revenue = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

    // Pending Orders in date range
    $pending_orders_query = "SELECT COUNT(*) as total FROM orders 
                            WHERE status = 'pending' AND DATE(created_at) BETWEEN :date_from AND :date_to";
    $pending_orders_stmt = $conn->prepare($pending_orders_query);
    $pending_orders_stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
    $pending_orders = $pending_orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Completed Orders in date range
    $completed_orders_query = "SELECT COUNT(*) as total FROM orders 
                              WHERE status = 'completed' AND DATE(created_at) BETWEEN :date_from AND :date_to";
    $completed_orders_stmt = $conn->prepare($completed_orders_query);
    $completed_orders_stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
    $completed_orders = $completed_orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // New Customers in date range
    $new_customers_query = "SELECT COUNT(*) as total FROM profiles 
                           WHERE role = 'customer' AND DATE(created_at) BETWEEN :date_from AND :date_to";
    $new_customers_stmt = $conn->prepare($new_customers_query);
    $new_customers_stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
    $new_customers = $new_customers_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Orders list in date range
    $orders_query = "SELECT o.order_id, o.customer_profile_id, o.total_amount, o.status, o.created_at, 
                     p.first_name, p.last_name, p.email, p.phone, p.address
                     FROM orders o 
                     JOIN profiles p ON o.customer_profile_id = p.profile_id 
                     WHERE DATE(o.created_at) BETWEEN :date_from AND :date_to
                     ORDER BY o.created_at DESC";
    $orders_stmt = $conn->prepare($orders_query);
    $orders_stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
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
        'orders' => $orders,
        'date_range' => [
            'type' => $dateRange,
            'from' => $dateFrom,
            'to' => $dateTo
        ],
        'employee_id' => $employee_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
