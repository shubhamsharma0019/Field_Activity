(() => {
    const rows = Array.isArray(window.reportRows) ? window.reportRows : [];
    const filters = {
        from: document.getElementById('report-from'),
        to: document.getElementById('report-to'),
        company: document.getElementById('report-company'),
        project: document.getElementById('report-project'),
        worker: document.getElementById('report-worker'),
    };
    const series = [
        ['completed', '#18b979'],
        ['in_progress', '#1e70ef'],
        ['pending', '#ff8c18'],
        ['rejected', '#ef374d'],
    ];

    let filteredRows = [];

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    const percent = (part, total) => total > 0 ? Math.round(part / total * 100) : 0;

    function rowDate(row) {
        return row.date ? new Date(row.date + 'T00:00:00') : null;
    }

    function isInRange(row) {
        const date = rowDate(row);
        if (!date) return true;

        if (filters.from?.value && date < new Date(filters.from.value + 'T00:00:00')) return false;
        if (filters.to?.value && date > new Date(filters.to.value + 'T23:59:59')) return false;

        return true;
    }

    function applyFilters() {
        const company = filters.company?.value || 'all';
        const project = filters.project?.value || 'all';
        const worker = filters.worker?.value || 'all';

        filteredRows = rows.filter((row) => {
            if (!isInRange(row)) return false;
            if (company !== 'all' && String(row.company_id || '') !== company) return false;
            if (project !== 'all' && String(row.project_id || '') !== project) return false;
            if (worker !== 'all' && String(row.worker_id || '') !== worker) return false;

            return true;
        });

        render();
    }

    function updateProjectOptions() {
        const company = filters.company?.value || 'all';
        filters.project?.querySelectorAll('option').forEach((option) => {
            if (option.value === 'all') {
                option.hidden = false;
                return;
            }

            option.hidden = company !== 'all' && option.dataset.company !== company;
        });

        const selected = filters.project?.selectedOptions?.[0];
        if (selected?.hidden && filters.project) {
            filters.project.value = 'all';
        }
    }

    function setText(selector, value) {
        const element = document.querySelector(selector);
        if (element) element.textContent = value;
    }

    function renderStats() {
        const total = filteredRows.length;
        const counts = {
            completed: filteredRows.filter((row) => row.status_group === 'completed').length,
            in_progress: filteredRows.filter((row) => row.status_group === 'in_progress').length,
            pending: filteredRows.filter((row) => row.status_group === 'pending').length,
            rejected: filteredRows.filter((row) => row.status_group === 'rejected').length,
        };
        const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        const monthTotal = filteredRows.filter((row) => {
            const date = rowDate(row);
            return date && date >= monthStart;
        }).length;

        setText('[data-stat="total"]', total);
        setText('[data-stat="completed"]', counts.completed);
        setText('[data-stat="in_progress"]', counts.in_progress);
        setText('[data-stat="pending"]', counts.pending);
        setText('[data-stat="rejected"]', counts.rejected);
        setText('[data-stat-note="month_total"]', `${monthTotal} this month`);
        setText('[data-stat-note="completed_percent"]', `${percent(counts.completed, total)}%`);
        setText('[data-stat-note="in_progress_percent"]', `${percent(counts.in_progress, total)}%`);
        setText('[data-stat-note="pending_percent"]', `${percent(counts.pending, total)}%`);
        setText('[data-stat-note="rejected_percent"]', `${percent(counts.rejected, total)}%`);
    }

    function trendData() {
        const end = filters.to?.value ? new Date(filters.to.value + 'T00:00:00') : new Date();
        const days = [];

        for (let index = 6; index >= 0; index -= 1) {
            const date = new Date(end);
            date.setDate(end.getDate() - index);
            const key = date.toISOString().slice(0, 10);
            const dayRows = filteredRows.filter((row) => row.date === key);

            days.push({
                label: date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }),
                completed: dayRows.filter((row) => row.status_group === 'completed').length,
                in_progress: dayRows.filter((row) => row.status_group === 'in_progress').length,
                pending: dayRows.filter((row) => row.status_group === 'pending').length,
                rejected: dayRows.filter((row) => row.status_group === 'rejected').length,
            });
        }

        return days;
    }

    function renderTrend() {
        const host = document.getElementById('trend-chart');
        if (!host) return;

        const data = trendData();
        const values = data.flatMap((row) => series.map(([key]) => Number(row[key] || 0)));
        const max = Math.max(1, ...values);
        const x = (index) => data.length <= 1 ? 42 : 26 + index * 248 / (data.length - 1);
        const y = (value) => 150 - value / max * 118;

        let svg = '<svg viewBox="0 0 300 190" preserveAspectRatio="none" aria-label="Activity completion trend">';
        [0, 1, 2, 3].forEach((step) => {
            const lineY = 32 + step * 36;
            svg += `<path d="M18 ${lineY}H286" stroke="#e4ebf4" stroke-width="1"/>`;
        });

        series.forEach(([key, color]) => {
            const points = data.map((row, index) => `${x(index)},${y(Number(row[key] || 0))}`).join(' ');
            svg += `<polyline points="${points}" fill="none" stroke="${color}" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>`;
        });

        data.forEach((row, index) => {
            svg += `<text x="${x(index)}" y="180" text-anchor="middle" fill="#526780" font-size="8">${escapeHtml(row.label)}</text>`;
        });

        host.innerHTML = svg + '</svg>';
    }

    function groupedBy(key) {
        return filteredRows.reduce((groups, row) => {
            const name = row[key] || 'Unknown';
            groups[name] = groups[name] || [];
            groups[name].push(row);
            return groups;
        }, {});
    }

    function topGroups(key, limit = 6) {
        return Object.entries(groupedBy(key))
            .map(([name, items]) => ({ name, items, count: items.length }))
            .sort((a, b) => b.count - a.count)
            .slice(0, limit);
    }

    function renderDistribution() {
        const host = document.getElementById('activity-type-list');
        const donut = document.getElementById('activity-donut');
        const total = filteredRows.length;
        const groups = topGroups('activity_type', 6);
        const colors = ['#28bce7', '#38c89c', '#ffbb25', '#5b86ef', '#9237ea', '#c371eb'];
        let offset = 0;
        const stops = groups.map((group, index) => {
            const start = offset;
            const size = total > 0 ? group.count / total * 100 : 0;
            offset += size;
            return `${colors[index % colors.length]} ${start}% ${offset}%`;
        });

        if (donut) {
            donut.style.background = stops.length ? `conic-gradient(${stops.join(', ')})` : '#e8eef6';
        }

        setText('#donut-total', total);

        if (!host) return;
        host.innerHTML = groups.length ? groups.map((group, index) => (
            `<span><b><i style="background:${colors[index % colors.length]}"></i>${escapeHtml(group.name)}</b>${group.count} (${percent(group.count, total)}%)</span>`
        )).join('') : '<p class="empty-state">No activity data found.</p>';
    }

    function renderTopWorkers() {
        const host = document.getElementById('top-workers-body');
        if (!host) return;

        const workerMap = {};
        filteredRows.forEach((row) => {
            const key = row.worker_id || row.worker || 'unknown';
            workerMap[key] = workerMap[key] || {
                name: row.worker || 'Worker',
                code: row.worker_code || '-',
                completed: 0,
                total: 0,
            };
            workerMap[key].total += 1;
            if (row.status_group === 'completed') workerMap[key].completed += 1;
        });

        const workers = Object.values(workerMap).sort((a, b) => b.completed - a.completed || b.total - a.total).slice(0, 5);
        host.innerHTML = workers.length ? workers.map((worker, index) => (
            `<tr><td>${index + 1}</td><td>${escapeHtml(worker.name)}<br><small>${escapeHtml(worker.code)}</small></td><td>${worker.completed}</td></tr>`
        )).join('') : '<tr><td colspan="3">No worker activity found.</td></tr>';
    }

    function renderProjectPerformance() {
        const host = document.getElementById('project-performance');
        if (!host) return;

        const groups = topGroups('project', 8);
        host.innerHTML = groups.length ? groups.map((group) => {
            const completed = group.items.filter((row) => row.status_group === 'completed').length;
            const width = Math.max(4, percent(completed, group.count));
            return `<div class="performance-row"><span>${escapeHtml(group.name)}</span><div class="performance-bar"><span style="width:${width}%"></span></div><b>${completed}/${group.count}</b></div>`;
        }).join('') : '<p class="empty-state">No project performance found.</p>';
    }

    function renderLocations() {
        const host = document.getElementById('location-bars');
        if (!host) return;

        const groups = topGroups('location', 7);
        const max = Math.max(1, ...groups.map((group) => group.count));
        host.innerHTML = groups.length ? groups.map((group) => (
            `<div>${group.count}<i style="--height:${Math.max(8, Math.round(group.count / max * 100))}%"></i><small>${escapeHtml(group.name)}</small></div>`
        )).join('') : '<div>0<i style="--height:8%"></i><small>No Data</small></div>';

    }

    function renderRecent() {
        const host = document.getElementById('recent-activity-body');
        if (!host) return;

        const recent = [...filteredRows].sort((a, b) => String(b.timestamp || '').localeCompare(String(a.timestamp || ''))).slice(0, 12);
        host.innerHTML = recent.length ? recent.map((row, index) => (
            `<tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(row.type)}</td>
                <td>${escapeHtml(row.title)}</td>
                <td>${escapeHtml(row.worker)}<br><small>${escapeHtml(row.worker_code)}</small></td>
                <td>${escapeHtml(row.project)}</td>
                <td>${escapeHtml(row.location)}</td>
                <td>${escapeHtml(row.timestamp ? new Date(row.timestamp.replace(' ', 'T')).toLocaleString('en-IN') : '-')}</td>
                <td><span class="status-${escapeHtml(row.status_group)}">${escapeHtml(row.status_label)}</span></td>
            </tr>`
        )).join('') : '<tr><td colspan="8" class="empty-state">No report data found for selected filters.</td></tr>';
    }

    function render() {
        renderStats();
        renderTrend();
        renderDistribution();
        renderTopWorkers();
        renderProjectPerformance();
        renderLocations();
        renderRecent();
    }

    function downloadReport() {
        const header = ['Type', 'Activity', 'Worker', 'Code', 'Company', 'Project', 'Location', 'Date', 'Status', 'Description'];
        const csvRows = [header, ...filteredRows.map((row) => [
            row.type,
            row.title,
            row.worker,
            row.worker_code,
            row.company,
            row.project,
            row.location,
            row.timestamp,
            row.status_label,
            row.description,
        ])];
        const csv = csvRows.map((row) => row.map((cell) => `"${String(cell ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');

        link.href = URL.createObjectURL(blob);
        link.download = 'field-activity-report.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    Object.values(filters).forEach((filter) => {
        filter?.addEventListener('change', () => {
            updateProjectOptions();
            applyFilters();
        });
    });
    document.getElementById('download-report')?.addEventListener('click', downloadReport);

    updateProjectOptions();
    applyFilters();
})();
