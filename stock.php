<?php
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

$error = '';
$success = '';

// Handle Add Stock Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $user_id = $_SESSION['user_id'];
    if (!$item_id || !$type || !$quantity || $quantity < 1) {
        $error = 'All fields are required and quantity must be positive.';
    } else {
        // Get current stock
        $stock_sql = "SELECT IFNULL(SUM(CASE WHEN type = 'in' THEN quantity WHEN type = 'out' THEN -quantity ELSE 0 END), 0) AS stock_level FROM stock_logs WHERE item_id = ?";
        $stmt = mysqli_prepare($conn, $stock_sql);
        mysqli_stmt_bind_param($stmt, 'i', $item_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $stock_level);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($type === 'out' && $quantity > $stock_level) {
            $error = 'Cannot stock out more than available quantity.';
        } else {
            $stmt2 = mysqli_prepare($conn, "INSERT INTO stock_logs (item_id, type, quantity, user_id) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, 'isii', $item_id, $type, $quantity, $user_id);
            if (mysqli_stmt_execute($stmt2)) {
                $success = 'Stock entry added successfully.';
            } else {
                $error = 'Failed to add stock entry.';
            }
            mysqli_stmt_close($stmt2);
        }
    }
}

// Fetch inventory for dropdown
$items = [];
$result = mysqli_query($conn, "SELECT * FROM inventory ORDER BY item_name ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
}

// Fetch current stock levels
$stock_levels = [];
$stock_sql = "SELECT i.item_id, i.item_name, IFNULL(SUM(CASE WHEN s.type = 'in' THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END), 0) AS stock_level, i.expiry_date FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id GROUP BY i.item_id, i.item_name, i.expiry_date ORDER BY i.item_name ASC";
$result2 = mysqli_query($conn, $stock_sql);
if ($result2) {
    while ($row = mysqli_fetch_assoc($result2)) {
        $stock_levels[$row['item_id']] = $row;
    }
}

// Fetch recent stock logs
$logs = [];
$result3 = mysqli_query($conn, "SELECT s.*, u.username, i.item_name FROM stock_logs s LEFT JOIN users u ON s.user_id = u.user_id LEFT JOIN inventory i ON s.item_id = i.item_id ORDER BY s.log_date DESC LIMIT 12");
if ($result3) {
    while ($row = mysqli_fetch_assoc($result3)) {
        $logs[] = $row;
    }
}
?>
<main style="margin-left:220px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
<?php if ($error): ?>
  <div class="card bg-danger text-white mb-2" style="padding:14px; font-weight:500;">
    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
  </div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="card bg-success text-white mb-2" style="padding:14px; font-weight:500;">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
  </div>
<?php endif; ?>
  <div class="flex flex-between mb-3" style="align-items:center; flex-wrap:wrap; gap:16px;">
    <h1 style="font-size:2rem; font-weight:700;">Stock In/Out</h1>
    <button class="btn btn-accent" id="quickStockBtn"><i class="fa-solid fa-plus-minus"></i> Quick In/Out</button>
  </div>
  <div class="flex flex-between" style="gap:24px; flex-wrap:wrap;">
    <div class="card shadow" style="flex:2; min-width:320px;">
      <h2 class="mb-2" style="font-size:1.2rem; font-weight:600;"><i class="fa-solid fa-boxes-stacked text-primary"></i> Current Stock Levels</h2>
      <div style="overflow-x:auto;">
        <table class="table" id="stockTable">
          <thead>
            <tr>
              <th>Item</th>
              <th>Stock</th>
              <th>Status</th>
              <th>Expiry</th>
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
              if ($row['expiry_date'] && $row['expiry_date'] < date('Y-m-d')) {
                $status = 'Expired';
                $statusClass = 'text-danger';
                $icon = 'fa-calendar-xmark';
              }
            ?>
            <tr>
              <td><?php echo htmlspecialchars($row['item_name']); ?></td>
              <td><?php echo $row['stock_level']; ?></td>
              <td><i class="fa-solid <?php echo $icon; ?> <?php echo $statusClass; ?>"></i> <span class="<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
              <td><?php echo $row['expiry_date'] ? htmlspecialchars($row['expiry_date']) : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card shadow" style="flex:1; min-width:280px; max-width:400px;">
      <h2 class="mb-2" style="font-size:1.2rem; font-weight:600;"><i class="fa-solid fa-clock-rotate-left text-info"></i> Recent Stock Movements</h2>
      <ul style="list-style:none; padding:0; max-height:340px; overflow-y:auto;">
        <?php foreach ($logs as $log): ?>
        <li class="flex flex-between mb-2" style="align-items:center; border-bottom:1px solid var(--border); padding-bottom:6px;">
          <span><i class="fa-solid fa-<?php echo $log['type'] === 'in' ? 'arrow-down text-success' : 'arrow-up text-danger'; ?>"></i> <b><?php echo htmlspecialchars($log['item_name']); ?></b> <span class="text-muted" style="font-size:0.95rem;">(<?php echo ucfirst($log['type']); ?>)</span></span>
          <span class="badge rounded <?php echo $log['type'] === 'in' ? 'bg-success' : 'bg-danger'; ?>" style="padding:6px 14px; font-size:0.98rem;"> <?php echo $log['quantity']; ?> </span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <!-- Modal for Quick In/Out -->
  <div id="stockModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.18); z-index:999; align-items:center; justify-content:center;">
    <div class="card shadow" style="max-width:400px; width:100%; position:relative;">
      <button id="closeStockModal" style="position:absolute; top:12px; right:12px; background:none; border:none; font-size:1.3rem; color:var(--danger); cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
      <h2 id="stockModalTitle" style="font-size:1.2rem; font-weight:600; margin-bottom:16px;">Quick Stock In/Out</h2>
      <form id="stockForm" method="post" action="stock.php">
        <label for="modal_item_id">Item <i class="fa-solid fa-circle-question text-info" title="Select the item."></i></label>
        <select id="modal_item_id" name="item_id" required>
          <option value="">Select Item</option>
          <?php foreach ($items as $item): ?>
            <option value="<?php echo $item['item_id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?></option>
          <?php endforeach; ?>
        </select>
        <label for="modal_type">Type <i class="fa-solid fa-circle-question text-info" title="Stock In or Out."></i></label>
        <select id="modal_type" name="type" required>
          <option value="in">Stock In</option>
          <option value="out">Stock Out</option>
        </select>
        <label for="modal_quantity">Quantity <i class="fa-solid fa-circle-question text-info" title="Enter the quantity."></i></label>
        <input type="number" id="modal_quantity" name="quantity" min="1" required>
        <div class="flex flex-between mt-2">
          <button type="submit" class="btn btn-accent" id="saveStockBtn" name="stock_entry"><i class="fa-solid fa-save"></i> Save</button>
          <button type="button" class="btn btn-danger" id="cancelStockBtn"><i class="fa-solid fa-xmark"></i> Cancel</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script src="assets/js/stock.js"></script> 