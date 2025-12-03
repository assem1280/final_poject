<?php
// Debug endpoint to check session
header('Content-Type: application/json');
session_start();

echo json_encode([
    'session_exists' => !empty($_SESSION),
    'profile_id' => $_SESSION['profile_id'] ?? null,
    'role' => $_SESSION['role'] ?? null,
    'first_name' => $_SESSION['first_name'] ?? null,
    'all_session_keys' => array_keys($_SESSION)
]);
?>
