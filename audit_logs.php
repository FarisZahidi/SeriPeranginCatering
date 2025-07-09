<?php
// Handle delete all logs (must be before any output)
if (session_status() === PHP_SESSION_NONE)
  session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_logs'])) {
  if ($_SESSION['role'] === 'Owner') {
    require_once 'includes/db.php';
    mysqli_query($conn, 'TRUNCATE TABLE audit_logs');
    $_SESSION['success'] = 'All audit logs have been deleted.';
    header('Location: audit_logs.php');
    exit;
  }
}
$required_role = 'Owner';
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

// Fetch audit logs with user info and item name
$sql = "SELECT a.*, u.name, i.item_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.user_id LEFT JOIN inventory i ON a.item_id = i.item_id ORDER BY a.created_at DESC LIMIT 200";
$result = mysqli_query($conn, $sql);
$logs = [];
if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
  }
}
function pretty_audit_data($json)
{
  if (!$json)
    return '';
  $arr = json_decode($json, true);
  if (!$arr)
    return htmlspecialchars($json);
  // Remove fields that are not useful for display
  unset($arr['image_path'], $arr['created_at']);
  if (empty($arr))
    return '';
  $out = '<ul class="audit-data-list">';
  foreach ($arr as $k => $v) {
    $label = ucwords(str_replace('_', ' ', $k));
    $out .= '<li><span class="audit-data-label">' . htmlspecialchars($label) . ':</span> <span class="audit-data-value">' . htmlspecialchars($v) . '</span></li>';
  }
  $out .= '</ul>';
  return $out;
}
?>
<link rel="stylesheet" href="assets/css/global.css?v=2">
<main class="audit-main">
  <div class="glassy-card card shadow audit-card">
    <div class="flex flex-between mb-2">
      <h1 class="audit-title"><i class="fa-solid fa-clipboard-list text-info"></i> Audit Log</h1>
      <form id="deleteAllLogsForm" method="post" style="margin:0;">
        <button type="button" class="btn btn-danger" id="deleteAllLogsBtn"><i class="fa-solid fa-trash"></i> Delete All
          Logs</button>
        <input type="hidden" name="delete_all_logs" value="1">
      </form>
    </div>
    <div class="audit-table-wrapper">
      <table class="table audit-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Action</th>
            <th>Name</th>
            <th>Before</th>
            <th>After</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><span class="text-info audit-user">
                  <?php echo htmlspecialchars($log['name'] ?? $log['user_id']); ?></span></td>
              <td>
                <span class="badge <?php
                if ($log['action'] === 'delete')
                  echo 'bg-danger';
                else if ($log['action'] === 'edit')
                  echo 'bg-warning';
                else if ($log['action'] === 'stock_in')
                  echo 'bg-success';
                else if ($log['action'] === 'stock_out')
                  echo 'bg-info';
                else
                  echo 'bg-secondary';
                ?>">
                  <?php
                  if ($log['action'] === 'stock_in')
                    echo 'Stock In';
                  else if ($log['action'] === 'stock_out')
                    echo 'Stock Out';
                  else if ($log['action'] === 'edit')
                    echo 'Edit';
                  else if ($log['action'] === 'delete')
                    echo 'Delete';
                  else
                    echo htmlspecialchars(ucfirst($log['action']));
                  ?>
                </span>
              </td>
              <td><span class="text-muted audit-item"><?php echo htmlspecialchars($log['item_name'] ?? ''); ?></span></td>
              <td><?php echo pretty_audit_data($log['before_data']); ?></td>
              <td><?php echo pretty_audit_data($log['after_data']); ?></td>
              <td><span class="text-info audit-time"><i class="fa-regular fa-clock"></i>
                  <?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (empty($logs)): ?>
        <div class="text-muted audit-empty">
          <i class="fa-regular fa-face-smile-beam"></i><br>No audit logs found.
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.getElementById('deleteAllLogsBtn')?.addEventListener('click', function (e) {
    Swal.fire({
      title: 'Delete All Audit Logs?',
      text: 'This action cannot be undone. All audit logs will be permanently deleted.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete all',
      cancelButtonText: 'Cancel',
      customClass: { popup: 'swal2-toast-glassy-error' }
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('deleteAllLogsForm').submit();
      }
    });
  });
</script>
<?php if (!empty($_SESSION['success'])): ?>
  <script>
    window.addEventListener('DOMContentLoaded', function () {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: <?php echo json_encode($_SESSION['success']); ?>,
        showConfirmButton: false,
        timer: 3000,
        customClass: { popup: 'swal2-toast-glassy-success' }
      });
    });
  </script>
  <?php unset($_SESSION['success']); endif; ?>