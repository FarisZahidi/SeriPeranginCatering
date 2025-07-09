<?php
// $required_role = 'Owner'; // Allow both Staff and Owner
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

// Fetch stats using batch expiry from stock_logs
$total_items = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory"))['cnt'];
$low_stock = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM (SELECT i.item_id, IFNULL(SUM(CASE WHEN s.type = 'in' AND (s.batch_expiry_date IS NULL OR s.batch_expiry_date >= CURDATE()) THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END), 0) AS stock_level FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id GROUP BY i.item_id HAVING stock_level < 10) as low_items");
$low_stock_count = mysqli_fetch_assoc($low_stock)['cnt'];
// Expired items: count batches with batch_expiry_date < CURDATE() and qty > 0
$expired_items = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM (SELECT i.item_id, s.batch_expiry_date, SUM(CASE WHEN s.type = 'in' THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END) AS qty FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id WHERE s.batch_expiry_date IS NOT NULL GROUP BY i.item_id, s.batch_expiry_date HAVING qty > 0 AND s.batch_expiry_date < CURDATE()) as expired_batches");
$expired_count = mysqli_fetch_assoc($expired_items)['cnt'];
$recent_updates = mysqli_query($conn, "SELECT item_name, created_at FROM inventory ORDER BY created_at DESC LIMIT 3");

// For chart: get counts for normal, low, expired
$normal_stock = $total_items - $low_stock_count - $expired_count;

// For alerts: items expiring soon (within 1 day) using batch expiry
$expiring_soon = mysqli_query($conn, "SELECT i.item_name, s.batch_expiry_date as expiry_date FROM inventory i JOIN stock_logs s ON i.item_id = s.item_id WHERE s.batch_expiry_date IS NOT NULL AND s.batch_expiry_date >= CURDATE() AND s.batch_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) GROUP BY i.item_id, s.batch_expiry_date ORDER BY s.batch_expiry_date ASC LIMIT 5");
$expiring_soon_list = [];
if ($expiring_soon && mysqli_num_rows($expiring_soon) > 0) {
    while ($row = mysqli_fetch_assoc($expiring_soon)) {
        $expiring_soon_list[] = $row;
    }
}
$show_expiry_alert = !empty($expiring_soon_list) && empty($_SESSION['expiring_soon_alert_shown']);
$low_stock_items = mysqli_query($conn, "SELECT i.item_name, IFNULL(SUM(CASE WHEN s.type = 'in' AND (s.batch_expiry_date IS NULL OR s.batch_expiry_date >= CURDATE()) THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END), 0) AS stock_level FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id GROUP BY i.item_id HAVING stock_level < 10 ORDER BY stock_level ASC LIMIT 5");

// Fetch stock by category
$category_data = [];
$cat_result = mysqli_query($conn, "SELECT category, SUM(CASE WHEN s.type = 'in' THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END) as total FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id GROUP BY category");
if ($cat_result) {
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $category_data[$row['category']] = (int) $row['total'];
    }
}
// Fetch weekly stock in/out trend (last 8 weeks)
$trend_labels = [];
$stock_in = [];
$stock_out = [];
for ($i = 7; $i >= 0; $i--) {
    $start = date('Y-m-d', strtotime("-{$i} week Monday"));
    $end = date('Y-m-d', strtotime("-{$i} week Sunday"));
    $trend_labels[] = date('M d', strtotime($start));
    $in = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(quantity),0) as qty FROM stock_logs WHERE type='in' AND log_date BETWEEN '$start' AND '$end'"))['qty'];
    $out = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(quantity),0) as qty FROM stock_logs WHERE type='out' AND log_date BETWEEN '$start' AND '$end'"))['qty'];
    $stock_in[] = (int) $in;
    $stock_out[] = (int) $out;
}

