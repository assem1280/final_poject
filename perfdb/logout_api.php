<?php
// Logout API
// End session and clear user data
header('Content-Type: application/json');
session_start();

// Clear all session data
$_SESSION = array();

// Delete session file
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully',
    'redirect' => '../docs/index.html'
]);
?>
