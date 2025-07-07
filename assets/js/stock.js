// Modal logic for Quick Stock In/Out
const quickStockBtn = document.getElementById('quickStockBtn');
const stockModal = document.getElementById('stockModal');
const closeStockModal = document.getElementById('closeStockModal');
const cancelStockBtn = document.getElementById('cancelStockBtn');
const stockForm = document.getElementById('stockForm');

// Open modal
quickStockBtn.addEventListener('click', function() {
  stockForm.reset();
  stockModal.style.display = 'flex';
});

// Close modal
closeStockModal.addEventListener('click', function() {
  stockModal.style.display = 'none';
});
cancelStockBtn.addEventListener('click', function() {
  stockModal.style.display = 'none';
});
window.addEventListener('click', function(e) {
  if (e.target === stockModal) stockModal.style.display = 'none';
});

// Client-side validation
stockForm.addEventListener('submit', function(e) {
  if (!stockForm.modal_item_id.value || !stockForm.modal_type.value || !stockForm.modal_quantity.value || parseInt(stockForm.modal_quantity.value) < 1) {
    alert('Please fill in all required fields and enter a valid quantity.');
    e.preventDefault();
  }
}); 