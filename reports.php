<?php
$required_role = 'Owner';
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

// Fetch inventory for pie chart and summary using batch expiry
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory"))['cnt'];
$expired = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM (SELECT i.item_id, s.batch_expiry_date, SUM(CASE WHEN s.type = 'in' THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END) AS qty FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id WHERE s.batch_expiry_date IS NOT NULL GROUP BY i.item_id, s.batch_expiry_date HAVING qty > 0 AND s.batch_expiry_date < CURDATE()) as expired_batches"))['cnt'];
$in_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM (SELECT i.item_id, SUM(CASE WHEN s.type = 'in' AND (s.batch_expiry_date IS NULL OR s.batch_expiry_date >= CURDATE()) THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END) AS stock_level FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id GROUP BY i.item_id HAVING stock_level > 0) as in_stock_items"))['cnt'];
$used = $total - $in_stock - $expired;

// Fetch stock usage for trend line (last 8 weeks)
$trend = [];
for ($i = 7; $i >= 0; $i--) {
  $start = date('Y-m-d', strtotime("-{$i} week Monday"));
  $end = date('Y-m-d', strtotime("-{$i} week Sunday"));
  $usedQty = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(quantity),0) as qty FROM stock_logs WHERE type='out' AND log_date BETWEEN '$start' AND '$end'"))['qty'];
  $trend[] = [
    'week' => date('M d', strtotime($start)),
    'qty' => (int) $usedQty
  ];
}

// Fetch inventory for table with total quantity and earliest batch expiry
$inventory = [];
$result = mysqli_query($conn, "SELECT i.*, IFNULL(SUM(CASE WHEN s.type = 'in' AND (s.batch_expiry_date IS NULL OR s.batch_expiry_date >= CURDATE()) THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END), 0) AS total_quantity, MIN(CASE WHEN s.type = 'in' AND s.batch_expiry_date >= CURDATE() THEN s.batch_expiry_date END) AS earliest_expiry FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id GROUP BY i.item_id ORDER BY earliest_expiry ASC, item_name ASC");
if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $inventory[] = $row;
  }
}

// Fetch all batches for each item (grouped by expiry date)
$batch_details = [];
$batch_sql = "SELECT i.item_id, s.batch_expiry_date, SUM(CASE WHEN s.type = 'in' THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END) AS batch_quantity, MIN(s.log_date) as stock_in_date FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id WHERE s.batch_expiry_date IS NOT NULL GROUP BY i.item_id, s.batch_expiry_date HAVING batch_quantity > 0 ORDER BY i.item_id, s.batch_expiry_date ASC";
$res_batch = mysqli_query($conn, $batch_sql);
if ($res_batch) {
  while ($row = mysqli_fetch_assoc($res_batch)) {
    $batch_details[$row['item_id']][] = $row;
  }
}

// Fetch summary stats for print
$low_stock = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM (SELECT i.item_id, IFNULL(SUM(CASE WHEN s.type = 'in' AND (s.batch_expiry_date IS NULL OR s.batch_expiry_date >= CURDATE()) THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END), 0) AS stock_level FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id GROUP BY i.item_id HAVING stock_level < 10) as low_items");
$low_stock_count = mysqli_fetch_assoc($low_stock)['cnt'];
$expired_items = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM (SELECT i.item_id, s.batch_expiry_date, SUM(CASE WHEN s.type = 'in' THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END) AS qty FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id WHERE s.batch_expiry_date IS NOT NULL GROUP BY i.item_id, s.batch_expiry_date HAVING qty > 0 AND s.batch_expiry_date < CURDATE()) as expired_batches");
$expired_count = mysqli_fetch_assoc($expired_items)['cnt'];

