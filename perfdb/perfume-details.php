<?php
// Lightweight compatibility redirect for legacy links that used "perfume-details.php"
// Preserve query string parameter `id` and forward to the new `product-details.php`.
$id = isset($_GET['id']) ? $_GET['id'] : null;
if ($id) {
    // Use a relative redirect to the new filename in the same directory
    header('Location: product-details.php?id=' . urlencode($id));
    exit;
}

// If no id provided, redirect to products listing or show a simple message.
header('Location: ../selected/selection.html');
exit;

?>