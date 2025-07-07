<?php
$required_role = 'Owner';
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

// Fetch inventory for pie chart
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory"))['cnt'];
$expired = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE()"))['cnt'];
$in_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory WHERE (expiry_date IS NULL OR expiry_date >= CURDATE())"))['cnt'];
$used = $total - $in_stock - $expired;

// Fetch stock usage for trend line (last 8 weeks)
$trend = [];
for ($i = 7; $i >= 0; $i--) {
  $start = date('Y-m-d', strtotime("-{$i} week Monday"));
  $end = date('Y-m-d', strtotime("-{$i} week Sunday"));
  $usedQty = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(quantity),0) as qty FROM stock_logs WHERE type='out' AND log_date BETWEEN '$start' AND '$end'"))['qty'];
  $trend[] = [
    'week' => date('M d', strtotime($start)),
    'qty' => (int)$usedQty
  ];
}

// Fetch inventory for table
$inventory = [];
$result = mysqli_query($conn, "SELECT * FROM inventory ORDER BY expiry_date ASC, item_name ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $inventory[] = $row;
    }
}
?>
<main style="margin-left:220px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
  <h1 style="font-size:2rem; font-weight:700; margin-bottom:24px;">Reports</h1>
  <div class="flex flex-between mb-3" style="gap:24px; flex-wrap:wrap;">
    <div class="card shadow" style="flex:1; min-width:320px; display:flex; flex-direction:column; align-items:center; justify-content:center; padding-bottom:32px;">
      <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:12px;"><i class="fa-solid fa-chart-pie text-primary"></i> Inventory Status</h2>
      <div style="width:220px; height:220px; margin-bottom:18px;"><canvas id="pieChart" width="220" height="220"></canvas></div>
    </div>
    <div class="card shadow" style="flex:2; min-width:320px;">
      <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:12px;"><i class="fa-solid fa-chart-line text-info"></i> Weekly Stock Usage</h2>
      <canvas id="trendChart" height="220"></canvas>
    </div>
  </div>
  <div class="card shadow mt-3">
    <div class="flex flex-between mb-2" style="align-items:center; flex-wrap:wrap; gap:12px;">
      <h2 style="font-size:1.1rem; font-weight:600;">Inventory Table</h2>
      <div>
        <button class="btn btn-accent" id="downloadCSV"><i class="fa-solid fa-file-csv"></i> Download CSV</button>
        <button class="btn btn-info" id="printTable"><i class="fa-solid fa-print"></i> Print</button>
      </div>
    </div>
    <div style="overflow-x:auto;">
      <table class="table" id="reportTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Unit</th>
            <th>Expiry Date</th>
            <th>Added</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($inventory as $item): ?>
          <tr<?php if ($item['expiry_date'] && $item['expiry_date'] < date('Y-m-d')) echo ' style=\"background:#ffe5e5;\"'; ?>>
            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
            <td><?php echo htmlspecialchars($item['category']); ?></td>
            <td><?php echo htmlspecialchars($item['unit']); ?></td>
            <td><?php echo $item['expiry_date'] ? htmlspecialchars($item['expiry_date']) : '-'; ?></td>
            <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/reports.js"></script>
<script>
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
          generateLabels: function(chart) {
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
    labels: [<?php foreach($trend as $t) echo "'{$t['week']}',"; ?>],
    datasets: [{
      label: 'Stock Used',
      data: [<?php foreach($trend as $t) echo $t['qty'] . ','; ?>],
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