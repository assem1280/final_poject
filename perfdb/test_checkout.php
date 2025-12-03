<?php
// Simple test to verify checkout.php works without fatal errors
header('Content-Type: application/json');
session_start();

// Test without session (should redirect to login)
echo "Test 1: Checkout without login\n";
include 'checkout.php';
?>
