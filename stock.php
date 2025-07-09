<?php
include 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/expiry_defaults.php';

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Handle Add Stock Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $item_id = intval($_POST['item_id'] ?? 0);
  $type = $_POST['type'] ?? '';
  $quantity = intval($_POST['quantity'] ?? 0);
  $batch_expiry_date = $_POST['batch_expiry_date'] ?? '';
  $user_id = $_SESSION['user_id'];

  // Fetch before stock level
  $before_stock = null;
  $before_stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(CASE WHEN type = 'in' THEN quantity WHEN type = 'out' THEN -quantity ELSE 0 END), 0) AS stock_level FROM stock_logs WHERE item_id = ?");
  mysqli_stmt_bind_param($before_stmt, 'i', $item_id);
  mysqli_stmt_execute($before_stmt);
  mysqli_stmt_bind_result($before_stmt, $before_stock);
  mysqli_stmt_fetch($before_stmt);
  mysqli_stmt_close($before_stmt);

  if (!$item_id || !$type || !$quantity || $quantity < 1) {
    $_SESSION['error'] = 'All fields are required and quantity must be positive.';
    header('Location: stock.php');
    exit;
  }

  // Validate expiry date for stock in
  if ($type === 'in' && empty($batch_expiry_date)) {
    $_SESSION['error'] = 'Expiry date is required for stock in entries.';
    header('Location: stock.php');
    exit;
  }

  if ($type === 'out') {
    // FIFO: Deduct from earliest-expiry batches
    $remaining = $quantity;
    $batches = [];
    $batch_sql = "SELECT batch_expiry_date, SUM(CASE WHEN type = 'in' THEN quantity WHEN type = 'out' THEN -quantity ELSE 0 END) AS qty
      FROM stock_logs
      WHERE item_id = $item_id AND batch_expiry_date >= CURDATE()
      GROUP BY batch_expiry_date
      HAVING qty > 0
      ORDER BY batch_expiry_date ASC";
    $res = mysqli_query($conn, $batch_sql);
    if ($res) {
      while ($row = mysqli_fetch_assoc($res)) {
        $batches[] = $row;
      }
    }
    $total_available = array_sum(array_column($batches, 'qty'));
    if ($remaining > $total_available) {
      $_SESSION['error'] = 'Cannot stock out more than available non-expired quantity.';
      header('Location: stock.php');
      exit;
    }
    foreach ($batches as $batch) {
      if ($remaining <= 0)
        break;
      $deduct = min($remaining, $batch['qty']);
      $stmt2 = mysqli_prepare($conn, "INSERT INTO stock_logs (item_id, type, quantity, batch_expiry_date, user_id) VALUES (?, ?, ?, ?, ?)");
      mysqli_stmt_bind_param($stmt2, 'isisi', $item_id, $type, $deduct, $batch['batch_expiry_date'], $user_id);
      mysqli_stmt_execute($stmt2);
      mysqli_stmt_close($stmt2);
      $remaining -= $deduct;
    }
    // Fetch after stock level
    $after_stock = null;
    $after_stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(CASE WHEN type = 'in' THEN quantity WHEN type = 'out' THEN -quantity ELSE 0 END), 0) AS stock_level FROM stock_logs WHERE item_id = ?");
    mysqli_stmt_bind_param($after_stmt, 'i', $item_id);
    mysqli_stmt_execute($after_stmt);
    mysqli_stmt_bind_result($after_stmt, $after_stock);
    mysqli_stmt_fetch($after_stmt);
    mysqli_stmt_close($after_stmt);
    $_SESSION['success'] = 'Stock out successful.';
    header('Location: stock.php');
    exit;
  } else {
    // Stock in
    $stmt = mysqli_prepare($conn, "INSERT INTO stock_logs (item_id, type, quantity, batch_expiry_date, user_id) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isisi', $item_id, $type, $quantity, $batch_expiry_date, $user_id);
    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['success'] = 'Stock in successful.';
      header('Location: stock.php');
      exit;
    } else {
      $_SESSION['error'] = 'Failed to add stock entry.';
      header('Location: stock.php');
      exit;
    }
    mysqli_stmt_close($stmt);
  }
}

include 'includes/navbar.php';

// Fetch inventory for dropdown with category info
$items = [];
$result = mysqli_query($conn, "SELECT * FROM inventory ORDER BY item_name ASC");
if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
  }
}

