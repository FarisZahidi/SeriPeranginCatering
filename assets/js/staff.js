// JS for staff management (add interactivity or validation here if needed) 

// Modal logic for Add/Edit Staff
const addStaffBtn = document.getElementById('addStaffBtn');
const staffModal = document.getElementById('staffModal');
const closeStaffModal = document.getElementById('closeStaffModal');
const cancelStaffBtn = document.getElementById('cancelStaffBtn');
const staffForm = document.getElementById('staffForm');
const staffModalTitle = document.getElementById('staffModalTitle');
const roleDesc = document.getElementById('roleDesc');
const saveStaffBtn = document.getElementById('saveStaffBtn');
const modalEditStaffId = document.getElementById('modal_edit_id');

const roleDescriptions = {
  'Owner': 'Full access to all modules',
  'Staff': 'Stock in/out, update usage only'
};

// Open modal for Add
addStaffBtn.addEventListener('click', function() {
  saveStaffBtn.name = 'add_staff';
  modalEditStaffId.value = '';
  staffModalTitle.textContent = 'Add Staff';
  staffForm.reset();
  document.getElementById('modal_user_id').value = '';
  roleDesc.textContent = '';
  staffModal.style.display = 'flex';
});

// Open modal for Edit
Array.from(document.getElementsByClassName('editStaffBtn')).forEach(function(btn) {
  btn.addEventListener('click', function() {
    saveStaffBtn.name = 'update_staff';
    modalEditStaffId.value = btn.getAttribute('data-id');
    const row = btn.closest('tr');
    staffModalTitle.textContent = 'Edit Staff';
    document.getElementById('modal_name').value = row.cells[0].textContent.trim(); // Set name
    document.getElementById('modal_username').value = row.cells[1].textContent.trim(); // Set username
    document.getElementById('modal_password').value = '';
    // Role cell contains a span with badge and icon, so extract text from the span
    const roleCell = row.cells[2];
    const roleText = roleCell.querySelector('span').textContent.trim();
    document.getElementById('modal_role').value = roleText;
    roleDesc.textContent = roleDescriptions[roleText] || '';
    staffModal.style.display = 'flex';
  });
});

// Show role description on change
const modalRole = document.getElementById('modal_role');
modalRole.addEventListener('change', function() {
  roleDesc.textContent = roleDescriptions[modalRole.value] || '';
});

// Close modal
closeStaffModal.addEventListener('click', function() {
  staffModal.style.display = 'none';
});
cancelStaffBtn.addEventListener('click', function() {
  staffModal.style.display = 'none';
});
window.addEventListener('click', function(e) {
  if (e.target === staffModal) staffModal.style.display = 'none';
});

// Client-side validation (basic)
staffForm.addEventListener('submit', function(e) {
  if (!staffForm.username.value.trim() || !staffForm.role.value) {
    alert('Please fill in all required fields.');
    e.preventDefault();
  }
});

// Delete confirmation
Array.from(document.getElementsByClassName('deleteStaffBtn')).forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    if (!confirm('Delete this staff?')) e.preventDefault();
  });
}); 