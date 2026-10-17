(() => {
    const filterType = document.getElementById('filterType');
    const filterDateWrap = document.getElementById('filterDateWrap');

    if (!filterType || !filterDateWrap) {
        return;
    }

    const toggleDateField = () => {
        filterDateWrap.hidden = filterType.value === '';
    };

    filterType.addEventListener('change', toggleDateField);
    toggleDateField();
})();
