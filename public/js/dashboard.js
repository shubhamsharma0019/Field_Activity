const menuButton = document.getElementById('menu-toggle');
const overlay = document.getElementById('sidebar-overlay');

document.body.classList.remove('sidebar-collapsed');

function updateMenuState() {
    if (!menuButton || !overlay) {
        return;
    }

    const isMobile = window.innerWidth <= 760;
    const isOpen = isMobile
        ? document.body.classList.contains('sidebar-open')
        : !document.body.classList.contains('sidebar-collapsed');

    menuButton.setAttribute('aria-expanded', String(isOpen));

    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.inert = !isOpen;
    }
}

if (menuButton) {
    menuButton.addEventListener('click', function () {
        const className = window.innerWidth <= 760 ? 'sidebar-open' : 'sidebar-collapsed';
        document.body.classList.toggle(className);
        updateMenuState();
    });
}

if (overlay) {
    overlay.addEventListener('click', function () {
        document.body.classList.remove('sidebar-open');
        updateMenuState();
    });
}

window.addEventListener('resize', updateMenuState);
updateMenuState();

const search = document.getElementById('dashboard-search');
if (search) {
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
}

document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        search?.focus();
    }

    if (event.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
        updateMenuState();
    }
});

const dialog = document.getElementById('detail-dialog');
if (dialog) {
    document.querySelectorAll('[data-detail], [data-preview]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('dialog-title').textContent = button.dataset.detail || button.dataset.preview;
            document.getElementById('dialog-text').textContent = button.dataset.description || 'No extra details available.';
            dialog.showModal();
        });
    });

    document.getElementById('close-dialog')?.addEventListener('click', function () {
        dialog.close();
    });
}

const activityRecords = Array.isArray(window.dashboardChartData)
    ? window.dashboardChartData
    : [];

const statusSummary = window.dashboardStatusSummary || {
    completed: 0,
    in_progress: 0,
    pending: 0,
};

const chartSeries = [
    { key: 'completed', label: 'Completed', color: '#237cf4', className: 'blue' },
    { key: 'in_progress', label: 'In Progress', color: '#18b980', className: 'green' },
    { key: 'pending', label: 'Pending', color: '#ff8c13', className: 'orange' },
];

function renderDashboardCharts(days) {
    const chart = document.getElementById('activity-chart');
    if (!chart) {
        return;
    }

    const records = activityRecords.slice(-days);
    const values = records.flatMap(function (record) {
        return chartSeries.map(function (series) {
            return Number(record[series.key] || 0);
        });
    });

    const maxValue = Math.max(10, Math.ceil(Math.max(...values, 0) / 10) * 10);
    const labels = records.map(function (record) { return record.label; });
    const x = function (index) {
        return records.length <= 1 ? 360 : 45 + index * 630 / (records.length - 1);
    };
    const y = function (value) { return 140 - value / maxValue * 120; };

    let markup = '<title>Activity counts for the selected ' + days + ' days</title>';

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
    });

    chartSeries.forEach(function (series, seriesIndex) {
        const points = records.map(function (record, index) {
            return x(index) + ',' + y(Number(record[series.key] || 0));
        });

        if (seriesIndex === 0 && points.length > 0) {
            markup += '<polygon points="45,140 ' + points.join(' ') + ' 675,140" fill="' + series.color + '" opacity=".07"/>';
        }

        if (points.length > 0) {
            markup += '<polyline points="' + points.join(' ') + '" fill="none" stroke="' + series.color + '" stroke-width="2.2" stroke-linejoin="round"/>';
        }

        records.forEach(function (record, index) {
            const value = Number(record[series.key] || 0);
            const description = labels[index] + ' - ' + series.label + ': ' + value;
            markup += '<circle class="chart-point" tabindex="0" aria-label="' + description + '" cx="' + x(index) + '" cy="' + y(value) + '" r="3.5" fill="' + series.color + '"><title>' + description + '</title></circle>';
        });
    });

    chart.innerHTML = markup;

    const chartCaption = document.getElementById('chart-caption');
    if (chartCaption) {
        chartCaption.textContent = records.length
            ? 'Live data - ' + labels[0] + ' to ' + labels[labels.length - 1]
            : 'No assignment activity found for this period';
    }

    const statusPeriod = document.getElementById('status-period');
    if (statusPeriod) {
        statusPeriod.textContent = 'All Time';
    }

    const totals = chartSeries.map(function (series) {
        return Number(statusSummary[series.key] || 0);
    });
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
    if (donut) {
        donut.style.background = total ? 'conic-gradient(' + segments.join(',') + ')' : '#e9eef5';
        donut.setAttribute('aria-label', total + ' assignments. ' + chartSeries.map(function (series, index) {
            return series.label + ': ' + totals[index];
        }).join(', '));
    }

    const assignmentTotal = document.getElementById('assignment-total');
    if (assignmentTotal) {
        assignmentTotal.textContent = total;
    }

    const statusLegend = document.getElementById('status-legend');
    if (statusLegend) {
        statusLegend.innerHTML = legend;
    }
}

const chartPeriod = document.getElementById('chart-period');
if (chartPeriod) {
    chartPeriod.addEventListener('change', function () {
        renderDashboardCharts(Number(chartPeriod.value));
    });
    renderDashboardCharts(Number(chartPeriod.value));
}
