<?php
// logout.php - Revised with OOP implementation
require_once 'session_manager.php';

// Initialize session manager
$sessionManager = new SessionManager();

// Logout user and redirect
$sessionManager->logout();
header('Location: login.php?message=logged_out');
exit;
?>