// Fetch recent stock logs for print (last 20 or filtered by date range)
$logs = [];
$where = [];
if (!empty($_GET['start_date'])) {
  $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
  $where[] = "s.log_date >= '" . $start_date . " 00:00:00'";
}
if (!empty($_GET['end_date'])) {
  $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);
  $where[] = "s.log_date <= '" . $end_date . " 23:59:59'";
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$sql_logs = "SELECT s.*, u.username, i.item_name FROM stock_logs s LEFT JOIN users u ON s.user_id = u.user_id LEFT JOIN inventory i ON s.item_id = i.item_id $where_sql ORDER BY s.log_date DESC LIMIT 100";
$result_logs = mysqli_query($conn, $sql_logs);
if ($result_logs) {
  while ($row = mysqli_fetch_assoc($result_logs)) {
    $logs[] = $row;
  }
}
?>
<main style="margin-left:230px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
  <h1 style="font-size:2rem; font-weight:700; margin-bottom:24px;">Reports</h1>
  <!-- <div class="flex flex-between mb-3" style="gap:24px; flex-wrap:wrap;">
    <div class="card shadow"
      style="flex:1; min-width:320px; max-width:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; padding-bottom:32px;">
      <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:12px;"><i
          class="fa-solid fa-chart-pie text-primary"></i> Inventory Status</h2>
      <div style="width:100%; max-width:600px; height:auto; margin-bottom:18px;"><canvas id="pieChart" width="600"
          height="340" style="display:block; margin:auto; width:100%; height:auto;"></canvas></div>
    </div>
    <div class="card shadow"
      style="flex:1; min-width:320px; max-width:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; padding-bottom:32px;">
      <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:12px;"><i
          class="fa-solid fa-chart-line text-info"></i> Weekly Stock Usage</h2>
      <div style="width:100%; max-width:600px; height:auto; margin-bottom:18px;"><canvas id="trendChart" width="600"
          height="340" style="display:block; margin:auto; width:100%; height:auto;"></canvas></div>
    </div>
  </div> -->
  <div class="card shadow mt-3">
    <div class="flex flex-between mb-2" style="align-items:center; flex-wrap:wrap; gap:12px;">
      <h2 style="font-size:1.1rem; font-weight:600;">Inventory List</h2>
      <div>
        <button class="btn btn-info" id="printInventory"><i class="fa-solid fa-print"></i> Print Inventory List</button>
      </div>
    </div>
    <div style="overflow-x:auto;" id="inventoryContainer">
      <?php foreach (
        $inventory as $item): ?>
        <div class="item-summary-block"
          style="margin-bottom:18px; padding:14px 18px 10px 18px; background:#f8f9fa; border-radius:7px; box-shadow:0 1px 3px rgba(0,0,0,0.03); border:1px solid #e0e0e0;">
          <div style="font-size:1.08em; font-weight:700; color:#333; margin-bottom:4px;">
            <?php echo htmlspecialchars($item['item_name']); ?>
          </div>
          <div style="display:flex; flex-wrap:wrap; gap:18px; font-size:0.98em; color:#444; margin-bottom:2px;">
            <div><b>Category:</b> <?php echo htmlspecialchars($item['category']); ?></div>
            <div><b>Unit:</b> <?php echo htmlspecialchars($item['unit']); ?></div>
            <div><b>Total Quantity:</b> <?php echo $item['total_quantity']; ?></div>
            <div><b>Added:</b> <?php echo date('d M Y', strtotime($item['created_at'])); ?></div>
            <div><b>Expiry Date:</b>
              <?php
              if ($item['earliest_expiry']) {
                $expiry_date = new DateTime($item['earliest_expiry']);
                $today = new DateTime();
                $diff = $today->diff($expiry_date);
                $days_until = $diff->invert ? -$diff->days : $diff->days;
                if ($days_until < 0) {
                  echo htmlspecialchars($item['earliest_expiry']) . ' <span style="color: #dc3545; font-weight: 600;">(Expired ' . abs($days_until) . ' days ago)</span>';
                } elseif ($days_until == 0) {
                  echo htmlspecialchars($item['earliest_expiry']) . ' <span style="color: #ffc107; font-weight: 600;">(Expires today)</span>';
                } elseif ($days_until <= 7) {
                  echo htmlspecialchars($item['earliest_expiry']) . ' <span style="color: #fd7e14; font-weight: 600;">(' . $days_until . ' days left)</span>';
                } else {
                  echo htmlspecialchars($item['earliest_expiry']) . ' <span style="color: #28a745; font-weight: 600;">(' . $days_until . ' days left)</span>';
                }
              } else {
                echo '-';
              }
              ?>
            </div>
          </div>
          <?php if (!empty($batch_details[$item['item_id']])): ?>
            <div style="margin-top:8px;">
              <!-- Batch table (already summary-first, urgent-highlighted) -->
              <div style="padding:8px 0 4px 0; font-weight:600; color:#333; font-size:0.98em;">Batch Details</div>
              <?php
              // Group batches by status
              $expired_batches = [];
              $soon_batches = [];
              $safe_batches = [];
              $safe_total_qty = 0;
              $today = new DateTime();
              foreach ($batch_details[$item['item_id']] as $batch) {
                $expiry = $batch['batch_expiry_date'];
                $qty = $batch['batch_quantity'];
                $stockIn = $batch['stock_in_date'] ? date('d M Y', strtotime($batch['stock_in_date'])) : '-';
                $expiryDate = new DateTime($expiry);
                $daysLeft = $today->diff($expiryDate)->invert ? -$today->diff($expiryDate)->days : $today->diff($expiryDate)->days;
                if ($daysLeft < 0) {
                  $expired_batches[] = [
                    'qty' => $qty,
                    'expiry' => $expiry,
                    'daysLeft' => $daysLeft,
                    'stockIn' => $stockIn
                  ];
                } elseif ($daysLeft <= 7) {
                  $soon_batches[] = [
                    'qty' => $qty,
                    'expiry' => $expiry,
                    'daysLeft' => $daysLeft,
                    'stockIn' => $stockIn
                  ];
                } else {
                  $safe_batches[] = [
                    'qty' => $qty,
                    'expiry' => $expiry,
                    'daysLeft' => $daysLeft,
                    'stockIn' => $stockIn
                  ];
                  $safe_total_qty += $qty;
                }
              }
              // Summary row
              $summary = [];
              if (count($expired_batches))
                $summary[] = count($expired_batches) . ' expired';
              if (count($soon_batches))
                $summary[] = count($soon_batches) . ' expiring soon';
              if (count($safe_batches))
                $summary[] = count($safe_batches) . ' safe';
              echo '<div style="margin-bottom:6px; font-size:0.97em; color:#555;">Summary: ' . implode(', ', $summary) . "." . '</div>';
              ?>
              <table class="table" style="margin:0; font-size:0.96em; border-collapse:collapse;">
                <thead>
                  <tr style="background:#f1f1f1;">
                    <th style="width:20%; padding:6px 8px; border:1px solid #e0e0e0; font-weight:600;">Batch Quantity</th>
                    <th style="width:30%; padding:6px 8px; border:1px solid #e0e0e0; font-weight:600;">Expiry Date</th>
                    <th style="width:25%; padding:6px 8px; border:1px solid #e0e0e0; font-weight:600;">Days Left</th>
                    <th style="width:25%; padding:6px 8px; border:1px solid #e0e0e0; font-weight:600;">Stock-In Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($expired_batches as $batch): ?>
                    <tr style="background:#ffe5e5;">
                      <td style="padding:6px 8px; border:1px solid #e0e0e0;"><?php echo $batch['qty']; ?></td>
                      <td style="padding:6px 8px; border:1px solid #e0e0e0;"><?php echo htmlspecialchars($batch['expiry']); ?>
                      </td>
                      <td class="text-danger"
                        style="padding:6px 8px; border:1px solid #e0e0e0; font-weight:500; letter-spacing:0.5px;">Expired
                        <?php echo abs($batch['daysLeft']); ?> days ago
                      </td>
                      <td style="padding:6px 8px; border:1px solid #e0e0e0;"><?php echo $batch['stockIn']; ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php foreach ($soon_batches as $batch): ?>
                    <tr style="background:#fff7e6;">
                      <td style="padding:6px 8px; border:1px solid #e0e0e0;"><?php echo $batch['qty']; ?></td>
                      <td style="padding:6px 8px; border:1px solid #e0e0e0;"><?php echo htmlspecialchars($batch['expiry']); ?>
                      </td>
                      <td class="text-orange"
                        style="padding:6px 8px; border:1px solid #e0e0e0; font-weight:500; letter-spacing:0.5px; ">
                        <?php echo $batch['daysLeft'] === 0 ? 'Expires today' : $batch['daysLeft'] . ' days left'; ?>
                      </td>
                      <td style="padding:6px 8px; border:1px solid #e0e0e0;"><?php echo $batch['stockIn']; ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (count($safe_batches)): ?>
                    <tr style="background:#f7f7f7;">
                      <td colspan="4" style="padding:6px 8px; border:1px solid #e0e0e0; color:#28a745; font-weight:500;">
                        <?php echo count($safe_batches); ?> safe batch<?php echo count($safe_batches) > 1 ? 'es' : ''; ?>,
                        total quantity: <?php echo $safe_total_qty; ?>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <!-- Stock Movements Table -->
  <div class="card shadow mt-3">
    <div class="flex flex-between mb-2" style="align-items:center; flex-wrap:wrap; gap:12px;">
      <h2 style="font-size:1.1rem; font-weight:600;">Recent Stock Movements</h2>
      <div>
        <button class="btn btn-warning" id="printStockLog"><i class="fa-solid fa-print"></i> Print Stock
          Movements</button>
      </div>
    </div>
    <!-- Date Range Filter -->
    <form method="get" style="margin-bottom:16px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
      <label for="start_date" style="font-weight:500;">From</label>
      <input type="date" id="start_date" name="start_date"
        value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
      <label for="end_date" style="font-weight:500;">To</label>
      <input type="date" id="end_date" name="end_date"
        value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
      <?php if (isset($_GET['start_date']) || isset($_GET['end_date'])): ?>
        <a href="reports.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>
    <div style="overflow-x:auto;">
      <table class="table" id="stockLogTable">
        <thead>
          <tr>
            <th>Date</th>
            <th>Item</th>
            <th>Type</th>
            <th>Qty</th>
            <th>User</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><?php echo date('d M Y H:i', strtotime($log['log_date'])); ?></td>
              <td><?php echo htmlspecialchars($log['item_name']); ?></td>
              <td><?php echo $log['type'] === 'in' ? 'Stock In' : 'Stock Out'; ?></td>
              <td><?php echo $log['quantity']; ?></td>
              <td><?php echo htmlspecialchars($log['username']); ?></td>
              </tr>
            <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/reports.js"></script>
