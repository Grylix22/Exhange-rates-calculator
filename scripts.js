const searchInput = document.getElementById('searchInput');
const dataRows = document.querySelectorAll('.data-row');

searchInput.addEventListener('input', function() {
  const searchText = searchInput.value.toLowerCase();

  dataRows.forEach(row => {
    const rowData = row.textContent.toLowerCase();

    if (rowData.includes(searchText)) {
      row.style.display = 'table-row';
    } else {
      row.style.display = 'none';
    }
  });
});
