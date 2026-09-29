(() => {
    const trendData = Array.isArray(window.reportTrendData) ? window.reportTrendData : [];
    const series = [
        ['completed', '#18b979'],
        ['in_progress', '#1e70ef'],
        ['pending', '#ff8c18'],
        ['rejected', '#ef374d'],
    ];

    function renderTrend() {
        const host = document.getElementById('trend-chart');
        if (!host) return;

        const values = trendData.flatMap(function (row) {
            return series.map(function ([key]) { return Number(row[key] || 0); });
        });
        const max = Math.max(1, ...values);
        const x = function (index) { return trendData.length <= 1 ? 40 : 20 + index * 260 / (trendData.length - 1); };
        const y = function (value) { return 165 - value / max * 135; };

        let svg = '<svg viewBox="0 0 300 190" preserveAspectRatio="none" aria-label="Activity completion trend">';
        [0, 1, 2, 3].forEach(function (step) {
            const lineY = 30 + step * 40;
            svg += '<path d="M10 ' + lineY + 'H290" stroke="#e9eff5" stroke-width="1"/>';
        });

        series.forEach(function ([key, color]) {
            const points = trendData.map(function (row, index) {
                return x(index) + ',' + y(Number(row[key] || 0));
            }).join(' ');
            svg += '<polyline points="' + points + '" fill="none" stroke="' + color + '" stroke-width="2.5" stroke-linejoin="round"/>';
        });

        svg += '</svg>';
        host.innerHTML = svg;
    }

    function downloadReport() {
        const rows = [['Metric', 'Value']];
        document.querySelectorAll('.report-stat').forEach(function (card) {
            rows.push([card.querySelector('h2')?.textContent || '', card.querySelector('strong')?.textContent || '0']);
        });

        rows.push([]);
        rows.push(['Top Workers', 'Completed']);
        document.querySelectorAll('.worker-rank tr:not(:first-child)').forEach(function (row) {
            rows.push([row.children[1]?.innerText.trim() || '', row.children[2]?.innerText.trim() || '0']);
        });

        const csv = rows.map(function (row) {
            return row.map(function (cell) {
                return '"' + String(cell).replaceAll('"', '""') + '"';
            }).join(',');
        }).join('\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'field-activity-report.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    document.getElementById('download-report')?.addEventListener('click', downloadReport);
    renderTrend();
})();
