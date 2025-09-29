<?php
require_once 'session_manager.php';

$sessionManager = new SessionManager();

$sessionManager->logout();
header('Location: login.php?message=logged_out');
exit;
?>