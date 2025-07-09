<?php
session_start();
require_once 'includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = mysqli_prepare($conn, "SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) === 1) {
            mysqli_stmt_bind_result($stmt, $user_id, $db_username, $db_password, $role);
            mysqli_stmt_fetch($stmt);
            if (password_verify($password, $db_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $role;
                // Redirect all users to dashboard
                header('Location: homepage.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Seri Perangin Catering</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); min-height:100vh; margin:0; padding:0;">
    <!-- Header with logo/title -->
    <header style="width:100%; padding:32px 0 18px 0; text-align:center;">
        <img src="assets/images/logo.png" alt="Seri Perangin Catering Logo" style="height:64px; margin-bottom:10px;"
            onerror="this.style.display='none'">
        <h1 style="font-size:2.1rem; font-weight:800; color:#388e3c; margin:0; letter-spacing:1px;">Seri Perangin
            Catering</h1>
        <div style="font-size:1.1rem; color:#388e3c; margin-top:4px; font-weight:500;">Inventory & Staff Management
            System</div>
    </header>
    <div class="login-container">
        <h2 style="color:#388e3c; font-weight:700; margin-bottom:18px;">Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error"
                style="color:#c62828; margin-bottom:10px; text-align:center; font-weight:600; background:#ffebee; border-radius:6px; padding:8px 0; width:100%;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form id="loginForm" method="post" action="index.php">
            <label for="username" style="font-weight:600; color:#388e3c;">Username</label>
            <input type="text" id="username" name="username" required>
            <label for="password" style="font-weight:600; color:#388e3c;">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <p style="margin-top:18px; color:#388e3c; font-size:1rem;">Don't have an account? <a href="register.php"
                style="color:#1976d2; text-decoration:underline;">Register here</a></p>
    </div>
    <script src="assets/js/login.js"></script>
</body>

</html>