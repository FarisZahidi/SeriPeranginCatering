<?php
session_start();
// Unset all session variables
$_SESSION = array();
// Destroy the session
session_destroy();
// Unset expiry alert session variable on logout
unset($_SESSION['expiring_soon_alert_shown']);
// Redirect to login page
header('Location: index.php');
exit;