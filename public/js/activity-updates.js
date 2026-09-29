(() => {
    const search = document.getElementById('update-search');
    const project = document.getElementById('update-project');
    const worker = document.getElementById('update-worker');
    const activityType = document.getElementById('update-activity-type');
    const date = document.getElementById('update-date');
    const rows = Array.from(document.querySelectorAll('#update-rows tr'));
    const count = document.getElementById('updates-count');
    const empty = document.getElementById('updates-empty');

    function renderUpdates() {
        const term = (search?.value || '').toLowerCase().trim();
        let visible = 0;

        rows.forEach(function (row, index) {
            const matches =
                (!term || row.dataset.search.includes(term)) &&
                (!project || project.value === 'all' || row.dataset.project === project.value) &&
                (!worker || worker.value === 'all' || row.dataset.worker === worker.value) &&
                (!activityType || activityType.value === 'all' || row.dataset.activityType === activityType.value) &&
                (!date || !date.value || row.dataset.date === date.value);

            row.hidden = !matches;

            if (matches) {
                visible++;
                row.children[1].textContent = visible;
            } else {
                row.children[1].textContent = index + 1;
            }
        });

        if (count) {
            count.textContent = 'Showing ' + visible + ' of ' + rows.length + ' updates';
        }

        if (empty) {
            empty.hidden = visible > 0;
        }
    }

    function exportCsv() {
        const visible = rows.filter(function (row) { return !row.hidden; });
        const output = [['Worker', 'Project', 'Activity Type', 'Remark', 'Location', 'Time', 'Status']];

        visible.forEach(function (row) {
            output.push([
                row.children[3].innerText.trim(),
                row.children[4].innerText.trim(),
                row.children[5].innerText.trim(),
                row.children[6].innerText.trim(),
                row.children[7].innerText.trim(),
                row.children[8].innerText.trim(),
                row.children[9].innerText.trim(),
            ]);
        });

        const csv = output.map(function (line) {
            return line.map(function (cell) {
                return '"' + String(cell).replaceAll('"', '""') + '"';
            }).join(',');
        }).join('\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'activity-updates.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    [search, project, worker, activityType, date].forEach(function (control) {
        control?.addEventListener('input', renderUpdates);
        control?.addEventListener('change', renderUpdates);
    });

    document.getElementById('update-reset')?.addEventListener('click', function () {
        if (search) search.value = '';
        if (project) project.value = 'all';
        if (worker) worker.value = 'all';
        if (activityType) activityType.value = 'all';
        if (date) date.value = '';
        renderUpdates();
    });

    document.getElementById('export-updates')?.addEventListener('click', exportCsv);

    renderUpdates();
})();
