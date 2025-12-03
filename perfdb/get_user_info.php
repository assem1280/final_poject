<?php
// Get user information from session
header('Content-Type: application/json');
session_start();

if (isset($_SESSION['profile_id']) && isset($_SESSION['role'])) {
    echo json_encode([
        'success' => true,
        'profile_id' => $_SESSION['profile_id'],
        'role' => $_SESSION['role'],
        'first_name' => isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '',
        'last_name' => isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '',
        'email' => isset($_SESSION['email']) ? $_SESSION['email'] : ''
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
}
?>
