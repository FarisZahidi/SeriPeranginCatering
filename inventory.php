<?php
$required_role = 'Owner';
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

$error = '';
$success = '';
$edit_item = null;

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? null;
    if (!$item_name || !$category || !$unit) {
        $error = 'Item name, category, and unit are required.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO inventory (item_name, category, unit, expiry_date) VALUES (?, ?, ?, ?)");
        if ($expiry_date === '') $expiry_date = null;
        mysqli_stmt_bind_param($stmt, 'ssss', $item_name, $category, $unit, $expiry_date);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Item added successfully.';
        } else {
            $error = 'Failed to add item.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Delete Item
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM inventory WHERE item_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $delete_id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Item deleted successfully.';
    } else {
        $error = 'Failed to delete item.';
    }
    mysqli_stmt_close($stmt);
}

// Handle Edit Item (fetch data)
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = mysqli_prepare($conn, "SELECT * FROM inventory WHERE item_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $edit_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_item = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Handle Update Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $edit_id = intval($_POST['edit_id']);
    $item_name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? null;
    if (!$item_name || !$category || !$unit) {
        $error = 'Item name, category, and unit are required.';
    } else {
        if ($expiry_date === '') $expiry_date = null;
        $stmt = mysqli_prepare($conn, "UPDATE inventory SET item_name = ?, category = ?, unit = ?, expiry_date = ? WHERE item_id = ?");
        mysqli_stmt_bind_param($stmt, 'ssssi', $item_name, $category, $unit, $expiry_date, $edit_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Item updated successfully.';
        } else {
            $error = 'Failed to update item.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch inventory list
$items = [];
$result = mysqli_query($conn, "SELECT * FROM inventory ORDER BY created_at DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
}

// For dropdowns (example categories/units)
$categories = ['Vegetables', 'Meat', 'Dairy', 'Dry Goods', 'Beverages', 'Other'];
$units = ['kg', 'g', 'L', 'ml', 'pcs', 'pack', 'box'];
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
    <h1 style="font-size:2rem; font-weight:700;">Inventory</h1>
    <button class="btn btn-accent" id="addItemBtn"><i class="fa-solid fa-plus"></i> Add Item</button>
  </div>
  <div class="card shadow">
    <div class="flex flex-between mb-2" style="align-items:center; flex-wrap:wrap; gap:12px;">
      <input type="text" id="search" placeholder="Search inventory..." style="max-width:320px;">
      <span class="text-muted" style="font-size:0.98rem;"><i class="fa-solid fa-circle-info"></i> Click column headers to sort</span>
    </div>
    <div style="overflow-x:auto;">
      <table class="table" id="inventoryTable">
        <thead>
          <tr>
            <th data-sort="item_name">Name <i class="fa-solid fa-sort"></i></th>
            <th data-sort="category">Category <i class="fa-solid fa-sort"></i></th>
            <th data-sort="unit">Unit <i class="fa-solid fa-sort"></i></th>
            <th data-sort="expiry_date">Expiry Date <i class="fa-solid fa-sort"></i></th>
            <th data-sort="created_at">Added <i class="fa-solid fa-sort"></i></th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="inventoryBody">
          <?php foreach ($items as $item): ?>
          <tr>
            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
            <td><?php echo htmlspecialchars($item['category']); ?></td>
            <td><?php echo htmlspecialchars($item['unit']); ?></td>
            <td><?php echo $item['expiry_date'] ? htmlspecialchars($item['expiry_date']) : '-'; ?></td>
            <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
            <td>
              <button class="btn btn-warning btn-sm editBtn" data-id="<?php echo $item['item_id']; ?>"><i class="fa-solid fa-pen"></i></button>
              <button class="btn btn-danger btn-sm deleteBtn" data-id="<?php echo $item['item_id']; ?>"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Modal for Add/Edit Item -->
  <div id="itemModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.18); z-index:999; align-items:center; justify-content:center;">
    <div class="card shadow" style="max-width:400px; width:100%; position:relative;">
      <button id="closeModal" style="position:absolute; top:12px; right:12px; background:none; border:none; font-size:1.3rem; color:var(--danger); cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
      <h2 id="modalTitle" style="font-size:1.2rem; font-weight:600; margin-bottom:16px;"></h2>
      <form id="itemForm" method="post" action="inventory.php">
        <input type="hidden" name="item_id" id="modal_item_id">
        <input type="hidden" name="edit_id" id="modal_edit_id">
        <label for="modal_item_name">Name <i class="fa-solid fa-circle-question text-info" title="Enter the item name."></i></label>
        <input type="text" name="item_name" id="modal_item_name" required>
        <label for="modal_category">Category <i class="fa-solid fa-circle-question text-info" title="Select the item category."></i></label>
        <select name="category" id="modal_category" required>
          <option value="">Select Category</option>
          <?php foreach($categories as $cat): ?>
            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
          <?php endforeach; ?>
        </select>
        <label for="modal_unit">Unit <i class="fa-solid fa-circle-question text-info" title="Select the unit of measurement."></i></label>
        <select name="unit" id="modal_unit" required>
          <option value="">Select Unit</option>
          <?php foreach($units as $unit): ?>
            <option value="<?php echo $unit; ?>"><?php echo $unit; ?></option>
          <?php endforeach; ?>
        </select>
        <label for="modal_expiry_date">Expiry Date <i class="fa-solid fa-circle-question text-info" title="Optional: Enter expiry date if perishable."></i></label>
        <input type="date" name="expiry_date" id="modal_expiry_date">
        <div class="flex flex-between mt-2">
          <button type="submit" class="btn btn-accent" id="saveBtn" name="add_item"><i class="fa-solid fa-save"></i> Save</button>
          <button type="button" class="btn btn-danger" id="cancelBtn"><i class="fa-solid fa-xmark"></i> Cancel</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script src="assets/js/inventory.js"></script>
<script>
// Update JS to set button name and hidden edit_id for Add/Edit
const itemForm = document.getElementById('itemForm');
const saveBtn = document.getElementById('saveBtn');
const modalEditId = document.getElementById('modal_edit_id');
Array.from(document.getElementsByClassName('editBtn')).forEach(function(btn) {
  btn.addEventListener('click', function() {
    saveBtn.name = 'update_item';
    modalEditId.value = btn.getAttribute('data-id');
  });
});
addItemBtn.addEventListener('click', function() {
  saveBtn.name = 'add_item';
  modalEditId.value = '';
});
</script> 