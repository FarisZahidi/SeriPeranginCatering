<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
// Optional: Role-based check
if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    header('Location: index.php');
    exit;
}
?>