<?php
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
function pretty_json($json)
{
  if (!$json)
    return '';
  $arr = json_decode($json, true);
  if (!$arr)
    return htmlspecialchars($json);
  // If only stock_level, show just the value
  if (count($arr) === 1 && isset($arr['stock_level'])) {
    return '<span style="font-weight:600; color:#007b5e;">' . htmlspecialchars($arr['stock_level']) . '</span>';
  }
  return '<pre style="white-space:pre-wrap;">' . htmlspecialchars(json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
}
?>
<main style="margin-left:230px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
  <h1 style="font-size:2rem; font-weight:700;">Audit Log</h1>
  <div class="card shadow" style="overflow-x:auto;">
    <table class="table">
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
        <?php foreach (
          $logs as $log): ?>
          <tr>
            <td><?php echo htmlspecialchars($log['name'] ?? $log['user_id']); ?></td>
            <td><span class="badge 
              <?php
              if ($log['action'] === 'delete')
                echo 'badge-danger';
              else if ($log['action'] === 'edit')
                echo 'badge-warning';
              else if ($log['action'] === 'stock_in')
                echo 'badge-success';
              else if ($log['action'] === 'stock_out')
                echo 'badge-primary';
              else
                echo 'badge-info';
              ?>">
                <?php
                if ($log['action'] === 'stock_in')
                  echo 'Stock In';
                else if ($log['action'] === 'stock_out')
                  echo 'Stock Out';
                else
                  echo htmlspecialchars(ucfirst($log['action']));
                ?>
              </span></td>
            <td><?php echo htmlspecialchars($log['item_name'] ?? ''); ?></td>
            <td><?php echo pretty_json($log['before_data']); ?></td>
            <td><?php echo pretty_json($log['after_data']); ?></td>
            <td><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($logs)): ?>
      <div class="text-muted" style="padding:24px; text-align:center;">No audit logs found.</div>
    <?php endif; ?>
  </div>
</main>