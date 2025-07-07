// JS for reports (add interactivity or charting here if needed) 

// Download table as CSV
function downloadTableAsCSV(tableId, filename) {
  const table = document.getElementById(tableId);
  let csv = '';
  for (let row of table.rows) {
    let rowData = [];
    for (let cell of row.cells) {
      let text = cell.textContent.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
      rowData.push('"' + text.replace(/"/g, '""') + '"');
    }
    csv += rowData.join(',') + '\n';
  }
  const blob = new Blob([csv], { type: 'text/csv' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

document.getElementById('downloadCSV').addEventListener('click', function() {
  downloadTableAsCSV('reportTable', 'inventory_report.csv');
});

// Print table
function printTable(tableId) {
  const table = document.getElementById(tableId).outerHTML;
  const win = window.open('', '', 'width=900,height=700');
  win.document.write('<html><head><title>Print Inventory Table</title>');
  win.document.write('<link rel="stylesheet" href="assets/css/global.css">');
  win.document.write('</head><body>');
  win.document.write('<h2>Inventory Table</h2>');
  win.document.write(table);
  win.document.write('</body></html>');
  win.document.close();
  win.print();
}

document.getElementById('printTable').addEventListener('click', function() {
  printTable('reportTable');
}); 