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
const searchResults = document.getElementById('topbar-search-results');
const globalSearchItems = Array.isArray(window.globalSearchItems) ? window.globalSearchItems : [];

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[char];
    });
}

function renderGlobalSearch() {
    if (!search || !searchResults) {
        return;
    }

    const term = search.value.toLowerCase().trim();
    if (!term) {
        searchResults.hidden = true;
        searchResults.innerHTML = '';
        return;
    }

    const matches = globalSearchItems.filter(function (item) {
        return [item.label, item.group, item.meta].join(' ').toLowerCase().includes(term);
    }).slice(0, 8);

    searchResults.innerHTML = matches.length
        ? matches.map(function (item) {
            return '<a href="' + item.url + '"><span class="search-result-group">' + escapeHtml(item.group) + '</span><b>' + escapeHtml(item.label) + '</b><small>' + escapeHtml(item.meta || 'Open page') + '</small></a>';
        }).join('')
        : '<p class="dropdown-empty">No result found.</p>';
    searchResults.hidden = false;
}

if (search) {
    search.addEventListener('input', function () {
        renderGlobalSearch();
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

function bindDropdown(buttonId, menuId) {
    const button = document.getElementById(buttonId);
    const menu = document.getElementById(menuId);

    if (!button || !menu) {
        return;
    }

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        document.querySelectorAll('.topbar-dropdown').forEach(function (dropdown) {
            if (dropdown !== menu) {
                dropdown.hidden = true;
            }
        });
        menu.hidden = !menu.hidden;
        button.setAttribute('aria-expanded', String(!menu.hidden));
    });
}

bindDropdown('notification-toggle', 'notification-menu');
bindDropdown('profile-toggle', 'profile-menu');

document.querySelector('.profile[data-detail]')?.addEventListener('click', function (event) {
    event.stopImmediatePropagation();
    const menu = document.getElementById('profile-menu');
    if (menu) {
        menu.hidden = !menu.hidden;
    }
});

document.addEventListener('click', function (event) {
    if (!event.target.closest('.topbar-menu') && !event.target.closest('.profile')) {
        document.querySelectorAll('.topbar-dropdown').forEach(function (dropdown) {
            dropdown.hidden = true;
        });
    }

    if (!event.target.closest('.topbar-search-wrap') && searchResults) {
        searchResults.hidden = true;
    }
});

document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        search?.focus();
    }

    if (event.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
        document.querySelectorAll('.topbar-dropdown').forEach(function (dropdown) {
            dropdown.hidden = true;
        });
        if (searchResults) {
            searchResults.hidden = true;
        }
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
    const totalActivity = values.reduce(function (sum, value) {
        return sum + value;
    }, 0);

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

    if (totalActivity === 0) {
        markup += '<text x="350" y="88" text-anchor="middle" fill="#60758d" font-size="13">No assignment activity yet</text>';
        markup += '<text x="350" y="110" text-anchor="middle" fill="#8a9ab0" font-size="10">Create a real assignment to start tracking progress.</text>';
    }

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
        chartCaption.textContent = totalActivity === 0
            ? 'No real assignments found after removing demo workers.'
            : records.length
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
