// Modal logic for Add/Edit Item
const addItemBtn = document.getElementById('addItemBtn');
const itemModal = document.getElementById('itemModal');
const closeModal = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn');
const itemForm = document.getElementById('itemForm');
const modalTitle = document.getElementById('modalTitle');
const saveBtn = document.getElementById('saveBtn');
const modalEditId = document.getElementById('modal_edit_id');

// Open modal for Add
addItemBtn.addEventListener('click', function() {
  saveBtn.name = 'add_item';
  modalEditId.value = '';
  modalTitle.textContent = 'Add Inventory Item';
  itemForm.reset();
  itemModal.style.display = 'flex';
});

// Open modal for Edit
Array.from(document.getElementsByClassName('editBtn')).forEach(function(btn) {
  btn.addEventListener('click', function() {
    saveBtn.name = 'update_item';
    modalEditId.value = btn.getAttribute('data-id');
    const row = btn.closest('tr');
    modalTitle.textContent = 'Edit Inventory Item';
    document.getElementById('modal_item_name').value = row.cells[0].textContent.trim();
    document.getElementById('modal_category').value = row.cells[1].textContent.trim();
    document.getElementById('modal_unit').value = row.cells[2].textContent.trim();
    document.getElementById('modal_expiry_date').value = row.cells[3].textContent.trim() === '-' ? '' : row.cells[3].textContent.trim();
    itemModal.style.display = 'flex';
  });
});

// Close modal
closeModal.addEventListener('click', function() {
  itemModal.style.display = 'none';
});
cancelBtn.addEventListener('click', function() {
  itemModal.style.display = 'none';
});
window.addEventListener('click', function(e) {
  if (e.target === itemModal) itemModal.style.display = 'none';
});

// Client-side validation (basic)
itemForm.addEventListener('submit', function(e) {
  if (!itemForm.modal_item_name.value.trim() || !itemForm.category.value || !itemForm.unit.value) {
    alert('Please fill in all required fields.');
    e.preventDefault();
  }
});

// Delete confirmation
Array.from(document.getElementsByClassName('deleteBtn')).forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    if (!confirm('Delete this item?')) e.preventDefault();
  });
});

// Search/filter table
const searchInput = document.getElementById('search');
searchInput.addEventListener('input', function() {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll('#inventoryTable tbody tr');
  rows.forEach(function(row) {
    const name = row.cells[0].textContent.toLowerCase();
    const category = row.cells[1].textContent.toLowerCase();
    if (name.includes(filter) || category.includes(filter)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});

// Sortable columns (simple client-side sort)
document.querySelectorAll('#inventoryTable th[data-sort]').forEach(function(th, idx) {
  th.addEventListener('click', function() {
    const table = th.closest('table');
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.rows);
    const dir = th.classList.toggle('asc') ? 1 : -1;
    rows.sort(function(a, b) {
      const aText = a.cells[idx].textContent.trim().toLowerCase();
      const bText = b.cells[idx].textContent.trim().toLowerCase();
      return aText.localeCompare(bText) * dir;
    });
    rows.forEach(row => tbody.appendChild(row));
  });
}); 