<?php
// $required_role = 'Owner'; // Allow both Staff and Owner
include 'includes/auth_check.php';
include 'includes/navbar.php';
require_once 'includes/db.php';

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$edit_item = null;

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
  $item_name = trim($_POST['item_name'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $unit = trim($_POST['unit'] ?? '');
  $image_path = null;
  // Handle image upload
  if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $target_dir = 'assets/images/';
    $ext = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('inv_', true) . '.' . $ext;
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_file)) {
      $image_path = $target_file;
    }
  }
  if (!$item_name || !$category || !$unit) {
    $_SESSION['error'] = 'Item name, category, and unit are required.';
    header('Location: inventory.php');
    exit;
  } else {
    $stmt = mysqli_prepare($conn, "INSERT INTO inventory (item_name, category, unit, image_path) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssss', $item_name, $category, $unit, $image_path);
    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['success'] = 'Item added successfully.';
      header('Location: inventory.php');
      exit;
    } else {
      $_SESSION['error'] = 'Failed to add item.';
      header('Location: inventory.php');
      exit;
    }
    mysqli_stmt_close($stmt);
  }
}

// Handle Delete Item
if (isset($_GET['delete'])) {
  $delete_id = intval($_GET['delete']);
  // Fetch item before deleting
  $item_stmt = mysqli_prepare($conn, "SELECT * FROM inventory WHERE item_id = ?");
  mysqli_stmt_bind_param($item_stmt, 'i', $delete_id);
  mysqli_stmt_execute($item_stmt);
  $item_result = mysqli_stmt_get_result($item_stmt);
  $item_data = mysqli_fetch_assoc($item_result);
  mysqli_stmt_close($item_stmt);
  // Fetch image path before deleting
  $img_stmt = mysqli_prepare($conn, "SELECT image_path FROM inventory WHERE item_id = ?");
  mysqli_stmt_bind_param($img_stmt, 'i', $delete_id);
  mysqli_stmt_execute($img_stmt);
  mysqli_stmt_bind_result($img_stmt, $image_path);
  mysqli_stmt_fetch($img_stmt);
  mysqli_stmt_close($img_stmt);
  // Delete image file if exists
  if ($image_path && file_exists($image_path)) {
    unlink($image_path);
  }
  $stmt = mysqli_prepare($conn, "DELETE FROM inventory WHERE item_id = ?");
  mysqli_stmt_bind_param($stmt, 'i', $delete_id);
  if (mysqli_stmt_execute($stmt)) {
    // Audit log
    if ($item_data && isset($_SESSION['user_id'])) {
      $user_id = $_SESSION['user_id'];
      $before_data = json_encode($item_data);
      $action = 'delete';
      $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, item_id, before_data) VALUES (?, ?, ?, ?)");
      mysqli_stmt_bind_param($log_stmt, 'isis', $user_id, $action, $delete_id, $before_data);
      mysqli_stmt_execute($log_stmt);
      mysqli_stmt_close($log_stmt);
    }
    $_SESSION['success'] = 'Item deleted successfully.';
    header('Location: inventory.php');
    exit;
  } else {
    $_SESSION['error'] = 'Failed to delete item.';
    header('Location: inventory.php');
    exit;
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
  // Fetch old data
  $old_stmt = mysqli_prepare($conn, "SELECT * FROM inventory WHERE item_id = ?");
  mysqli_stmt_bind_param($old_stmt, 'i', $edit_id);
  mysqli_stmt_execute($old_stmt);
  $old_result = mysqli_stmt_get_result($old_stmt);
  $old_data = mysqli_fetch_assoc($old_result);
  mysqli_stmt_close($old_stmt);
  $item_name = trim($_POST['item_name'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $unit = trim($_POST['unit'] ?? '');
  $image_path = null;
  $old_image_path = null;
  // Fetch current image_path
  $img_stmt = mysqli_prepare($conn, "SELECT image_path FROM inventory WHERE item_id = ?");
  mysqli_stmt_bind_param($img_stmt, 'i', $edit_id);
  mysqli_stmt_execute($img_stmt);
  mysqli_stmt_bind_result($img_stmt, $old_image_path);
  mysqli_stmt_fetch($img_stmt);
  mysqli_stmt_close($img_stmt);
  // Handle image upload
  if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $target_dir = 'assets/images/';
    $ext = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('inv_', true) . '.' . $ext;
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_file)) {
      $image_path = $target_file;
      // Delete old image if exists
      if ($old_image_path && file_exists($old_image_path)) {
        unlink($old_image_path);
      }
    }
  }
  if (!$item_name || !$category || !$unit) {
    $_SESSION['error'] = 'Item name, category, and unit are required.';
    header('Location: inventory.php');
    exit;
  } else {
    if ($image_path) {
      $stmt = mysqli_prepare($conn, "UPDATE inventory SET item_name = ?, category = ?, unit = ?, image_path = ? WHERE item_id = ?");
      mysqli_stmt_bind_param($stmt, 'ssssi', $item_name, $category, $unit, $image_path, $edit_id);
    } else {
      $stmt = mysqli_prepare($conn, "UPDATE inventory SET item_name = ?, category = ?, unit = ? WHERE item_id = ?");
      mysqli_stmt_bind_param($stmt, 'sssi', $item_name, $category, $unit, $edit_id);
    }
    if (mysqli_stmt_execute($stmt)) {
      // Audit log
      if ($old_data && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $before_data = json_encode($old_data);
        // Fetch new data
        $new_stmt = mysqli_prepare($conn, "SELECT * FROM inventory WHERE item_id = ?");
        mysqli_stmt_bind_param($new_stmt, 'i', $edit_id);
        mysqli_stmt_execute($new_stmt);
        $new_result = mysqli_stmt_get_result($new_stmt);
        $new_data = mysqli_fetch_assoc($new_result);
        mysqli_stmt_close($new_stmt);
        $after_data = json_encode($new_data);
        $action = 'edit';
        $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, item_id, before_data, after_data) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($log_stmt, 'isiss', $user_id, $action, $edit_id, $before_data, $after_data);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
      }
      $_SESSION['success'] = 'Item updated successfully.';
      header('Location: inventory.php');
      exit;
    } else {
      $_SESSION['error'] = 'Failed to update item.';
      header('Location: inventory.php');
      exit;
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
$categories = ['Vegetables', 'Fruits', 'Meat', 'Fish', 'Dairy', 'Beverages', 'Dry Goods', 'Canned Goods', 'Frozen', 'Other'];
$units = ['kg', 'g', 'L', 'ml', 'pcs', 'pack', 'box'];
?>
<main style="margin-left:220px; padding:32px 16px 16px 16px; background:var(--bg); min-height:100vh;">
  <?php if ($error): ?>
    <script>
      window.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: undefined,
          title: '<div style="display:flex;align-items:center;gap:10px;"><i class=\'fa-solid fa-triangle-exclamation\' style=\'color:#dc3545;font-size:1.2em;\'></i> <span>' + <?php echo json_encode($error); ?> + '</span></div>',
          background: 'rgba(255,255,255,0.97)',
          color: '#dc3545',
          showConfirmButton: false,
          timer: 3000,
          customClass: { popup: 'swal2-toast-glassy-error' },
          didOpen: (toast) => {
            toast.style.borderLeft = '6px solid #dc3545';
            toast.style.boxShadow = '0 2px 8px #c6282822';
            toast.style.fontWeight = '700';
          }
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
          icon: undefined,
          title: '<div style="display:flex;align-items:center;gap:10px;"><i class=\'fa-solid fa-circle-check\' style=\'color:#43a047;font-size:1.2em;\'></i> <span>' + <?php echo json_encode($success); ?> + '</span></div>',
          background: 'rgba(255,255,255,0.97)',
          color: '#43a047',
          showConfirmButton: false,
          timer: 3000,
          customClass: { popup: 'swal2-toast-glassy-success' },
          didOpen: (toast) => {
            toast.style.borderLeft = '6px solid #43a047';
            toast.style.boxShadow = '0 2px 8px #43a04722';
            toast.style.fontWeight = '700';
          }
        });
      });
    </script>
  <?php endif; ?>
  <div class="flex flex-between mb-3" style="align-items:center; flex-wrap:wrap; gap:16px;">
    <h1 style="font-size:2rem; font-weight:700;">Inventory</h1>
    <button class="btn btn-accent" id="addItemBtn"><i class="fa-solid fa-plus"></i> Add Item</button>
  </div>
  <div class="card shadow">
    <div class="flex flex-between mb-2" style="align-items:center; flex-wrap:wrap; gap:12px;">
      <input type="text" id="search" placeholder="Search inventory..." style="max-width:320px;">
      <span class="text-muted" style="font-size:0.98rem;"><i class="fa-solid fa-circle-info"></i> Click column headers
        to sort</span>
    </div>
    <div style="overflow-x:auto;">
      <table class="table" id="inventoryTable">
        <thead>
          <tr>
            <th>Image</th>
            <th data-sort="item_name">Name <i class="fa-solid fa-sort"></i></th>
            <th data-sort="category">Category <i class="fa-solid fa-sort"></i></th>
            <th data-sort="unit">Unit <i class="fa-solid fa-sort"></i></th>
            <th data-sort="created_at">Added <i class="fa-solid fa-sort"></i></th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="inventoryBody">
          <?php foreach ($items as $item): ?>
            <tr>
              <td>
                <?php if (!empty($item['image_path'])): ?>
                  <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="Item Image"
                    style="width:40px; height:40px; object-fit:cover; border-radius:4px;">
                <?php else: ?>
                  <span
                    style="display:inline-block; width:40px; height:40px; background:#eee; color:#bbb; text-align:center; line-height:40px; border-radius:4px;"><i
                      class="fa-solid fa-image"></i></span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($item['item_name']); ?></td>
              <td><?php echo htmlspecialchars($item['category']); ?></td>
              <td><?php echo htmlspecialchars($item['unit']); ?></td>
              <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
              <td>
                <button class="btn btn-warning btn-sm editBtn" data-id="<?php echo $item['item_id']; ?>"><i
                    class="fa-solid fa-pen"></i></button>
                <a href="inventory.php?delete=<?php echo $item['item_id']; ?>" class="btn btn-danger btn-sm deleteBtn"
                  data-id="<?php echo $item['item_id']; ?>"><i class="fa-solid fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Modal for Add/Edit Item -->
  <div id="itemModal" class="modal"
    style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.18); z-index:999; align-items:center; justify-content:center;">
    <div class="card shadow" style="max-width:400px; width:100%; position:relative;">
      <button id="closeModal"
        style="position:absolute; top:12px; right:12px; background:none; border:none; font-size:1.3rem; color:var(--danger); cursor:pointer;"><i
          class="fa-solid fa-xmark"></i></button>
      <h2 id="modalTitle" style="font-size:1.2rem; font-weight:600; margin-bottom:16px;"></h2>
      <form id="itemForm" method="post" action="inventory.php" enctype="multipart/form-data">
        <input type="hidden" name="item_id" id="modal_item_id">
        <input type="hidden" name="edit_id" id="modal_edit_id">
        <label for="modal_item_name">Name <i class="fa-solid fa-circle-question text-info"
            title="Enter the item name."></i></label>
        <input type="text" name="item_name" id="modal_item_name" required>
        <label for="modal_category">Category <i class="fa-solid fa-circle-question text-info"
            title="Select the item category."></i></label>
        <select name="category" id="modal_category" required>
          <option hidden value="">Select Category</option>
          <?php foreach (
            $categories as $cat): ?>
            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
          <?php endforeach; ?>
        </select>
        <label for="modal_unit">Unit <i class="fa-solid fa-circle-question text-info"
            title="Select the unit of measurement."></i></label>
        <select name="unit" id="modal_unit" required>
          <option hidden value="">Select Unit</option>
          <?php foreach ($units as $unit): ?>
            <option value="<?php echo $unit; ?>"><?php echo $unit; ?></option>
          <?php endforeach; ?>
        </select>
        <label for="modal_item_image">Image <i class="fa-solid fa-circle-question text-info"
            title="Optional: Upload an image for this item."></i></label>
        <input type="file" name="item_image" id="modal_item_image" accept="image/*">
        <div class="flex flex-between mt-2">
          <button type="submit" class="btn btn-accent" id="saveBtn" name="add_item"><i class="fa-solid fa-save"></i>
            Save</button>
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
  const modalItemId = document.getElementById('modal_item_id');
  const modalItemName = document.getElementById('modal_item_name');
  const modalCategory = document.getElementById('modal_category');
  const modalUnit = document.getElementById('modal_unit');
  const modalImage = document.getElementById('modal_item_image');
  // Add image preview for edit
  let imagePreview = document.getElementById('modal_image_preview');
  if (!imagePreview) {
    imagePreview = document.createElement('div');
    imagePreview.id = 'modal_image_preview';
    imagePreview.style = 'margin-bottom:10px; text-align:center;';
    modalImage.parentNode.insertBefore(imagePreview, modalImage.nextSibling);
  }
  Array.from(document.getElementsByClassName('editBtn')).forEach(function (btn) {
    btn.addEventListener('click', function () {
      saveBtn.name = 'update_item';
      modalEditId.value = btn.getAttribute('data-id');
      // Find the row and extract data
      const row = btn.closest('tr');
      // Table columns: 0=Image, 1=Name, 2=Category, 3=Unit, 4=Expiry, 5=Added, 6=Actions
      modalItemName.value = row.children[1].textContent.trim();
      modalCategory.value = row.children[2].textContent.trim();
      modalUnit.value = row.children[3].textContent.trim();
      // Set image preview if available
      const imgCell = row.children[0];
      const imgTag = imgCell.querySelector('img');
      if (imgTag) {
        imagePreview.innerHTML = `<img src='${imgTag.src}' alt='Current Image' style='width:60px; height:60px; object-fit:cover; border-radius:4px;'>`;
      } else {
        imagePreview.innerHTML = '';
      }
    });
  });
  addItemBtn.addEventListener('click', function () {
    saveBtn.name = 'add_item';
    modalEditId.value = '';
    modalItemName.value = '';
    modalCategory.value = '';
    modalUnit.value = '';
    imagePreview.innerHTML = '';
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include 'includes/footer.php'; ?>
</body>

</html>