<?php
require_once 'includes/db.php';

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!$username || !$password || !$confirm_password || !$role) {
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
            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, 'sss', $username, $hashed, $role);
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

<body>
    <div class="login-container">
        <h2>Register</h2>
        <?php if ($error): ?>
            <div class="error" style="color:red; margin-bottom:10px; text-align:center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success" style="color:green; margin-bottom:10px; text-align:center;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <form id="registerForm" method="post" action="register.php">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <label for="role">Role</label>
            <select id="role" name="role" required
                style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; background:#fff; color:#333; margin-bottom:20px;">
                <option hidden value="">Select Role</option>
                <option value="Owner">Owner</option>
                <option value="Staff">Staff</option>
            </select>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>
    <script src="assets/js/register.js"></script>
</body>

</html>