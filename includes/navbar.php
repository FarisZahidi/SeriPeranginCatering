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
<link rel="stylesheet" href="assets/css/global.css?v=3">
<style>
  :root {
    --sidebar-glass: rgba(67, 160, 71, 0.18);
    /* Glassy green */
    --sidebar-blur: blur(18px);
    --sidebar-shadow: 0 8px 32px 0 rgba(34, 49, 63, 0.18), 0 2px 8px rgba(56, 142, 60, 0.10);
    --sidebar-accent: #43a047;
    --sidebar-width: 230px;
    --sidebar-width-collapsed: 64px;
  }

  .sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-glass);
    backdrop-filter: var(--sidebar-blur);
    box-shadow: var(--sidebar-shadow);
    border-right: 1.5px solid var(--border);
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    display: flex;
    flex-direction: column;
    transition: width 0.2s cubic-bezier(.4, 1.2, .6, 1);
  }

  .sidebar .logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 0 18px 0;
  }

  .sidebar .logo-bg {
    background: linear-gradient(135deg, #43a047 60%, #fd7e14 100%);
    border-radius: 50%;
    width: 62px;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 12px #b2dfdb44;
    margin-bottom: 10px;
  }

  .sidebar .logo i {
    color: #fff;
    font-size: 2.1rem;
    filter: drop-shadow(0 2px 8px #b2dfdb);
  }

  .sidebar .logo span {
    font-size: 1.18rem;
    font-weight: 800;
    color: var(--sidebar-accent);
    letter-spacing: 1.2px;
    margin-top: 2px;
    text-shadow: 0 1px 4px #fff8;
  }

  .sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
    margin-top: 18px;
  }

  .sidebar li {
    margin-bottom: 6px;
  }

  .sidebar a {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 30px;
    color: #23272f;
    font-size: 1.09rem;
    border-left: 4px solid transparent;
    border-radius: 0 18px 18px 0;
    transition: background 0.18s, border 0.18s, color 0.18s, box-shadow 0.18s;
    font-weight: 500;
    position: relative;
  }

  .sidebar a.active,
  .sidebar a:hover {
    background: linear-gradient(90deg, #e8f5e9 0%, #c8e6c9 100%);
    color: var(--sidebar-accent);
    border-left: 4px solid var(--sidebar-accent);
    font-weight: 700;
    box-shadow: 0 2px 8px #43a04711;
  }

  .sidebar a i {
    font-size: 1.25rem;
    min-width: 22px;
    text-align: center;
  }

  .sidebar .logout {
    margin-top: auto;
    margin-bottom: 22px;
    padding: 0 18px;
  }

  .sidebar .logout a {
    width: 100%;
    justify-content: center;
    font-size: 1.08rem;
    border-radius: 10px;
    padding: 12px 0;
    background: linear-gradient(90deg, #fd7e14 0%, #ffb74d 100%);
    color: #fff;
    font-weight: 700;
    box-shadow: 0 2px 8px #fd7e1444;
    border: none;
    transition: background 0.18s, color 0.18s;
  }

  .sidebar .logout a:hover {
    background: linear-gradient(90deg, #e65100 0%, #fd7e14 100%);
    color: #fff;
  }

  .sidebar .logout a i {
    font-size: 1.15rem;
  }

  .sidebar .collapse-btn {
    display: none;
    position: absolute;
    top: 18px;
    right: 18px;
    background: rgba(255, 255, 255, 0.7);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 101;
    box-shadow: 0 1px 4px #23272f22;
    transition: background 0.18s;
  }

  .sidebar .collapse-btn i {
    font-size: 1.2rem;
    color: var(--sidebar-accent);
  }

  .topbar {
    margin-left: var(--sidebar-width);
    height: 62px;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.92) 60%, #e8f5e9 100%);
    border-bottom: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 36px;
    position: sticky;
    top: 0;
    z-index: 90;
    box-shadow: 0 4px 18px #43a04711, 0 2px 8px #23272f0a;
    backdrop-filter: blur(14px);
  }

  .topbar .user-info {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 1.08rem;
    color: #23272f;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.85);
    padding: 7px 22px 7px 10px;
    border-radius: 22px;
    box-shadow: 0 2px 8px #43a04711;
    border: 1.5px solid #e8f5e9;
    position: relative;
    min-width: 180px;
  }

  .topbar .user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #43a047 60%, #fd7e14 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    box-shadow: 0 1px 4px #43a04722;
    border: 2px solid #fff;
    overflow: hidden;
  }

  .topbar .user-avatar i {
    color: #fff;
    font-size: 1.3rem;
    filter: drop-shadow(0 2px 8px #b2dfdb);
  }

  @media (max-width: 900px) {
    .sidebar {
      width: var(--sidebar-width-collapsed);
      min-width: var(--sidebar-width-collapsed);
      align-items: center;
    }

    .sidebar .logo span {
      display: none;
    }

    .sidebar .logo-bg {
      width: 44px;
      height: 44px;
    }

    .sidebar a {
      justify-content: center;
      padding: 14px 0;
      font-size: 1.25rem;
      border-radius: 12px;
    }

    .sidebar a span {
      display: none;
    }

    .sidebar .logout {
      padding: 0 2px;
    }

    .topbar {
      margin-left: var(--sidebar-width-collapsed);
      padding: 0 10px;
    }

    .sidebar .collapse-btn {
      display: flex;
    }
  }
</style>
<div class="sidebar">
  <!-- <button class="collapse-btn" onclick="document.body.classList.toggle('sidebar-collapsed')"><i
      class="fa fa-bars"></i></button> -->
  <div class="logo">
    <div class="logo-bg"><i class="fa-solid fa-utensils"></i></div>
    <span>Seri Perangin</span>
  </div>
  <ul>
    <li><a href="homepage.php" class="<?php echo nav_active('homepage.php'); ?>"><i class="fa-solid fa-gauge"></i>
        <span>Dashboard</span></a></li>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Owner'): ?>
      <li><a href="inventory.php" class="<?php echo nav_active('inventory.php'); ?>"><i class="fa-solid fa-warehouse"></i>
          <span>Inventory</span></a></li>
      <li><a href="stock.php" class="<?php echo nav_active('stock.php'); ?>"><i
            class="fa-solid fa-arrow-right-arrow-left"></i> <span>Stock</span></a></li>
      <li><a href="reports.php" class="<?php echo nav_active('reports.php'); ?>"><i class="fa-solid fa-chart-pie"></i>
          <span>Reports</span></a></li>
      <li><a href="staff.php" class="<?php echo nav_active('staff.php'); ?>"><i class="fa-solid fa-users"></i>
          <span>Staff</span></a></li>
      <li><a href="audit_logs.php" class="<?php echo nav_active('audit_logs.php'); ?>"><i
            class="fa-solid fa-shield-halved"></i> <span>Audit Log</span></a></li>
    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'Staff'): ?>
      <li><a href="stock.php" class="<?php echo nav_active('stock.php'); ?>"><i
            class="fa-solid fa-arrow-right-arrow-left"></i> <span>Stock</span></a></li>
    <?php endif; ?>
  </ul>
  <div class="logout">
    <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-sign-out-alt"></i> <span>Logout</span></a>
  </div>
</div>
<div class="topbar">
  <div class="user-info">
    <div class="user-avatar">
      <i class="fa-solid fa-user"></i>
    </div>
    <span><?php echo isset($_SESSION['name']) && $_SESSION['name'] !== null ? htmlspecialchars($_SESSION['name']) : 'User'; ?>
      <span style="color:#bfa100; font-weight:700;">(<?php echo htmlspecialchars($_SESSION['role']); ?>)</span></span>
  </div>
</div>
<div style="margin-left:var(--sidebar-width);"></div>
<script>
  // Sidebar collapse for mobile
  if (window.innerWidth <= 900) {
    document.body.classList.add('sidebar-collapsed');
  }
  window.addEventListener('resize', function () {
    if (window.innerWidth <= 900) {
      document.body.classList.add('sidebar-collapsed');
    } else {
      document.body.classList.remove('sidebar-collapsed');
    }
  });
</script>