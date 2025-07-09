// JS for reports (add interactivity or charting here if needed) 

// Add a global for print data (set in reports.php)
window.printData = window.printData || {};

// Print table
function printTable(tableId) {
  const table = document.getElementById(tableId).outerHTML;
  // Prepare summary and logs for print
  const summary = window.printData.summary || {};
  const logs = window.printData.logs || [];
  // Build summary HTML
  let summaryHtml = `<div class='print-summary' style='display:flex; justify-content:center; gap:32px; margin-bottom:24px;'>
    <div style='background:#f8f9fa; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06); padding:18px 28px; min-width:140px; text-align:center;'>
      <div style='font-size:1.1rem; color:#888; margin-bottom:6px;'><i class='fa-solid fa-warehouse' style='color:#28a745;'></i> Total Items</div>
      <div style='font-size:2.1rem; font-weight:700; color:#28a745;'>${summary.total_items || '-'}</div>
    </div>
    <div style='background:#fffbe6; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06); padding:18px 28px; min-width:140px; text-align:center;'>
      <div style='font-size:1.1rem; color:#bfa100; margin-bottom:6px;'><i class='fa-solid fa-triangle-exclamation' style='color:#ffc107;'></i> Low Stock</div>
      <div style='font-size:2.1rem; font-weight:700; color:#bfa100;'>${summary.low_stock || '-'}</div>
    </div>
    <div style='background:#fff0f0; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06); padding:18px 28px; min-width:140px; text-align:center;'>
      <div style='font-size:1.1rem; color:#c00; margin-bottom:6px;'><i class='fa-solid fa-calendar-xmark' style='color:#dc3545;'></i> Expired Items</div>
      <div style='font-size:2.1rem; font-weight:700; color:#c00;'>${summary.expired || '-'}</div>
    </div>
  </div>`;
  // Build logs HTML
  let logsHtml = `<div class='print-logs' style='margin-bottom:18px;'>
    <h3 style='margin-bottom:8px;'>Recent Stock Movements</h3>
    <table class='table' style='font-size:1.02rem;'>
      <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Qty</th><th>User</th></tr></thead>
      <tbody>`;
  logs.forEach(log => {
    logsHtml += `<tr>
      <td>${log.log_date ? new Date(log.log_date).toLocaleString() : '-'}</td>
      <td>${log.item_name || '-'}</td>
      <td>${log.type ? (log.type === 'in' ? 'Stock In' : 'Stock Out') : '-'}</td>
      <td>${log.quantity || '-'}</td>
      <td>${log.username || '-'}</td>
    </tr>`;
  });
  logsHtml += '</tbody></table></div>';
  // Signature section
  let signatureHtml = `<div class='print-signatures' style='margin-top:40px; display:flex; justify-content:space-between; gap:40px;'>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Prepared by</div>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Approved by</div>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Date</div>
  </div>`;

  const win = window.open('', '', 'width=900,height=700');
  win.document.write('<html><head><title>Print Inventory Table</title>');
  win.document.write('<link rel="stylesheet" href="assets/css/global.css">');
  win.document.write(`
    <style>
      @media print {
        body { background: #fff; color: #222; }
        .print-header { text-align:center; margin-bottom:24px; }
        .print-logo { width:60px; margin-bottom:8px; }
        .print-date { font-size:0.98rem; color:#888; margin-bottom:12px; }
        .table { font-size:1.12rem; border-collapse:collapse; width:100%; margin-bottom:24px; }
        .table th, .table td { padding: 14px 12px; border: 1px solid #bbb; }
        .table th { background: #f2f2f2; font-weight:700; }
        .table tr:nth-child(even) { background: #fafafa; }
        .print-footer { text-align:center; font-size:0.95rem; color:#888; position:fixed; bottom:0; left:0; width:100%; padding:8px 0; }
        .print-summary, .print-logs { margin-bottom:24px; }
        .print-signatures { margin-top:40px; }
        @page { margin-bottom: 40px; }
      }
    </style>
  `);
  win.document.write('</head><body>');
  win.document.write('<div class="print-header">');
  win.document.write('<img src="assets/images/logo.png" class="print-logo" alt="Logo" onerror="this.style.display=\'none\'">');
  win.document.write('<h2 style="margin-bottom:4px;">Seri Perangin Catering</h2>');
  win.document.write('<div class="print-date">Printed: ' + new Date().toLocaleString() + '</div>');
  win.document.write('<h3 style="margin-bottom:16px;">Inventory Report</h3>');
  win.document.write('</div>');
  win.document.write(summaryHtml);
  win.document.write(table);
  win.document.write(logsHtml);
  win.document.write(signatureHtml);
  win.document.write('<div class="print-footer">Page <span class="pageNumber"></span> | Seri Perangin Catering</div>');
  win.document.write('<script>window.onload = function() { if (window.matchMedia) { window.print(); } else { print(); } }</script>');
  win.document.write('</body></html>');
  win.document.close();
}

