<?php
$required_role = 'Owner';
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

// Fetch stats
$total_items = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory"))['cnt'];
$low_stock = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM (SELECT i.item_id, IFNULL(SUM(CASE WHEN s.type = 'in' THEN s.quantity WHEN s.type = 'out' THEN -s.quantity ELSE 0 END), 0) AS stock_level FROM inventory i LEFT JOIN stock_logs s ON i.item_id = s.item_id GROUP BY i.item_id HAVING stock_level < 10) as low_items");
$low_stock_count = mysqli_fetch_assoc($low_stock)['cnt'];
$expired_items = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE()");
$expired_count = mysqli_fetch_assoc($expired_items)['cnt'];
$recent_updates = mysqli_query($conn, "SELECT item_name, created_at FROM inventory ORDER BY created_at DESC LIMIT 3");

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
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>This is your dashboard. Use the navigation above to manage inventory, staff, stock, and view reports.</p>
        <div class="dashboard-links">
            <a href="inventory.php">Manage Inventory</a>
            <a href="stock.php">Stock In/Out</a>
            <a href="staff.php">Staff Management</a>
            <a href="reports.php">Reports</a>
        </div>
    </div>
    <main style="margin-left:220px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
        <h1 style="font-size:2.2rem; font-weight:700; margin-bottom:8px;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <div class="text-muted mb-3" style="font-size:1.1rem;">This is your dashboard. Use the sidebar to manage inventory, staff, stock, and view reports.</div>
        <div class="flex flex-between mb-3" style="flex-wrap:wrap; gap:24px;">
            <div class="card shadow flex flex-center" style="flex:1; min-width:220px; border-left:6px solid var(--primary);">
                <div>
                    <div class="text-muted mb-2"><i class="fa-solid fa-warehouse text-success"></i> Total Items</div>
                    <div style="font-size:2.2rem; font-weight:700; color:var(--primary);">
                        <?php echo $total_items; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow flex flex-center" style="flex:1; min-width:220px; border-left:6px solid var(--warning);">
                <div>
                    <div class="text-muted mb-2"><i class="fa-solid fa-triangle-exclamation text-warning"></i> Low Stock</div>
                    <div style="font-size:2.2rem; font-weight:700; color:var(--warning);">
                        <?php echo $low_stock_count; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow flex flex-center" style="flex:1; min-width:220px; border-left:6px solid var(--danger);">
                <div>
                    <div class="text-muted mb-2"><i class="fa-solid fa-calendar-xmark text-danger"></i> Expired Items</div>
                    <div style="font-size:2.2rem; font-weight:700; color:var(--danger);">
                        <?php echo $expired_count; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow flex flex-center" style="flex:1; min-width:220px; border-left:6px solid var(--secondary);">
                <div>
                    <div class="text-muted mb-2"><i class="fa-solid fa-clock-rotate-left text-info"></i> Recent Updates</div>
                    <ul style="list-style:none; padding:0; margin:0;">
                        <?php while($row = mysqli_fetch_assoc($recent_updates)): ?>
                            <li style="font-size:1rem; color:var(--secondary); margin-bottom:4px;"><i class="fa-solid fa-circle-dot text-info"></i> <?php echo htmlspecialchars($row['item_name']); ?> <span class="text-muted" style="font-size:0.9rem;">(<?php echo date('d M', strtotime($row['created_at'])); ?>)</span></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/homepage.js"></script>
</body>
</html> 