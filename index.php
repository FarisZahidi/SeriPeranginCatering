<?php
session_start();
require_once 'includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = mysqli_prepare($conn, "SELECT user_id, name, username, password, role FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) === 1) {
            mysqli_stmt_bind_result($stmt, $user_id, $db_name, $db_username, $db_password, $role);
            mysqli_stmt_fetch($stmt);
            if (password_verify($password, $db_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $db_name;
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

<body class="auth-bg">
    <!-- Overlay handled by CSS -->
    <div class="auth-card-modern">
        <div class="auth-logo">
            <!-- Replace with your logo image if desired -->
            <svg width="72" height="72" viewBox="0 0 56 56" fill="none">
                <circle cx="28" cy="28" r="28" fill="#43a047" />
                <g>
                    <rect x="16" y="36" width="24" height="6" rx="3" fill="#fff" />
                    <rect x="24" y="14" width="8" height="18" rx="4" fill="#fff" />
                    <rect x="20" y="10" width="16" height="6" rx="3" fill="#fff" />
                </g>
                <g>
                    <ellipse cx="28" cy="44" rx="10" ry="2.5" fill="#c8e6c9" opacity="0.7" />
                </g>
            </svg>
        </div>
        <h2>Welcome Back</h2>
        <p class="auth-subtitle">Seri Perangin Catering<br><span style='font-size:0.98rem; color:#fd7e14;'>Inventory
                Management System</span></p>
        <?php if (!empty($error)): ?>
            <div class="error"><i class="fa-solid fa-triangle-exclamation" style="font-size:1.2em;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="index.php" autocomplete="off">
            <div class="form-group-modern">
                <input type="text" id="username" name="username" required placeholder=" ">
                <label for="username">Username</label>
            </div>
            <div class="form-group-modern">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label for="password">Password</label>
            </div>
            <button type="submit" class="auth-btn"><i class="fa fa-sign-in-alt"></i> Login</button>
        </form>
    </div>
</body>

</html>