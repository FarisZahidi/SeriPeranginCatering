<?php
require_once 'includes/db.php';

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!$name || !$username || !$password || !$confirm_password || !$role) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username exists
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Username already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, 'ssss', $name, $username, $hashed, $role);
            if (mysqli_stmt_execute($stmt2)) {
                $success = 'Registration successful! You can now <a href=\'index.php\'>login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            mysqli_stmt_close($stmt2);
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Seri Perangin Catering</title>
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
        <h2 style="color:#388e3c; font-weight:700; margin-bottom:18px;">Register</h2>
        <?php if ($error): ?>
            <div class="error"
                style="color:#c62828; margin-bottom:10px; text-align:center; font-weight:600; background:#ffebee; border-radius:6px; padding:8px 0; width:100%;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"
                style="color:#2e7d32; margin-bottom:10px; text-align:center; font-weight:600; background:#e8f5e9; border-radius:6px; padding:8px 0; width:100%;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <form id="registerForm" method="post" action="register.php">
            <label for="name" style="font-weight:600; color:#388e3c;">Name</label>
            <input type="text" id="name" name="name" required>
            <label for="username" style="font-weight:600; color:#388e3c;">Username</label>
            <input type="text" id="username" name="username" required>
            <label for="password" style="font-weight:600; color:#388e3c;">Password</label>
            <input type="password" id="password" name="password" required>
            <label for="confirm_password" style="font-weight:600; color:#388e3c;">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <label for="role" style="font-weight:600; color:#388e3c;">Role</label>
            <select id="role" name="role" required>
                <option hidden value="">Select Role</option>
                <option value="Owner">Owner</option>
                <option value="Staff">Staff</option>
            </select>
            <button type="submit">Register</button>
        </form>
        <p style="margin-top:18px; color:#388e3c; font-size:1rem;">Already have an account? <a href="index.php"
                style="color:#1976d2; text-decoration:underline;">Login here</a></p>
    </div>
    <script src="assets/js/register.js"></script>
</body>

</html>