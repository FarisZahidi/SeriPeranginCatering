<?php
$required_role = 'Owner';
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$edit_user = null;

// Handle Add Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? '';
  if (!$username || !$password || !$role) {
    $error = 'All fields are required.';
  } else {
    // Check if username exists
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
      $_SESSION['error'] = 'Username already exists.';
      header('Location: staff.php');
      exit;
    } else {
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt2 = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
      mysqli_stmt_bind_param($stmt2, 'sss', $username, $hashed, $role);
      if (mysqli_stmt_execute($stmt2)) {
        $_SESSION['success'] = 'Staff added successfully.';
        header('Location: staff.php');
        exit;
      } else {
        $_SESSION['error'] = 'Failed to add staff.';
        header('Location: staff.php');
        exit;
      }
      mysqli_stmt_close($stmt2);
    }
    mysqli_stmt_close($stmt);
  }
}

// Handle Delete Staff
if (isset($_GET['delete'])) {
  $delete_id = intval($_GET['delete']);
  if ($delete_id == $_SESSION['user_id']) {
    $_SESSION['error'] = 'You cannot delete your own account.';
    header('Location: staff.php');
    exit;
  } else {
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $delete_id);
    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['success'] = 'Staff deleted successfully.';
      header('Location: staff.php');
      exit;
    } else {
      $_SESSION['error'] = 'Failed to delete staff.';
      header('Location: staff.php');
      exit;
    }
    mysqli_stmt_close($stmt);
  }
}

