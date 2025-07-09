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
    e.preventDefault();
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: 'Please fill in all required fields and enter a valid quantity.',
      showConfirmButton: false,
      timer: 3000
    });
    return false;
  }
  
  // Validate expiry date for stock in
  if (stockForm.modal_type.value === 'in' && !stockForm.batch_expiry_date.value) {
    e.preventDefault();
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: 'Expiry date is required for stock in entries.',
      showConfirmButton: false,
      timer: 3000
    });
    return false;
  }
  // Confirmation dialog
  e.preventDefault();
  const type = stockForm.modal_type.value === 'in' ? 'Stock In' : 'Stock Out';
  const itemSelect = stockForm.modal_item_id;
  const itemName = itemSelect.options[itemSelect.selectedIndex].text;
  const qty = stockForm.modal_quantity.value;
  const expiry = stockForm.batch_expiry_date ? stockForm.batch_expiry_date.value : '';
  let html = `<b>Type:</b> ${type}<br><b>Item:</b> ${itemName}<br><b>Quantity:</b> ${qty}`;
  if (type === 'Stock In' && expiry) html += `<br><b>Expiry:</b> ${expiry}`;
  Swal.fire({
    title: 'Confirm Quick ' + type + '?',
    html: html,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, Save',
    cancelButtonText: 'Cancel',
    customClass: { popup: 'swal2-toast-glassy-success' }
  }).then((result) => {
    if (result.isConfirmed) {
      stockForm.submit();
    }
  });
  return false;
});

// Expiry date suggestion functionality
const suggestExpiryBtn = document.getElementById('suggestExpiryBtn');
const clearExpiryBtn = document.getElementById('clearExpiryBtn');
const expirySuggestion = document.getElementById('expiry_suggestion');

// Item data for expiry suggestions (populated from PHP)
const itemData = window.itemData || [];
const expiryDefaults = window.expiryDefaults || {};

// Suggest expiry date based on item category
if (suggestExpiryBtn) {
  suggestExpiryBtn.addEventListener('click', function() {
    const selectedItemId = stockForm.modal_item_id.value;
    if (!selectedItemId) {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'warning',
        title: 'Please select an item first.',
        showConfirmButton: false,
        timer: 3000
      });
      return;
    }
    
    const selectedItem = itemData.find(item => item.item_id == selectedItemId);
    if (selectedItem && expiryDefaults[selectedItem.category]) {
      const defaultDays = expiryDefaults[selectedItem.category].default_days;
      const suggestedDate = new Date();
      suggestedDate.setDate(suggestedDate.getDate() + defaultDays);
      
      const formattedDate = suggestedDate.toISOString().split('T')[0];
      stockForm.batch_expiry_date.value = formattedDate;
      
      expirySuggestion.innerHTML = `<i class="fa-solid ${expiryDefaults[selectedItem.category].icon}"></i> ${expiryDefaults[selectedItem.category].description}`;
    } else {
      // Default suggestion
      const suggestedDate = new Date();
      suggestedDate.setDate(suggestedDate.getDate() + 30);
      const formattedDate = suggestedDate.toISOString().split('T')[0];
      stockForm.batch_expiry_date.value = formattedDate;
      
      expirySuggestion.innerHTML = '<i class="fa-solid fa-box"></i> Default suggestion: 30 days';
    }
  });
}

// Clear expiry date
if (clearExpiryBtn) {
  clearExpiryBtn.addEventListener('click', function() {
    stockForm.batch_expiry_date.value = '';
    expirySuggestion.innerHTML = '';
  });
}

