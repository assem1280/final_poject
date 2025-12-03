<?php
// Keep session alive - called via JavaScript periodically
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Set session configuration before starting
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 86400); // 24 hours

session_start();

if (isset($_SESSION['profile_id'])) {
    // Session is active
    $_SESSION['last_activity'] = time();
    
    // Reset session cookie expiration to extend it
    if (isset($_COOKIE[session_name()])) {
        setcookie(
            session_name(),
            session_id(),
            time() + 86400, // 24 hours from now
            '/',
            '',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            true // httponly
        );
    }
    
    echo json_encode([
        'success' => true, 
        'session_active' => true,
        'profile_id' => $_SESSION['profile_id'],
        'role' => $_SESSION['role'] ?? null,
        'message' => 'Session refreshed'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'session_active' => false,
        'message' => 'No active session'
    ]);
}
?>
