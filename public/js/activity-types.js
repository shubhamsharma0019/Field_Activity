(() => {
    const search = document.getElementById('activity-search');
    const status = document.getElementById('activity-status');
    const rows = Array.from(document.querySelectorAll('#activity-rows tr'));
    const empty = document.getElementById('activities-empty');
    const count = document.getElementById('activity-count');
    const formPanel = document.getElementById('activity-form-panel');
    const openForm = document.getElementById('open-activity-form');
    const closeForm = document.getElementById('close-activity-form');
    const cancelForm = document.getElementById('cancel-activity');
    const description = document.getElementById('activity-description');
    const descriptionCount = document.getElementById('activity-description-count');
    const trackingRequired = document.getElementById('tracking-required');

    function renderActivities() {
        const query = (search?.value || '').toLowerCase().trim();
        const selectedStatus = status?.value || 'all';
        let visibleRows = 0;

        rows.forEach(function (row, index) {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const matchesStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
            const isVisible = matchesSearch && matchesStatus;

            row.hidden = !isVisible;

            if (isVisible) {
                visibleRows++;
                row.querySelector('td').textContent = visibleRows;
            } else {
                row.querySelector('td').textContent = index + 1;
            }
        });

        if (empty) {
            empty.hidden = visibleRows > 0;
        }

        if (count) {
            count.textContent = visibleRows + ' of ' + rows.length + ' activity types';
        }
    }

    function updateDescriptionCount() {
        if (!description || !descriptionCount) {
            return;
        }

        descriptionCount.textContent = description.value.length + '/500';
    }

    function showForm() {
        if (formPanel) {
            formPanel.hidden = false;
            document.getElementById('activity-name')?.focus();
        }
    }

    function hideForm() {
        if (formPanel) {
            formPanel.hidden = true;
        }
    }

    function syncTrackingForMode() {
        const mode = document.querySelector('input[name="activity_mode"]:checked')?.value;

        if (!trackingRequired) {
            return;
        }

        if (mode === 'continuous_tracking') {
            trackingRequired.checked = true;
            trackingRequired.disabled = true;
            return;
        }

        trackingRequired.disabled = false;
    }

    search?.addEventListener('input', renderActivities);
    status?.addEventListener('change', renderActivities);
    description?.addEventListener('input', updateDescriptionCount);
    openForm?.addEventListener('click', showForm);
    closeForm?.addEventListener('click', hideForm);
    cancelForm?.addEventListener('click', hideForm);

    document.querySelectorAll('input[name="activity_mode"]').forEach(function (radio) {
        radio.addEventListener('change', syncTrackingForMode);
    });

    document.getElementById('activity-form')?.addEventListener('submit', function () {
        if (trackingRequired) {
            trackingRequired.disabled = false;
        }
    });

    updateDescriptionCount();
    syncTrackingForMode();
    renderActivities();
})();