// Auto-suggest when item is selected
if (stockForm && stockForm.modal_item_id) {
  stockForm.modal_item_id.addEventListener('change', function() {
    const selectedItemId = this.value;
    if (selectedItemId) {
      const selectedItem = itemData.find(item => item.item_id == selectedItemId);
      if (selectedItem && expiryDefaults[selectedItem.category]) {
        const defaultDays = expiryDefaults[selectedItem.category].default_days;
        const suggestedDate = new Date();
        suggestedDate.setDate(suggestedDate.getDate() + defaultDays);
        
        const formattedDate = suggestedDate.toISOString().split('T')[0];
        stockForm.batch_expiry_date.value = formattedDate;
        
        expirySuggestion.innerHTML = `<i class="fa-solid ${expiryDefaults[selectedItem.category].icon}"></i> ${expiryDefaults[selectedItem.category].description}`;
      }
    }
  });
}

// Show/hide expiry date input based on type
const expiryDateInput = document.getElementById('modal_batch_expiry_date');
const expiryLabel = expiryDateInput ? expiryDateInput.previousElementSibling : null;
const typeSelect = document.getElementById('modal_type');
const expiryDateGroup = document.getElementById('expiry_date_group');
if (typeSelect && expiryDateGroup) {
  function toggleExpiryInput() {
    if (typeSelect.value === 'out') {
      expiryDateGroup.style.display = 'none';
    } else {
      expiryDateGroup.style.display = '';
    }
  }
  typeSelect.addEventListener('change', toggleExpiryInput);
  toggleExpiryInput(); // Initial
}

// Help button functionality
const helpBtn = document.getElementById('helpBtn');
if (helpBtn) {
  helpBtn.addEventListener('click', function() {
    Swal.fire({
      title: 'Stock Management Help',
      html: `
        <div style="text-align: left;">
          <h4 style="color: #007bff; margin-bottom: 16px;">📋 How to Add Stock:</h4>
          <ol style="margin-bottom: 16px;">
            <li>Click "Quick In/Out" button</li>
            <li>Select the item from dropdown</li>
            <li>Choose "Stock In" type</li>
            <li>Enter quantity</li>
            <li>Set expiry date (or use "Suggest" button)</li>
            <li>Click "Save"</li>
          </ol>
          
          <h4 style="color: #28a745; margin-bottom: 16px;">🎯 Smart Expiry Suggestions:</h4>
          <ul style="margin-bottom: 16px;">
            <li><i class="fa-solid fa-carrot"></i> <strong>Vegetables/Fruits:</strong> 7 days</li>
            <li><i class="fa-solid fa-drumstick-bite"></i> <strong>Meat:</strong> 3 days</li>
            <li><i class="fa-solid fa-fish"></i> <strong>Fish:</strong> 2 days</li>
            <li><i class="fa-solid fa-cheese"></i> <strong>Dairy:</strong> 7 days</li>
            <li><i class="fa-solid fa-wine-bottle"></i> <strong>Beverages:</strong> 30 days</li>
            <li><i class="fa-solid fa-wheat-awn"></i> <strong>Dry Goods:</strong> 1 year</li>
            <li><i class="fa-solid fa-can-food"></i> <strong>Canned Goods:</strong> 2 years</li>
            <li><i class="fa-solid fa-snowflake"></i> <strong>Frozen:</strong> 90 days</li>
          </ul>
          
          <h4 style="color: #ffc107; margin-bottom: 16px;">💡 Tips:</h4>
          <ul>
            <li>Use "Suggest" button for automatic expiry date</li>
            <li>Use "Clear" button to reset expiry date</li>
            <li>Stock levels only count non-expired items</li>
            <li>Each batch has its own expiry date</li>
          </ul>
        </div>
      `,
      width: '600px',
      confirmButtonText: 'Got it!',
      confirmButtonColor: '#007bff'
    });
  });
}

