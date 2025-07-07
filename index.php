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
                // Redirect based on role
                if ($role === 'Owner') {
                    header('Location: homepage.php');
                } else {
                    header('Location: stock.php');
                }
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
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="error" style="color:red; margin-bottom:10px; text-align:center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form id="loginForm" method="post" action="index.php">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
    <script src="assets/js/login.js"></script>
</body>
</html> 