// Get categories for expiry defaults
$categories = [];
foreach ($items as $item) {
  if (!in_array($item['category'], $categories)) {
    $categories[] = $item['category'];
  }
}

// Fetch current stock levels with batch expiry tracking
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'item_name';
$order = (isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC') ? 'DESC' : 'ASC';
$allowed_sorts = ['item_name', 'stock_level', 'earliest_expiry'];
if (!in_array($sort, $allowed_sorts))
  $sort = 'item_name';
$where = [];
if ($search) {
  $where[] = "i.item_name LIKE '%$search%'";
}
// We'll filter status after fetching, since it's computed in PHP
$stock_sql = "SELECT
  i.item_id,
  i.item_name,
  IFNULL(SUM(CASE WHEN s.type = 'in' AND (s.batch_expiry_date IS NULL OR s.batch_expiry_date >= CURDATE()) THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END), 0) AS stock_level,
  MIN(CASE WHEN s.type = 'in' AND s.batch_expiry_date >= CURDATE() THEN s.batch_expiry_date END) AS earliest_expiry
FROM inventory i
LEFT JOIN stock_logs s ON i.item_id = s.item_id
";
if ($where) {
  $stock_sql .= 'WHERE ' . implode(' AND ', $where) . "\n";
}
$stock_sql .= "GROUP BY i.item_id, i.item_name\nORDER BY $sort $order";
$result2 = mysqli_query($conn, $stock_sql);
$stock_levels = [];
if ($result2) {
  while ($row = mysqli_fetch_assoc($result2)) {
    // Compute status for filtering
    $status = 'Sufficient';
    if ($row['stock_level'] < 10)
      $status = 'Low';
    if ($row['earliest_expiry'] && $row['earliest_expiry'] < date('Y-m-d'))
      $status = 'Expired';
    if ($status_filter && $status !== $status_filter)
      continue;
    $stock_levels[$row['item_id']] = $row;
  }
}

// Fetch recent stock logs with batch expiry dates
$logs = [];
$result3 = mysqli_query($conn, "SELECT s.*, u.username, i.item_name FROM stock_logs s LEFT JOIN users u ON s.user_id = u.user_id LEFT JOIN inventory i ON s.item_id = i.item_id ORDER BY s.log_date DESC LIMIT 12");
if ($result3) {
  while ($row = mysqli_fetch_assoc($result3)) {
    $logs[] = $row;
  }
}

// Fetch all batches for each item (grouped by expiry date)
$batch_details = [];
$batch_sql = "SELECT i.item_id, s.batch_expiry_date, SUM(CASE WHEN s.type = 'in' THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END) AS batch_quantity, MIN(s.log_date) as stock_in_date
FROM inventory i
LEFT JOIN stock_logs s ON i.item_id = s.item_id
WHERE s.batch_expiry_date IS NOT NULL
GROUP BY i.item_id, s.batch_expiry_date
HAVING batch_quantity > 0
ORDER BY i.item_id, s.batch_expiry_date ASC";
$res_batch = mysqli_query($conn, $batch_sql);
if ($res_batch) {
  while ($row = mysqli_fetch_assoc($res_batch)) {
    $batch_details[$row['item_id']][] = $row;
  }
}
?>
<main style="margin-left:230px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
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
    <h1 style="font-size:2rem; font-weight:700;">Stock In/Out</h1>
    <div style="display:flex; gap:8px;">
      <button class="btn btn-info" id="helpBtn" style="font-size:0.9rem;">
        <i class="fa-solid fa-question-circle"></i> Help
      </button>
      <button class="btn btn-accent" id="quickStockBtn"><i class="fa-solid fa-plus-minus"></i> Quick In/Out</button>
    </div>
  </div>
  <div class="flex flex-between" style="gap:24px; flex-wrap:wrap;">
    <div class="card shadow" style="flex:2; min-width:320px;">
      <h2 class="mb-2" style="font-size:1.2rem; font-weight:600;"><i class="fa-solid fa-boxes-stacked text-primary"></i>
        Current Stock Levels</h2>
      <!-- Sort/Filter Controls -->
      <form method="get" style="margin-bottom:12px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
        <input type="text" name="search" placeholder="Search item name..."
          value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
          style="padding:6px 10px; border-radius:4px; border:1px solid #ccc; min-width:160px;">
        <select name="status" style="padding:6px 10px; border-radius:4px; border:1px solid #ccc;">
          <option value="">All Status</option>
          <option value="Sufficient" <?php if (isset($_GET['status']) && $_GET['status'] === 'Sufficient')
            echo 'selected'; ?>>Sufficient</option>
          <option value="Low" <?php if (isset($_GET['status']) && $_GET['status'] === 'Low')
            echo 'selected'; ?>>Low
          </option>
          <option value="Expired" <?php if (isset($_GET['status']) && $_GET['status'] === 'Expired')
            echo 'selected'; ?>>
            Expired</option>
        </select>
        <select name="sort" style="padding:6px 10px; border-radius:4px; border:1px solid #ccc;">
          <option value="item_name" <?php if (!isset($_GET['sort']) || $_GET['sort'] === 'item_name')
            echo 'selected'; ?>>
            Sort by Name</option>
          <option value="stock_level" <?php if (isset($_GET['sort']) && $_GET['sort'] === 'stock_level')
            echo 'selected'; ?>>Sort by Stock</option>
          <option value="earliest_expiry" <?php if (isset($_GET['sort']) && $_GET['sort'] === 'earliest_expiry')
            echo 'selected'; ?>>Sort by Expiry</option>
        </select>
        <select name="order" style="padding:6px 10px; border-radius:4px; border:1px solid #ccc;">
          <option value="ASC" <?php if (!isset($_GET['order']) || $_GET['order'] === 'ASC')
            echo 'selected'; ?>>Ascending
          </option>
          <option value="DESC" <?php if (isset($_GET['order']) && $_GET['order'] === 'DESC')
            echo 'selected'; ?>>
            Descending
          </option>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
        <?php if ($_GET): ?><a href="stock.php" class="btn btn-secondary">Reset</a><?php endif; ?>
      </form>
      <div style="overflow-x:auto;">
        <table class="table" id="stockTable">
          <thead>
            <tr>
              <th>Item</th>
              <th>Stock</th>
              <th>Status</th>
              <th>Earliest Expiry</th>
              <th>Batches</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stock_levels as $row): ?>
              <?php
              $status = 'Sufficient';
              $statusClass = 'text-success';
              $icon = 'fa-circle-check';
              if ($row['stock_level'] < 10) {
                $status = 'Low';
                $statusClass = 'text-warning';
                $icon = 'fa-triangle-exclamation';
              }
              if ($row['earliest_expiry'] && $row['earliest_expiry'] < date('Y-m-d')) {
                $status = 'Expired';
                $statusClass = 'text-danger';
                $icon = 'fa-calendar-xmark';
              }
              $item_id = $row['item_id'];
              $batch_count = isset($batch_details[$item_id]) ? count($batch_details[$item_id]) : 0;
              ?>
              <tr>
                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                <td><?php echo $row['stock_level']; ?></td>
                <td><i class="fa-solid <?php echo $icon; ?> <?php echo $statusClass; ?>"></i> <span
                    class="<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                <td>
                  <?php
                  if ($row['earliest_expiry']) {
                    $expiry_date = new DateTime($row['earliest_expiry']);
                    $today = new DateTime();
                    $diff = $today->diff($expiry_date);
                    $days_until = $diff->invert ? -$diff->days : $diff->days;

                    if ($days_until < 0) {
                      echo htmlspecialchars($row['earliest_expiry']) . ' <span style="color: #dc3545; font-weight: 600;">(Expired ' . abs($days_until) . ' days ago)</span>';
                    } elseif ($days_until == 0) {
                      echo htmlspecialchars($row['earliest_expiry']) . ' <span style="color: #ffc107; font-weight: 600;">(Expires today)</span>';
                    } elseif ($days_until <= 7) {
                      echo htmlspecialchars($row['earliest_expiry']) . ' <span style="color: #fd7e14; font-weight: 600;">(' . $days_until . ' days left)</span>';
                    } else {
                      echo htmlspecialchars($row['earliest_expiry']) . ' <span style="color: #28a745; font-weight: 600;">(' . $days_until . ' days left)</span>';
                    }
                  } else {
                    echo '-';
                  }
                  ?>
                </td>
                <td><?php echo $batch_count; ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-secondary batch-details-btn"
                    data-item-id="<?php echo $item_id; ?>">
                    <i class="fa-solid fa-chevron-down"></i> Details
                  </button>
                </td>
              </tr>
              <tr class="batch-details-row" id="batch-details-<?php echo $item_id; ?>"
                style="display:none; background:#f8f9fa;">
                <td colspan="6">
                  <div class="batch-details-table-wrapper"></div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card shadow" style="flex:1; min-width:280px; max-width:400px;">
      <h2 class="mb-2" style="font-size:1.2rem; font-weight:600;"><i
          class="fa-solid fa-clock-rotate-left text-info"></i> Recent Stock Movements</h2>
      <ul style="list-style:none; padding:0; max-height:340px; overflow-y:auto;">
        <?php foreach ($logs as $log): ?>
          <li class="flex flex-between mb-2"
            style="align-items:center; border-bottom:1px solid var(--border); padding-bottom:6px;">
            <div style="flex:1;">
              <div><i
                  class="fa-solid fa-<?php echo $log['type'] === 'in' ? 'arrow-down text-success' : 'arrow-up text-danger'; ?>"></i>
                <b><?php echo htmlspecialchars($log['item_name']); ?></b> <span class="text-muted"
                  style="font-size:0.95rem;">(<?php echo ucfirst($log['type']); ?>)</span>
              </div>
              <?php if ($log['type'] === 'in' && $log['batch_expiry_date']): ?>
                <div style="font-size:0.85rem; color: #6c757d; margin-top:2px;">
                  Expires: <?php echo date('d M Y', strtotime($log['batch_expiry_date'])); ?>
                </div>
              <?php endif; ?>
            </div>
            <span class="badge rounded <?php echo $log['type'] === 'in' ? 'bg-success' : 'bg-danger'; ?>"
              style="padding:6px 14px; font-size:0.98rem;"> <?php echo $log['quantity']; ?> </span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <!-- Modal for Quick In/Out -->
  <div id="stockModal" class="modal"
    style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.18); z-index:999; align-items:center; justify-content:center;">
    <div class="card shadow" style="max-width:400px; width:100%; position:relative;">
      <button id="closeStockModal"
        style="position:absolute; top:12px; right:12px; background:none; border:none; font-size:1.3rem; color:var(--danger); cursor:pointer;"><i
          class="fa-solid fa-xmark"></i></button>
      <h2 id="stockModalTitle" style="font-size:1.2rem; font-weight:600; margin-bottom:16px;">Quick Stock In/Out</h2>
      <form id="stockForm" method="post" action="stock.php">
        <label for="modal_item_id">Item <i class="fa-solid fa-circle-question text-info"
            title="Select the item."></i></label>
        <select id="modal_item_id" name="item_id" required>
          <option hidden value="">Select Item</option>
          <?php foreach ($items as $item): ?>
            <option value="<?php echo $item['item_id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?></option>
          <?php endforeach; ?>
        </select>
        <label for="modal_type">Type <i class="fa-solid fa-circle-question text-info"
            title="Stock In or Out."></i></label>
        <select id="modal_type" name="type" required>
          <option value="in">Stock In</option>
          <option value="out">Stock Out</option>
        </select>
        <label for="modal_quantity">Quantity <i class="fa-solid fa-circle-question text-info"
            title="Enter the quantity."></i></label>
        <input type="number" id="modal_quantity" name="quantity" min="1" required>
        <div id="expiry_date_group">
          <label for="modal_batch_expiry_date">Expiry Date <i class="fa-solid fa-circle-question text-info"
              title="Required for stock in entries."></i></label>
          <div style="margin-bottom:8px;">
            <input type="date" id="modal_batch_expiry_date" name="batch_expiry_date" required>
            <div id="expiry_suggestion" style="font-size:0.85rem; color:#6c757d; margin-top:4px;"></div>
          </div>
          <div style="display:flex; gap:8px; margin-bottom:16px;">
            <button type="button" class="btn btn-sm btn-outline-primary" id="suggestExpiryBtn">
              <i class="fa-solid fa-lightbulb"></i> Suggest
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearExpiryBtn">
              <i class="fa-solid fa-eraser"></i> Clear
            </button>
          </div>
        </div>
        <div class="flex flex-between mt-2">
          <button type="submit" class="btn btn-accent" id="saveStockBtn" name="stock_entry"><i
              class="fa-solid fa-save"></i> Save</button>
          <button type="button" class="btn btn-danger" id="cancelStockBtn"><i class="fa-solid fa-xmark"></i>
            Cancel</button>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Pass PHP data to JavaScript
  window.itemData = <?php echo json_encode($items); ?>;
  window.expiryDefaults = <?php echo json_encode($EXPIRY_DEFAULTS); ?>;
  window.batchDetails = <?php echo json_encode($batch_details); ?>;
</script>
<script src="assets/js/stock.js"></script>
</body>

</html>