<script>
  // Pass summary and logs data to JS for print
  window.printData = {
    summary: {
      total_items: <?php echo json_encode($total); ?>,
      low_stock: <?php echo json_encode($low_stock_count); ?>,
      expired: <?php echo json_encode($expired_count); ?>
    },
    logs: <?php echo json_encode($logs); ?>
  };
  // Pie Chart
  const pieCtx = document.getElementById('pieChart').getContext('2d');
  new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: ['Expired', 'Used', 'In Stock'],
      datasets: [{
        data: [<?php echo $expired; ?>, <?php echo $used; ?>, <?php echo $in_stock; ?>],
        backgroundColor: ['#dc3545', '#17a2b8', '#28a745'],
        borderWidth: 1
      }]
    },
    options: {
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            generateLabels: function (chart) {
              const data = chart.data;
              if (data.labels.length && data.datasets.length) {
                return data.labels.map((label, i) => {
                  const value = data.datasets[0].data[i];
                  return {
                    text: `${label} (${value})`,
                    fillStyle: data.datasets[0].backgroundColor[i],
                    strokeStyle: data.datasets[0].backgroundColor[i],
                    index: i
                  };
                });
              }
              return [];
            }
          }
        }
      },
      responsive: false,
      maintainAspectRatio: false
    }
  });
  // Trend Line
  const trendCtx = document.getElementById('trendChart').getContext('2d');
  new Chart(trendCtx, {
    type: 'line',
    data: {
      labels: [<?php foreach ($trend as $t)
        echo "'{$t['week']}',"; ?>],
      datasets: [{
        label: 'Stock Used',
        data: [<?php foreach ($trend as $t)
          echo $t['qty'] . ','; ?>],
        borderColor: '#007bff',
        backgroundColor: 'rgba(0,123,255,0.08)',
        tension: 0.3,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#007bff',
        pointBorderColor: '#fff',
        pointHoverRadius: 6
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      responsive: true
    }
  });
</script>