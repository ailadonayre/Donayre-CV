<?php
// session_keepalive.php
require_once 'auth.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    // Update session timestamp
    $_SESSION['last_activity'] = time();
    echo json_encode(['status' => 'success', 'message' => 'Session refreshed']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
}
?>