// Print Inventory List only
function printInventory() {
  // Get the new summary block structure instead of table
  const inventoryContent = document.getElementById('inventoryContainer').innerHTML;
  const summary = window.printData.summary || {};
  let summaryHtml = `<div class='print-summary' style='display:flex; justify-content:center; gap:32px; margin-bottom:24px;'>
    <div style='background:#f8f9fa; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06); padding:18px 28px; min-width:140px; text-align:center;'>
      <div style='font-size:1.1rem; color:#888; margin-bottom:6px;'><i class='fa-solid fa-warehouse' style='color:#28a745;'></i> Total Items</div>
      <div style='font-size:2.1rem; font-weight:700; color:#28a745;'>${summary.total_items || '-'}</div>
    </div>
    <div style='background:#fffbe6; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06); padding:18px 28px; min-width:140px; text-align:center;'>
      <div style='font-size:1.1rem; color:#bfa100; margin-bottom:6px;'><i class='fa-solid fa-triangle-exclamation' style='color:#ffc107;'></i> Low Stock</div>
      <div style='font-size:2.1rem; font-weight:700; color:#bfa100;'>${summary.low_stock || '-'}</div>
    </div>
    <div style='background:#fff0f0; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06); padding:18px 28px; min-width:140px; text-align:center;'>
      <div style='font-size:1.1rem; color:#c00; margin-bottom:6px;'><i class='fa-solid fa-calendar-xmark' style='color:#dc3545;'></i> Expired Items</div>
      <div style='font-size:2.1rem; font-weight:700; color:#c00;'>${summary.expired || '-'}</div>
    </div>
  </div>`;
  let signatureHtml = `<div class='print-signatures' style='margin-top:40px; display:flex; justify-content:space-between; gap:40px;'>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Prepared by</div>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Approved by</div>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Date</div>
  </div>`;
  const win = window.open('', '', 'width=900,height=700');
  win.document.write('<html><head><title>Print Inventory List</title>');
  win.document.write('<link rel="stylesheet" href="assets/css/global.css">');
  win.document.write(`
    <style>
      @media print {
        body { background: #fff; color: #222; }
        .print-header { text-align:center; margin-bottom:24px; }
        .print-logo { width:60px; margin-bottom:8px; }
        .print-date { font-size:0.98rem; color:#888; margin-bottom:12px; }
        .table { font-size:1.12rem; border-collapse:collapse; width:100%; margin-bottom:24px; }
        .table th, .table td { padding: 14px 12px; border: 1px solid #bbb; }
        .table th { background: #f2f2f2; font-weight:700; }
        .table tr:nth-child(even) { background: #fafafa; }
        .print-footer { text-align:center; font-size:0.95rem; color:#888; position:fixed; bottom:0; left:0; width:100%; padding:8px 0; }
        .print-summary { margin-bottom:24px; }
        .print-signatures { margin-top:40px; }
        .item-summary-block { 
          margin-bottom:18px; 
          padding:14px 18px 10px 18px; 
          background:#f8f9fa; 
          border-radius:7px; 
          box-shadow:0 1px 3px rgba(0,0,0,0.03); 
          border:1px solid #e0e0e0;
          page-break-inside: avoid;
        }
        .item-summary-block > div:first-child { 
          font-size:1.08em; 
          font-weight:700; 
          color:#333; 
          margin-bottom:4px; 
        }
        .item-summary-block > div:nth-child(2) { 
          display:flex; 
          flex-wrap:wrap; 
          gap:18px; 
          font-size:0.98em; 
          color:#444; 
          margin-bottom:2px; 
        }
        .item-summary-block > div:nth-child(2) > div { 
          margin-bottom:4px; 
        }
        .item-summary-block table { 
          margin:8px 0 0 0; 
          font-size:0.96em; 
          border-collapse:collapse; 
          width:100%; 
        }
        .item-summary-block table th { 
          padding:6px 8px; 
          border:1px solid #e0e0e0; 
          font-weight:600; 
          background:#f1f1f1; 
        }
        .item-summary-block table td { 
          padding:6px 8px; 
          border:1px solid #e0e0e0; 
        }
        .item-summary-block table tr[style*="background:#ffe5e5"] { 
          background:#ffe5e5 !important; 
        }
        .item-summary-block table tr[style*="background:#fff7e6"] { 
          background:#fff7e6 !important; 
        }
        .item-summary-block table tr[style*="background:#f7f7f7"] { 
          background:#f7f7f7 !important; 
        }
        @page { margin-bottom: 40px; }
      }
    </style>
  `);
  win.document.write('</head><body>');
  win.document.write('<div class="print-header">');
  win.document.write('<img src="assets/images/logo.png" class="print-logo" alt="Logo" onerror="this.style.display=\'none\'">');
  win.document.write('<h2 style="margin-bottom:4px;">Seri Perangin Catering</h2>');
  win.document.write('<div class="print-date">Printed: ' + new Date().toLocaleString() + '</div>');
  win.document.write('<h3 style="margin-bottom:16px;">Inventory Report</h3>');
  win.document.write('</div>');
  win.document.write(summaryHtml);
  win.document.write('<div class="inventory-content">' + inventoryContent + '</div>');
  win.document.write(signatureHtml);
  win.document.write('<div class="print-footer">Page <span class="pageNumber"></span> | Seri Perangin Catering</div>');
  win.document.write('<script>window.onload = function() { if (window.matchMedia) { window.print(); } else { print(); } }</script>');
  win.document.write('</body></html>');
  win.document.close();
}

