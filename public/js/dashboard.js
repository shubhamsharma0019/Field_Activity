// Open and close the sidebar on desktop and mobile.
const menuButton = document.getElementById('menu-toggle');
const overlay = document.getElementById('sidebar-overlay');

function updateMenuState() {
    const isMobile = window.innerWidth <= 760;
    const isOpen = isMobile
        ? document.body.classList.contains('sidebar-open')
        : !document.body.classList.contains('sidebar-collapsed');
    menuButton.setAttribute('aria-expanded', String(isOpen));
    document.getElementById('sidebar').inert = !isOpen;
}

menuButton.addEventListener('click', function () {
    const className = window.innerWidth <= 760 ? 'sidebar-open' : 'sidebar-collapsed';
    document.body.classList.toggle(className);
    updateMenuState();
});

overlay.addEventListener('click', function () {
    document.body.classList.remove('sidebar-open');
    updateMenuState();
});
window.addEventListener('resize', updateMenuState);
updateMenuState();

// Filter the sample assignment table.
const search = document.getElementById('dashboard-search');
search.addEventListener('input', function () {
    const rows = document.querySelectorAll('#assignment-rows tr');
    let visibleRows = 0;
    rows.forEach(function (row) {
        row.hidden = !row.textContent.toLowerCase().includes(search.value.toLowerCase().trim());
        if (!row.hidden) {
            visibleRows++;
        }
    });
    const emptyMessage = document.getElementById('search-empty');
    if (emptyMessage) {
        emptyMessage.hidden = visibleRows > 0;
    }
});

document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        search.focus();
    }
    if (event.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
        updateMenuState();
    }
});

// Show details without navigating to pages that have not been built yet.
const dialog = document.getElementById('detail-dialog');
document.querySelectorAll('[data-detail], [data-preview]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('dialog-title').textContent = button.dataset.detail || button.dataset.preview;
        document.getElementById('dialog-text').textContent = button.dataset.description || 'This section is not connected yet. You are viewing the dashboard design preview.';
        dialog.showModal();
    });
});
document.getElementById('close-dialog').addEventListener('click', function () {
    dialog.close();
});

// Sample daily data. Replace these records with backend data when connected.
const activityRecords = [
    [14, 8, 4], [18, 11, 5], [12, 7, 6], [22, 10, 8], [19, 14, 7],
    [25, 12, 5], [17, 9, 4], [20, 13, 6], [24, 16, 9], [16, 12, 5],
    [28, 18, 7], [21, 14, 8], [26, 11, 4], [30, 16, 6], [23, 13, 9],
    [18, 10, 5], [27, 17, 8], [32, 19, 6], [25, 15, 10], [29, 18, 7],
    [20, 12, 5], [31, 20, 9], [26, 14, 8], [12, 7, 6], [25, 16, 8],
    [12, 11, 10], [25, 12, 10], [36, 18, 8], [24, 18, 3], [23, 17, 14],
];

const chartSeries = [
    { label: 'Completed', color: '#237cf4', className: 'blue' },
    { label: 'In Progress', color: '#18b980', className: 'green' },
    { label: 'Pending', color: '#ff8c13', className: 'orange' },
];

function renderDashboardCharts(days) {
    const chart = document.getElementById('activity-chart');
    const records = activityRecords.slice(-days);
    const maxValue = Math.max(10, Math.ceil(Math.max(...records.flat()) / 10) * 10);
    const totals = [0, 0, 0];
    const labels = records.map(function (record, index) {
        const date = new Date(2026, 8, 28);
        date.setDate(date.getDate() - records.length + 1 + index);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
    });
    const x = function (index) { return 45 + index * 630 / (records.length - 1); };
    const y = function (value) { return 140 - value / maxValue * 120; };
    let markup = '<title>Activity counts for the selected ' + days + ' days</title>';

    // Grid lines and labels follow the highest value in the selected data.
    for (let step = 0; step <= 4; step++) {
        const value = maxValue * step / 4;
        markup += '<path class="chart-grid" d="M45 ' + y(value) + 'H675"/>';
        markup += '<text x="12" y="' + (y(value) + 4) + '" class="chart-labels">' + value + '</text>';
    }
    records.forEach(function (record, index) {
        if (index % Math.ceil(records.length / 7) === 0 || index === records.length - 1) {
            markup += '<path class="chart-grid" d="M' + x(index) + ' 20V140"/>';
            markup += '<text text-anchor="middle" x="' + x(index) + '" y="164" class="chart-labels">' + labels[index] + '</text>';
        }
        record.forEach(function (value, seriesIndex) { totals[seriesIndex] += value; });
    });

    chartSeries.forEach(function (series, seriesIndex) {
        const points = records.map(function (record, index) { return x(index) + ',' + y(record[seriesIndex]); });
        if (seriesIndex === 0) {
            markup += '<polygon points="45,140 ' + points.join(' ') + ' 675,140" fill="' + series.color + '" opacity=".07"/>';
        }
        markup += '<polyline points="' + points.join(' ') + '" fill="none" stroke="' + series.color + '" stroke-width="2.2" stroke-linejoin="round"/>';
        records.forEach(function (record, index) {
            const description = labels[index] + ' · ' + series.label + ': ' + record[seriesIndex];
            markup += '<circle class="chart-point" tabindex="0" aria-label="' + description + '" cx="' + x(index) + '" cy="' + y(record[seriesIndex]) + '" r="3.5" fill="' + series.color + '"><title>' + description + '</title></circle>';
        });
    });
    chart.innerHTML = markup;
    document.getElementById('chart-caption').textContent = 'Sample data · ' + labels[0] + ' – ' + labels[labels.length - 1];
    document.getElementById('status-period').textContent = 'Last ' + days + ' Days';

    const total = totals.reduce(function (sum, value) { return sum + value; }, 0);
    let position = 0;
    const segments = [];
    let legend = '';
    chartSeries.forEach(function (series, index) {
        const percentage = total ? totals[index] / total * 100 : 0;
        segments.push(series.color + ' ' + position + '% ' + (position + percentage) + '%');
        position += percentage;
        legend += '<p><i class="dot ' + series.className + '"></i><span>' + series.label + '</span><strong>' + totals[index] + '</strong><small>' + percentage.toFixed(1) + '%</small></p>';
    });
    const donut = document.getElementById('assignment-donut');
    donut.style.background = total ? 'conic-gradient(' + segments.join(',') + ')' : '#e9eef5';
    donut.setAttribute('aria-label', total + ' assignments. ' + chartSeries.map(function (series, index) { return series.label + ': ' + totals[index]; }).join(', '));
    document.getElementById('assignment-total').textContent = total;
    document.getElementById('status-legend').innerHTML = legend;
}

const chartPeriod = document.getElementById('chart-period');
if (chartPeriod) {
    chartPeriod.addEventListener('change', function () {
        renderDashboardCharts(Number(chartPeriod.value));
    });
    renderDashboardCharts(Number(chartPeriod.value));
}
