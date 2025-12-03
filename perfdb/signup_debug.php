<?php
// Debug signup API - log all incoming data
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

// Log the raw POST data
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

// Create a debug log file
$debug_log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'raw_input' => $raw_input,
    'decoded_input' => $input,
    'first_name' => $input['first_name'] ?? 'NOT SET',
    'last_name' => $input['last_name'] ?? 'NOT SET',
    'email' => $input['email'] ?? 'NOT SET',
    'phone' => $input['phone'] ?? 'NOT SET',
    'address' => $input['address'] ?? 'NOT SET',
    'role' => $input['role'] ?? 'NOT SET',
];

// Write to a debug file
file_put_contents('signup_debug.txt', print_r($debug_log, true) . "\n\n", FILE_APPEND);

// Return the debug info
echo json_encode($debug_log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