// Handle Edit Staff (fetch data)
if (isset($_GET['edit'])) {
  $edit_id = intval($_GET['edit']);
  $stmt = mysqli_prepare($conn, "SELECT user_id, username, role FROM users WHERE user_id = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $edit_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $edit_user = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
}

// Handle Update Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
  $edit_id = intval($_POST['edit_id']);
  $username = trim($_POST['username'] ?? '');
  $role = $_POST['role'] ?? '';
  $password = $_POST['password'] ?? '';
  if (!$username || !$role) {
    $error = 'Username and role are required.';
  } else {
    // Check if username is taken by another user
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'si', $username, $edit_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
      $_SESSION['error'] = 'Username already exists.';
      header('Location: staff.php');
      exit;
    } else {
      if ($password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt2 = mysqli_prepare($conn, "UPDATE users SET username = ?, password = ?, role = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt2, 'sssi', $username, $hashed, $role, $edit_id);
      } else {
        $stmt2 = mysqli_prepare($conn, "UPDATE users SET username = ?, role = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt2, 'ssi', $username, $role, $edit_id);
      }
      if (mysqli_stmt_execute($stmt2)) {
        $_SESSION['success'] = 'Staff updated successfully.';
        header('Location: staff.php');
        exit;
      } else {
        $_SESSION['error'] = 'Failed to update staff.';
        header('Location: staff.php');
        exit;
      }
      mysqli_stmt_close($stmt2);
    }
    mysqli_stmt_close($stmt);
  }
}

// Fetch staff list
$staff = [];
$result = mysqli_query($conn, "SELECT user_id, username, role, created_at FROM users ORDER BY created_at DESC");
if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $staff[] = $row;
  }
}
$roles = [
  'Owner' => ['badge' => 'bg-success', 'icon' => 'fa-crown', 'desc' => 'Full access to all modules'],
  'Staff' => ['badge' => 'bg-info', 'icon' => 'fa-user', 'desc' => 'Stock in/out, update usage only'],
];
?>
<main style="margin-left:220px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
  <?php if ($error): ?>
    <script>
      window.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: <?php echo json_encode($error); ?>,
          showConfirmButton: false,
          timer: 3000
        });
      });
    </script>
  <?php endif; ?>
  <?php if ($success): ?>
    <script>
      window.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: <?php echo json_encode($success); ?>,
          showConfirmButton: false,
          timer: 3000
        });
      });
    </script>
  <?php endif; ?>
  <div class="flex flex-between mb-3" style="align-items:center; flex-wrap:wrap; gap:16px;">
    <h1 style="font-size:2rem; font-weight:700;">Staff Management</h1>
    <button class="btn btn-accent" id="addStaffBtn"><i class="fa-solid fa-user-plus"></i> Add Staff</button>
  </div>
  <div class="card shadow">
    <div style="overflow-x:auto;">
      <table class="table" id="staffTable">
        <thead>
          <tr>
            <th>Username</th>
            <th>Role</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($staff as $user): ?>
            <tr>
              <td><?php echo htmlspecialchars($user['username']); ?></td>
              <td><?php echo htmlspecialchars($user['role']); ?></td>
              <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
              <td>
                <button class="btn btn-warning btn-sm editStaffBtn" data-id="<?php echo $user['user_id']; ?>"><i
                    class="fa-solid fa-pen"></i></button>
                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                  <a href="staff.php?delete=<?php echo $user['user_id']; ?>" class="btn btn-danger btn-sm deleteStaffBtn"
                    data-id="<?php echo $user['user_id']; ?>"><i class="fa-solid fa-trash"></i></a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Modal for Add/Edit Staff -->
  <div id="staffModal" class="modal"
    style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.18); z-index:999; align-items:center; justify-content:center;">
    <div class="card shadow" style="max-width:400px; width:100%; position:relative;">
      <button id="closeStaffModal"
        style="position:absolute; top:12px; right:12px; background:none; border:none; font-size:1.3rem; color:var(--danger); cursor:pointer;"><i
          class="fa-solid fa-xmark"></i></button>
      <h2 id="staffModalTitle" style="font-size:1.2rem; font-weight:600; margin-bottom:16px;"></h2>
      <form id="staffForm" method="post" action="staff.php">
        <input type="hidden" name="user_id" id="modal_user_id">
        <input type="hidden" name="edit_id" id="modal_edit_id">
        <label for="modal_username">Username <i class="fa-solid fa-circle-question text-info"
            title="Enter the staff username."></i></label>
        <input type="text" name="username" id="modal_username" required>
        <label for="modal_password">Password <i class="fa-solid fa-circle-question text-info"
            title="Set a password. Leave blank to keep current (edit only)."></i></label>
        <input type="password" name="password" id="modal_password">
        <label for="modal_role">Role <i class="fa-solid fa-circle-question text-info"
            title="Select the staff role."></i></label>
        <select name="role" id="modal_role" required>
          <option hidden value="">Select Role</option>
          <?php foreach ($roles as $role => $info): ?>
            <option value="<?php echo $role; ?>"><?php echo $role; ?></option>
          <?php endforeach; ?>
        </select>
        <div class="text-muted mb-2 mt-2" id="roleDesc" style="font-size:0.98rem;"></div>
        <div class="flex flex-between mt-2">
          <button type="submit" class="btn btn-accent" id="saveStaffBtn" name="add_staff"><i
              class="fa-solid fa-save"></i> Save</button>
          <button type="button" class="btn btn-danger" id="cancelStaffBtn"><i class="fa-solid fa-xmark"></i>
            Cancel</button>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/staff.js"></script>
<script>
  // Update JS to set button name and hidden edit_id for Add/Edit
  const staffForm = document.getElementById('staffForm');
  const saveStaffBtn = document.getElementById('saveStaffBtn');
  const modalEditStaffId = document.getElementById('modal_edit_id');
  Array.from(document.getElementsByClassName('editStaffBtn')).forEach(function (btn) {
    btn.addEventListener('click', function () {
      saveStaffBtn.name = 'update_staff';
      modalEditStaffId.value = btn.getAttribute('data-id');
    });
  });
  addStaffBtn.addEventListener('click', function () {
    saveStaffBtn.name = 'add_staff';
    modalEditStaffId.value = '';
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>