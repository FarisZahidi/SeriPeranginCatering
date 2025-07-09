<?php
$current = basename($_SERVER['PHP_SELF']);
function nav_active($page)
{
  global $current;
  return $current === $page ? 'active' : '';
}
?>
<!-- Sidebar and Topbar Navigation -->
<link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/global.css">
<style>
  .sidebar {
    width: 220px;
    background: #fff;
    border-right: 1.5px solid var(--border);
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    display: flex;
    flex-direction: column;
    transition: width 0.2s;
  }

  .sidebar .logo {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--primary);
    padding: 28px 0 18px 0;
    text-align: center;
    letter-spacing: 1px;
  }

  .sidebar ul {
    list-style: none;
    padding: 0 0 0 0;
    margin: 0;
    flex: 1;
  }

  .sidebar li {
    margin-bottom: 6px;
  }

  .sidebar a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 28px;
    color: var(--text);
    font-size: 1.08rem;
    border-left: 4px solid transparent;
    transition: background 0.15s, border 0.15s, color 0.15s;
  }

  .sidebar a.active,
  .sidebar a:hover {
    background: var(--bg);
    color: var(--primary);
    border-left: 4px solid var(--primary);
    font-weight: 600;
  }

  .sidebar .logout {
    margin-top: auto;
    margin-bottom: 18px;
  }

  .topbar {
    margin-left: 220px;
    height: 60px;
    background: #fff;
    border-bottom: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 32px;
    position: sticky;
    top: 0;
    z-index: 90;
  }

  .topbar .user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1rem;
    color: var(--muted);
  }

  .topbar .user-info .fa-user-circle {
    font-size: 1.3rem;
    color: var(--primary);
  }

  .topbar .logout-btn {
    margin-left: 18px;
    background: var(--danger);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    padding: 8px 18px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
  }

  .topbar .logout-btn:hover {
    background: #b52a37;
  }

  @media (max-width: 900px) {
    .sidebar {
      width: 60px;
      min-width: 60px;
      align-items: center;
    }

    .sidebar .logo,
    .sidebar span {
      display: none;
    }

    .sidebar a {
      justify-content: center;
      padding: 13px 0;
      font-size: 1.2rem;
    }

    .topbar {
      margin-left: 60px;
      padding: 0 10px;
    }
  }
</style>
<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-utensils"></i> <span>Seri Perangin</span></div>
  <ul>
    <li><a href="homepage.php" class="<?php echo nav_active('homepage.php'); ?>"><i class="fa-solid fa-gauge"></i>
        <span>Dashboard</span></a></li>
    <li><a href="inventory.php" class="<?php echo nav_active('inventory.php'); ?>"><i class="fa-solid fa-warehouse"></i>
        <span>Inventory</span></a></li>
    <li><a href="stock.php" class="<?php echo nav_active('stock.php'); ?>"><i
          class="fa-solid fa-arrow-right-arrow-left"></i> <span>Stock</span></a></li>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Owner'): ?>
      <li><a href="reports.php" class="<?php echo nav_active('reports.php'); ?>"><i class="fa-solid fa-chart-pie"></i>
          <span>Reports</span></a></li>
      <li><a href="staff.php" class="<?php echo nav_active('staff.php'); ?>"><i class="fa-solid fa-users"></i>
          <span>Staff</span></a></li>
      <li><a href="audit_logs.php" class="<?php echo nav_active('audit_logs.php'); ?>"><i
            class="fa-solid fa-shield-halved"></i> <span>Audit Log</span></a></li>
    <?php endif; ?>
  </ul>
  <div class="logout">
    <a href="logout.php" class="btn btn-danger" style="width:90%;margin:0 auto;display:flex;justify-content:center;"><i
        class="fa-solid fa-sign-out-alt"></i> <span>Logout</span></a>
  </div>
</div>
<div class="topbar">
  <div class="user-info">
    <i class="fa-solid fa-user-circle"></i>
    <span><?php echo htmlspecialchars($_SESSION['username']); ?>
      (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
  </div>
</div>
<div style="margin-left:220px;"></div>