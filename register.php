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
        <h2>Create Account</h2>
        <p class="auth-subtitle">Seri Perangin Catering<br><span style='font-size:0.98rem; color:#fd7e14;'>Inventory
                Management System</span></p>
        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="register.php" autocomplete="off">
            <div class="form-group-modern">
                <input type="text" id="name" name="name" required placeholder=" ">
                <label for="name">Name</label>
            </div>
            <div class="form-group-modern">
                <input type="text" id="username" name="username" required placeholder=" ">
                <label for="username">Username</label>
            </div>
            <div class="form-group-modern">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label for="password">Password</label>
            </div>
            <div class="form-group-modern">
                <input type="password" id="confirm_password" name="confirm_password" required placeholder=" ">
                <label for="confirm_password">Confirm Password</label>
            </div>
            <div class="form-group-modern">
                <select id="role" name="role" required>
                    <option hidden value="">Select Role</option>
                    <option value="Owner">Owner</option>
                    <option value="Staff">Staff</option>
                </select>
                <label for="role">Role</label>
            </div>
            <button type="submit" class="auth-btn"><i class="fa fa-user-plus"></i> Register</button>
        </form>
        <div class="auth-divider"><span>or</span></div>
        <div class="auth-switch">
            Already have an account? <a href="index.php">Login here</a>
        </div>
    </div>
</body>

</html>