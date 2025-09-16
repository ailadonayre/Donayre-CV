<?php
// logout.php
require_once 'auth.php';

// Logout user and redirect
logoutUser();
header('Location: login.php?message=logged_out');
exit;
?>