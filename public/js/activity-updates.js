(() => {
    const search = document.getElementById('update-search');
    const project = document.getElementById('update-project');
    const worker = document.getElementById('update-worker');
    const activityType = document.getElementById('update-activity-type');
    const status = document.getElementById('update-status');
    const date = document.getElementById('update-date');
    const rows = Array.from(document.querySelectorAll('#update-rows tr'));
    const count = document.getElementById('updates-count');
    const empty = document.getElementById('updates-empty');
    const detailPanel = document.getElementById('update-detail-panel');
    const selectAll = document.getElementById('updates-select-all');
    const updateGroups = window.activityUpdateGroups || {};

    function renderUpdates() {
        const term = (search?.value || '').toLowerCase().trim();
        let visible = 0;

        rows.forEach(function (row, index) {
            const matches =
                (!term || row.dataset.search.includes(term)) &&
                (!project || project.value === 'all' || row.dataset.project === project.value) &&
                (!worker || worker.value === 'all' || row.dataset.worker === worker.value) &&
                (!activityType || activityType.value === 'all' || row.dataset.activityType === activityType.value) &&
                (!status || status.value === 'all' || row.dataset.status === status.value) &&
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
            count.textContent = 'Showing ' + visible + ' of ' + rows.length + ' update groups';
        }

        if (empty) {
            empty.hidden = visible > 0;
        }

        updateSelectAllState();
    }

    function exportCsv() {
        const selected = rows.filter(function (row) {
            return !row.hidden && row.querySelector('.update-checkbox')?.checked;
        });
        const visible = selected.length ? selected : rows.filter(function (row) { return !row.hidden; });
        const output = [['Worker', 'Project', 'Work Type', 'Remark', 'Location', 'Time', 'Status']];

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

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || 'N/A';
        }
    }

    function rowToUpdate(row) {
        return {
            id: row.dataset.id,
            session_code: row.dataset.session,
            photo: row.dataset.photo,
            worker: row.dataset.workerName,
            worker_code: row.dataset.workerCode,
            project: row.dataset.projectName,
            activity_type: row.dataset.activityTypeName,
            remark: row.dataset.remark,
            location: row.dataset.location,
            gps: row.dataset.gps,
            latitude: row.dataset.latitude,
            longitude: row.dataset.longitude,
            accuracy: row.dataset.accuracy,
            time: row.dataset.time,
            status_label: row.dataset.statusLabel,
        };
    }

    function applyUpdateDetail(update) {
        setText('detail-update-worker', (update.worker || 'N/A') + (update.worker_code ? ' (' + update.worker_code + ')' : ''));
        setText('detail-update-session', update.session_code);
        setText('detail-update-project', update.project);
        setText('detail-update-type', update.activity_type);
        setText('detail-update-location', update.location);
        setText('detail-update-gps', (update.gps || 'N/A') + (update.accuracy ? ' | accuracy ' + update.accuracy + 'm' : ''));
        setText('detail-update-time', update.time);
        setText('detail-update-status', update.status_label);
        setText('detail-update-remark', update.remark || 'No remark added.');

        const mapLink = document.getElementById('detail-update-map');
        if (mapLink) {
            const hasGps = update.latitude && update.longitude;
            mapLink.hidden = !hasGps;
            mapLink.href = hasGps
                ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(update.latitude + ',' + update.longitude)
                : '#';
        }
    }

    function renderGallery(row) {
        const photo = document.getElementById('detail-update-photo');
        const group = updateGroups[row.dataset.groupId] || [rowToUpdate(row)];
        const photos = group.filter(function (item) { return item.photo; });
        const items = photos.length ? photos : group;

        if (!photo) {
            return;
        }

        photo.style.backgroundImage = '';
        photo.innerHTML = '';

        if (!items.length) {
            photo.innerHTML = '<div class="update-gallery-empty">No image found</div>';
            applyUpdateDetail(rowToUpdate(row));
            return;
        }

        const preview = document.createElement('div');
        preview.className = 'update-gallery-preview';
        preview.style.backgroundImage = "url('" + (items[0].photo || row.dataset.photo) + "')";

        const strip = document.createElement('div');
        strip.className = 'update-gallery-strip';

        items.forEach(function (item, index) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'update-gallery-thumb' + (index === 0 ? ' active' : '');
            button.style.backgroundImage = item.photo ? "url('" + item.photo + "')" : '';
            button.setAttribute('aria-label', 'View update image ' + (index + 1));
            button.addEventListener('click', function () {
                strip.querySelectorAll('.update-gallery-thumb').forEach(function (thumb) {
                    thumb.classList.remove('active');
                });
                button.classList.add('active');
                preview.style.backgroundImage = item.photo ? "url('" + item.photo + "')" : '';
                applyUpdateDetail(item);
            });
            strip.appendChild(button);
        });

        photo.appendChild(preview);
        photo.appendChild(strip);
        applyUpdateDetail(items[0]);
    }

    function showDetail(row) {
        rows.forEach(function (item) { item.classList.remove('selected'); });
        row.classList.add('selected');
        renderGallery(row);

        if (detailPanel) {
            detailPanel.hidden = false;
            document.body.classList.add('update-detail-open');
        }
    }

    function updateSelectAllState() {
        if (!selectAll) {
            return;
        }

        const visible = rows.filter(function (row) { return !row.hidden; });
        const checked = visible.filter(function (row) { return row.querySelector('.update-checkbox')?.checked; });
        selectAll.checked = visible.length > 0 && checked.length === visible.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < visible.length;
    }

    [search, project, worker, activityType, status, date].forEach(function (control) {
        control?.addEventListener('input', renderUpdates);
        control?.addEventListener('change', renderUpdates);
    });

    document.getElementById('update-reset')?.addEventListener('click', function () {
        if (search) search.value = '';
        if (project) project.value = 'all';
        if (worker) worker.value = 'all';
        if (activityType) activityType.value = 'all';
        if (status) status.value = 'all';
        if (date) date.value = '';
        renderUpdates();
    });

    document.getElementById('export-updates')?.addEventListener('click', exportCsv);
    document.getElementById('close-update-detail')?.addEventListener('click', function () {
        if (detailPanel) {
            detailPanel.hidden = true;
            document.body.classList.remove('update-detail-open');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && detailPanel && !detailPanel.hidden) {
            detailPanel.hidden = true;
            document.body.classList.remove('update-detail-open');
        }
    });

    selectAll?.addEventListener('change', function () {
        rows.forEach(function (row) {
            if (!row.hidden) {
                const checkbox = row.querySelector('.update-checkbox');
                if (checkbox) {
                    checkbox.checked = selectAll.checked;
                }
            }
        });
        updateSelectAllState();
    });

    rows.forEach(function (row) {
        row.querySelector('.view-update')?.addEventListener('click', function (event) {
            event.stopPropagation();
            showDetail(row);
        });

        row.addEventListener('click', function (event) {
            if (event.target.matches('input[type="checkbox"]')) {
                updateSelectAllState();
                return;
            }

            showDetail(row);
        });
    });

    renderUpdates();
})();