// Print Stock Log only
function printStockLog() {
  const summary = window.printData.summary || {};
  const logs = window.printData.logs || [];
  // Use the actual stock log table HTML for print
  const table = document.getElementById('stockLogTable').outerHTML;
  // No summary section for stock log printout
  let signatureHtml = `<div class='print-signatures' style='margin-top:40px; display:flex; justify-content:space-between; gap:40px;'>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Prepared by</div>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Approved by</div>
    <div style='text-align:center; flex:1;'><div style='height:48px;'></div>_________________________<br>Date</div>
  </div>`;
  const win = window.open('', '', 'width=900,height=700');
  win.document.write('<html><head><title>Print Stock Movements</title>');
  win.document.write('<link rel="stylesheet" href="assets/css/global.css">');
  win.document.write(`
    <style>
      @media print {
        body { background: #fff; color: #222; }
        .print-header { text-align:center; margin-bottom:24px; }
        .print-logo { width:60px; margin-bottom:8px; }
        .print-date { font-size:0.98rem; color:#888; margin-bottom:12px; }
        .table { font-size:1.12rem; border-collapse:collapse; width:100%; margin-bottom:24px; }
        .table th, .table td { padding: 14px 12px; border: 1px solid #bbb; }
        .table th { background: #f2f2f2; font-weight:700; }
        .table tr:nth-child(even) { background: #fafafa; }
        .print-footer { text-align:center; font-size:0.95rem; color:#888; position:fixed; bottom:0; left:0; width:100%; padding:8px 0; }
        .print-summary, .print-logs { margin-bottom:24px; }
        .print-signatures { margin-top:40px; }
        @page { margin-bottom: 40px; }
      }
    </style>
  `);
  win.document.write('</head><body>');
  win.document.write('<div class="print-header">');
  win.document.write('<img src="assets/images/logo.png" class="print-logo" alt="Logo" onerror="this.style.display=\'none\'">');
  win.document.write('<h2 style="margin-bottom:4px;">Seri Perangin Catering</h2>');
  win.document.write('<div class="print-date">Printed: ' + new Date().toLocaleString() + '</div>');
  win.document.write('<h3 style="margin-bottom:16px;">Stock Movements Report</h3>');
  win.document.write('</div>');
  win.document.write(table);
  win.document.write(signatureHtml);
  win.document.write('<div class="print-footer">Page <span class="pageNumber"></span> | Seri Perangin Catering</div>');
  win.document.write('<script>window.onload = function() { if (window.matchMedia) { window.print(); } else { print(); } }</script>');
  win.document.write('</body></html>');
  win.document.close();
}

// Bind new print buttons
// Wrap in DOMContentLoaded to ensure elements exist

document.addEventListener('DOMContentLoaded', function() {
  if (document.getElementById('printInventory')) {
    document.getElementById('printInventory').addEventListener('click', function() {
      printInventory();
    });
  }
  if (document.getElementById('printStockLog')) {
    document.getElementById('printStockLog').addEventListener('click', function() {
      printStockLog();
    });
  }
}); 