// Batch details expand/collapse and rendering
window.addEventListener('DOMContentLoaded', function () {
  const batchDetails = window.batchDetails || {};
  const detailBtns = document.querySelectorAll('.batch-details-btn');
  if (detailBtns.length === 0) {
    console.warn('No Details buttons found.');
  } else {
    console.log('Attaching Details button events to', detailBtns.length, 'buttons.');
  }
  detailBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const itemId = this.getAttribute('data-item-id');
      const detailsRow = document.getElementById('batch-details-' + itemId);
      const wrapper = detailsRow.querySelector('.batch-details-table-wrapper');
      if (detailsRow.style.display === 'none') {
        // Render batch table
        let html = '';
        if (batchDetails[itemId] && batchDetails[itemId].length > 0) {
          html += '<table class="table" style="margin-bottom:0;">';
          html += '<thead><tr><th>Quantity</th><th>Expiry Date</th><th>Days Left</th><th>Stock-In Date</th></tr></thead><tbody>';
          batchDetails[itemId].forEach(batch => {
            const expiry = batch.batch_expiry_date;
            const qty = batch.batch_quantity;
            const stockIn = batch.stock_in_date ? new Date(batch.stock_in_date) : null;
            let expiryDate = new Date(expiry);
            let today = new Date();
            let daysLeft = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
            let expiryClass = '';
            let expiryText = '';
            if (daysLeft < 0) {
              expiryClass = 'text-danger';
              expiryText = `Expired ${-daysLeft} days ago`;
            } else if (daysLeft === 0) {
              expiryClass = 'text-warning';
              expiryText = 'Expires today';
            } else if (daysLeft <= 7) {
              expiryClass = 'text-orange';
              expiryText = daysLeft + ' days left';
            } else {
              expiryClass = 'text-success';
              expiryText = daysLeft + ' days left';
            }
            html += `<tr><td>${qty}</td><td>${expiry}</td><td class="${expiryClass}">${expiryText}</td><td>${stockIn ? stockIn.toLocaleDateString() : '-'}</td></tr>`;
          });
          html += '</tbody></table>';
        } else {
          html = '<div class="text-muted">No batch details available.</div>';
        }
        wrapper.innerHTML = html;
        detailsRow.style.display = '';
        this.querySelector('i').classList.remove('fa-chevron-down');
        this.querySelector('i').classList.add('fa-chevron-up');
      } else {
        detailsRow.style.display = 'none';
        wrapper.innerHTML = '';
        this.querySelector('i').classList.remove('fa-chevron-up');
        this.querySelector('i').classList.add('fa-chevron-down');
      }
    });
  });

  // Confirmation for main stock in/out form
  const mainStockForm = document.querySelector('.stock-card form[action="stock.php"][method="post"]');
  if (mainStockForm) {
    mainStockForm.addEventListener('submit', function(e) {
      const itemSelect = mainStockForm.querySelector('[name="item_id"]');
      const typeSelect = mainStockForm.querySelector('[name="type"]');
      const qtyInput = mainStockForm.querySelector('[name="quantity"]');
      const expiryInput = mainStockForm.querySelector('[name="batch_expiry_date"]');
      if (!itemSelect.value || !typeSelect.value || !qtyInput.value || parseInt(qtyInput.value) < 1) {
        e.preventDefault();
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'Please fill in all required fields and enter a valid quantity.',
          showConfirmButton: false,
          timer: 3000
        });
        return false;
      }
      if (typeSelect.value === 'in' && !expiryInput.value) {
        e.preventDefault();
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'Expiry date is required for stock in entries.',
          showConfirmButton: false,
          timer: 3000
        });
        return false;
      }
      e.preventDefault();
      const type = typeSelect.value === 'in' ? 'Stock In' : 'Stock Out';
      const itemName = itemSelect.options[itemSelect.selectedIndex].text;
      const qty = qtyInput.value;
      const expiry = expiryInput ? expiryInput.value : '';
      let html = `<b>Type:</b> ${type}<br><b>Item:</b> ${itemName}<br><b>Quantity:</b> ${qty}`;
      if (type === 'Stock In' && expiry) html += `<br><b>Expiry:</b> ${expiry}`;
      Swal.fire({
        title: 'Confirm ' + type + '?',
        html: html,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Save',
        cancelButtonText: 'Cancel',
        customClass: { popup: 'swal2-toast-glassy-success' }
      }).then((result) => {
        if (result.isConfirmed) {
          mainStockForm.submit();
        }
      });
      return false;
    });
  }
}); 