// Example: Upcoming events (static for now)
$upcoming = [
    ['date' => date('Y-m-d', strtotime('+2 days')), 'event' => 'Stock Review'],
    ['date' => date('Y-m-d', strtotime('+5 days')), 'event' => 'Supplier Delivery'],
    ['date' => date('Y-m-d', strtotime('+7 days')), 'event' => 'Inventory Audit'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Seri Perangin Catering</title>
    <link rel="stylesheet" href="assets/css/homepage.css">
</head>

<body>
    <main style="margin-left:220px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
        <h1 style="font-size:2.2rem; font-weight:700; margin-bottom:8px;">Welcome,
            <?php echo htmlspecialchars($_SESSION['username']); ?>!
        </h1>
        <div class="text-muted mb-3" style="font-size:1.1rem;">This is your dashboard. Use the sidebar to manage
            inventory, staff, stock, and view reports.</div>
        <!-- Alerts Card (now on top) -->
        <div
            style="background:#fffbe6; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,0.04); padding:28px 24px; width:100%; margin-bottom:36px; display:flex; flex-direction:column; justify-content:center; border-left:6px solid #ffc107;">
            <div
                style="font-weight:700; font-size:1.15rem; margin-bottom:18px; color:#bfa100; display:flex; align-items:center;">
                <i class="fa-solid fa-bell-exclamation" style="margin-right:8px; color:#ffc107;"></i>
                Expiry & Low Stock Alerts
            </div>
            <ul style="list-style:none; padding:0; margin:0;">
                <!-- Expiring Soon -->
                <?php if (mysqli_num_rows($expiring_soon) > 0): ?>
                    <li style="margin-bottom:16px;">
                        <div style="color: #dc3545; font-weight: 600; margin-bottom:8px; display:flex; align-items:center;">
                            <i class="fa-solid fa-calendar-week" style="margin-right:6px; color:#dc3545;"></i>
                            <span
                                style="background:#ffeaea; color:#dc3545; border-radius:12px; padding:2px 12px; font-size:0.98em; font-weight:600; margin-right:8px;">Expiring
                                Soon</span>
                        </div>
                        <?php while ($row = mysqli_fetch_assoc($expiring_soon)): ?>
                            <div style="margin-left:24px; margin-bottom:6px; display:flex; align-items:center;">
                                <i class="fa-solid fa-exclamation-circle" style="color:#dc3545; margin-right:7px;"></i>
                                <span style="font-weight:600;"><?php echo htmlspecialchars($row['item_name']); ?></span>
                                <span
                                    style="background:#fff0f0; color:#dc3545; border-radius:10px; padding:2px 10px; font-size:0.93em; margin-left:10px;">
                                    Batch: <?php echo htmlspecialchars($row['expiry_date']); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </li>
                <?php endif; ?>
                <!-- Low Stock -->
                <?php if (mysqli_num_rows($low_stock_items) > 0): ?>
                    <li style="margin-bottom:16px;">
                        <div style="color: #ffc107; font-weight: 600; margin-bottom:8px; display:flex; align-items:center;">
                            <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px; color:#ffc107;"></i>
                            <span
                                style="background:#fffbe6; color:#bfa100; border-radius:12px; padding:2px 12px; font-size:0.98em; font-weight:600; margin-right:8px;">Low
                                Stock</span>
                        </div>
                        <?php while ($row = mysqli_fetch_assoc($low_stock_items)): ?>
                            <div style="margin-left:24px; margin-bottom:6px; display:flex; align-items:center;">
                                <i class="fa-solid fa-box-open" style="color:#ffc107; margin-right:7px;"></i>
                                <span style="font-weight:600;"><?php echo htmlspecialchars($row['item_name']); ?></span>
                                <span
                                    style="background:#fff8e1; color:#bfa100; border-radius:10px; padding:2px 10px; font-size:0.93em; margin-left:10px;">
                                    Qty: <span style="color:#dc3545;"><?php echo $row['stock_level']; ?></span>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </li>
                <?php endif; ?>
                <?php if (mysqli_num_rows($expiring_soon) == 0 && mysqli_num_rows($low_stock_items) == 0): ?>
                    <li class="text-muted" style="margin-top:18px;">No alerts at this time.</li>
                <?php endif; ?>
            </ul>
        </div>
        <!-- Inventory Summary Section -->
        <div style="font-size:1.25rem; font-weight:700; margin-bottom:12px; color:#333;">Inventory Summary</div>
        <div style="display:flex; flex-wrap:wrap; gap:32px; margin-bottom:32px; width:100%;">
            <div
                style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:32px 0; flex:1; min-width:180px; display:flex; flex-direction:column; align-items:center; justify-content:center; border-left:6px solid #007bff; height:140px;">
                <div style="color:#888; font-size:1.05rem; margin-bottom:6px; font-weight:500;"><i
                        class="fa-solid fa-warehouse text-success"></i> Total Items</div>
                <div style="font-size:2.6rem; font-weight:700; color:#007bff;"> <?php echo $total_items; ?> </div>
            </div>
            <div
                style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:32px 0; flex:1; min-width:180px; display:flex; flex-direction:column; align-items:center; justify-content:center; border-left:6px solid #ffc107; height:140px;">
                <div style="color:#888; font-size:1.05rem; margin-bottom:6px; font-weight:500;"><i
                        class="fa-solid fa-triangle-exclamation text-warning"></i> Low Stock</div>
                <div style="font-size:2.6rem; font-weight:700; color:#ffc107;"> <?php echo $low_stock_count; ?> </div>
            </div>
            <div
                style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:32px 0; flex:1; min-width:180px; display:flex; flex-direction:column; align-items:center; justify-content:center; border-left:6px solid #dc3545; height:140px;">
                <div style="color:#888; font-size:1.05rem; margin-bottom:6px; font-weight:500;"><i
                        class="fa-solid fa-calendar-xmark text-danger"></i> Expired Items</div>
                <div style="font-size:2.6rem; font-weight:700; color:#dc3545;"> <?php echo $expired_count; ?> </div>
            </div>
            <div
                style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:32px 0; flex:1; min-width:180px; display:flex; flex-direction:column; align-items:center; justify-content:center; border-left:6px solid #6c757d; height:140px;">
                <div style="color:#888; font-size:1.05rem; margin-bottom:6px; font-weight:500;"><i
                        class="fa-solid fa-clock-rotate-left text-info"></i> Recent Updates</div>
                <div style="font-size:2.6rem; font-weight:700; color:#6c757d;">
                    <?php echo mysqli_num_rows($recent_updates); ?>
                </div>
            </div>
        </div>
        <!-- Analytics & Alerts Row -->
        <div style="font-size:1.25rem; font-weight:700; margin-bottom:12px; color:#333;">Analytics & Alerts</div>
        <div style="display:flex; flex-wrap:wrap; gap:24px; margin-bottom:32px; align-items:stretch;">
            <div
                style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:24px; flex:1; min-width:320px; max-width:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; height:340px;">
                <div style="font-weight:600; margin-bottom:12px; text-align:center;">Inventory Status Overview</div>
                <canvas id="stockChart" width="600" height="220"
                    style="display:block; margin:auto; width:100%; max-width:600px; height:auto;"></canvas>
            </div>
            <div
                style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:24px; flex:1; min-width:320px; max-width:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; height:340px;">
                <div style="font-weight:600; margin-bottom:12px; text-align:center;">Stock by Category</div>
                <canvas id="categoryChart" width="600" height="220"
                    style="display:block; margin:auto; width:100%; max-width:600px; height:auto;"></canvas>
            </div>
            <div
                style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:24px; flex:1; min-width:320px; max-width:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; height:340px;">
                <div style="font-weight:600; margin-bottom:12px; text-align:center;">Stock In/Out Trend (Last 8 Weeks)
                </div>
                <canvas id="trendChart" width="600" height="220"
                    style="display:block; margin:auto; width:100%; max-width:600px; height:auto;"></canvas>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/homepage.js"></script>
    <script>
        // Expiry Date Alert Popup (show only once per login/logout)
        document.addEventListener('DOMContentLoaded', function () {
            const expiringSoon = <?php echo json_encode($expiring_soon_list); ?>;
            const showExpiryAlert = <?php echo $show_expiry_alert ? 'true' : 'false'; ?>;
            if (expiringSoon.length > 0 && showExpiryAlert) {
                let html = '<ul style="text-align:left; margin:0; padding:0 0 0 18px;">';
                expiringSoon.forEach(item => {
                    html += `<li style=\"margin-bottom:6px;\"><b>${item.item_name}</b> <span style=\"color:#dc3545; font-weight:600;\">(${item.expiry_date})</span></li>`;
                });
                html += '</ul>';
                Swal.fire({
                    title: 'Expiring Very Soon!',
                    html: `<div style=\"font-size:1.08em; margin-bottom:8px;\">The following items will expire within 1 day:</div>${html}`,
                    icon: 'warning',
                    iconColor: '#dc3545',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK',
                    showCloseButton: true,
                    customClass: { popup: 'swal2-inv-expiry' }
                }).then(() => {
                    // Set session variable via AJAX
                    fetch('set_expiry_alert_session.php', { method: 'POST', credentials: 'same-origin' });
                });
                // Optional: Play a sound
                const audio = new Audio('https://cdn.pixabay.com/audio/2022/07/26/audio_124bfae5b2.mp3');
                audio.play().catch(() => { });
            }
        });
        // Chart.js for Inventory Status
        const ctx = document.getElementById('stockChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Normal', 'Low Stock', 'Expired'],
                datasets: [{
                    data: [<?php echo $normal_stock; ?>, <?php echo $low_stock_count; ?>, <?php echo $expired_count; ?>],
                    backgroundColor: ['#4caf50', '#ffc107', '#f44336'],
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        // Stock by Category Chart
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(catCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($category_data)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($category_data)); ?>,
                    backgroundColor: ['#42a5f5', '#66bb6a', '#ffa726', '#ab47bc', '#ec407a', '#ff7043', '#26a69a', '#d4e157'],
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        // Stock In/Out Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [
                    {
                        label: 'Stock In',
                        data: <?php echo json_encode($stock_in); ?>,
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76,175,80,0.08)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#4caf50',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Stock Out',
                        data: <?php echo json_encode($stock_out); ?>,
                        borderColor: '#f44336',
                        backgroundColor: 'rgba(244,67,54,0.08)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#f44336',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                },
                responsive: true
            }
        });
    </script>
</body>

</html>