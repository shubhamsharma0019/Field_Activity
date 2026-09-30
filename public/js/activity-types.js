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
    const createProjectWork = document.getElementById('create-project-work');
    const projectWorkFields = document.getElementById('project-work-fields');
    const projectSelect = document.getElementById('project-work-project');
    const projectWorkName = document.querySelector('[name="project_work_name"]');
    const startInput = document.querySelector('[name="start_date"]');
    const endInput = document.querySelector('[name="end_date"]');
    const projectDateNote = document.querySelector('.project-date-note');

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
            count.textContent = visibleRows + ' of ' + rows.length + ' work types';
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
            document.body.classList.add('activity-modal-open');
            document.getElementById('activity-name')?.focus();
        }
    }

    function hideForm() {
        if (formPanel) {
            formPanel.hidden = true;
            document.body.classList.remove('activity-modal-open');
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

    function syncProjectWorkFields() {
        const enabled = Boolean(createProjectWork?.checked);

        if (projectWorkFields) {
            projectWorkFields.hidden = !enabled;
        }

        if (projectSelect) {
            projectSelect.required = enabled;
        }

        if (projectWorkName) {
            projectWorkName.required = enabled;
        }
    }

    function syncProjectDates() {
        const selected = projectSelect?.selectedOptions?.[0];
        if (!selected || !selected.value) {
            return;
        }

        const projectStart = selected.dataset.startDate || '';
        const projectEnd = selected.dataset.endDate || '';

        if (startInput) {
            startInput.min = projectStart;
            startInput.max = projectEnd;
        }

        if (endInput) {
            endInput.min = startInput?.value || projectStart;
            endInput.max = projectEnd;
        }

        if (projectDateNote) {
            projectDateNote.textContent = projectStart && projectEnd ? 'Project range: ' + projectStart + ' to ' + projectEnd : '';
        }
    }

    search?.addEventListener('input', renderActivities);
    status?.addEventListener('change', renderActivities);
    description?.addEventListener('input', updateDescriptionCount);
    openForm?.addEventListener('click', showForm);
    closeForm?.addEventListener('click', hideForm);
    cancelForm?.addEventListener('click', hideForm);
    createProjectWork?.addEventListener('change', syncProjectWorkFields);
    projectSelect?.addEventListener('change', syncProjectDates);
    startInput?.addEventListener('change', function () {
        if (endInput && endInput.value && startInput.value && endInput.value < startInput.value) {
            endInput.value = startInput.value;
        }
        syncProjectDates();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && formPanel && !formPanel.hidden) {
            hideForm();
        }
    });

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
    syncProjectWorkFields();
    syncProjectDates();
    renderActivities();
})();
