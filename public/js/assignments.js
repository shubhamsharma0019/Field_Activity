(() => {
    const form = document.getElementById('assignment-form');
    const description = document.getElementById('assignment-description');
    const search = document.getElementById('assignment-search');
    const company = document.getElementById('assignment-company');
    const project = document.getElementById('assignment-project');
    const activityType = document.getElementById('assignment-activity-type');
    const status = document.getElementById('assignment-status');
    const rows = Array.from(document.querySelectorAll('#assignment-rows tr'));
    const empty = document.getElementById('assignments-empty');
    const count = document.getElementById('assignment-count');
    const formCompany = document.getElementById('form-company');
    const formProject = document.getElementById('form-project');
    const formActivity = document.getElementById('form-project-activity');
    const formMode = document.getElementById('form-activity-mode');
    const formTarget = document.getElementById('form-target');
    const formTracking = document.getElementById('form-tracking');
    const formWorker = document.getElementById('form-worker');
    const statusToast = document.getElementById('assignment-status-toast');

    function renderAssignments() {
        const query = (search?.value || '').toLowerCase().trim();
        let visibleRows = 0;

        rows.forEach(function (row, index) {
            const visible =
                (!query || row.dataset.search.includes(query)) &&
                (!company || company.value === 'all' || row.dataset.company === company.value) &&
                (!project || project.value === 'all' || row.dataset.project === project.value) &&
                (!activityType || activityType.value === 'all' || row.dataset.activityType === activityType.value) &&
                (!status || status.value === 'all' || row.dataset.status === status.value);

            row.hidden = !visible;

            if (visible) {
                visibleRows++;
                row.children[0].textContent = visibleRows;
            } else {
                row.children[0].textContent = index + 1;
            }
        });

        if (empty) {
            empty.hidden = visibleRows > 0;
        }

        if (count) {
            count.textContent = visibleRows + ' of ' + rows.length + ' assignments';
        }
    }

    function updateDescriptionCount() {
        const counter = document.getElementById('assignment-description-count');
        if (description && counter) {
            counter.textContent = description.value.length + '/500';
        }
    }

    function filterFormProjects() {
        if (!formCompany || !formProject) {
            return;
        }

        let selectedProjectVisible = false;

        Array.from(formProject.options).forEach(function (option) {
            if (!option.value) {
                return;
            }

            option.hidden = formCompany.value !== 'all' && option.dataset.company !== formCompany.value;

            if (!option.hidden && option.value === formProject.value) {
                selectedProjectVisible = true;
            }
        });

        if (!selectedProjectVisible) {
            formProject.value = '';
        }

        filterFormActivities();
    }

    function filterFormActivities() {
        if (!formProject || !formActivity) {
            return;
        }

        Array.from(formActivity.options).forEach(function (option) {
            if (!option.value) {
                return;
            }

            option.hidden = option.dataset.project !== formProject.value;
        });

        formActivity.value = '';
        updateActivityFields();
        filterFormWorkers();
    }

    function filterFormWorkers() {
        if (!formProject || !formWorker) {
            return;
        }

        const projectOption = formProject.selectedOptions[0];
        const projectCompany = projectOption?.dataset.company || '';
        let selectedWorkerVisible = false;
        let visibleWorkers = 0;

        Array.from(formWorker.options).forEach(function (option) {
            if (!option.value && !option.dataset.emptyMessage) {
                return;
            }

            if (option.dataset.emptyMessage) {
                option.hidden = true;
                return;
            }

            option.hidden = Boolean(projectCompany) && projectCompany !== 'internal' && option.dataset.company !== projectCompany;

            if (!option.hidden && option.value === formWorker.value) {
                selectedWorkerVisible = true;
            }

            if (!option.hidden) {
                visibleWorkers++;
            }
        });

        if (!selectedWorkerVisible) {
            formWorker.value = '';
        }

        const emptyOption = formWorker.querySelector('[data-empty-message]');
        if (emptyOption) {
            emptyOption.hidden = visibleWorkers > 0;
        }
    }

    function modeLabel(mode) {
        if (mode === 'start_end') return 'Start-End';
        if (mode === 'continuous_tracking') return 'Continuous Tracking';
        return 'Single Submission';
    }

    function updateActivityFields() {
        const option = formActivity?.selectedOptions[0];
        const selectedMode = option?.dataset.mode || '';

        if (formMode) {
            formMode.value = selectedMode ? modeLabel(selectedMode) : 'Select project work';
        }

        if (formTarget) {
            formTarget.placeholder = option?.dataset.target ? 'Default: ' + option.dataset.target : 'Use project work target';
        }

        if (formTracking) {
            formTracking.checked = option?.dataset.tracking === '1' || selectedMode === 'continuous_tracking';
        }
    }

    [search, company, project, activityType, status].forEach(function (control) {
        control?.addEventListener('input', renderAssignments);
        control?.addEventListener('change', renderAssignments);
    });

    document.getElementById('assignment-reset')?.addEventListener('click', function () {
        if (search) search.value = '';
        if (company) company.value = 'all';
        if (project) project.value = 'all';
        if (activityType) activityType.value = 'all';
        if (status) status.value = 'all';
        renderAssignments();
    });

    description?.addEventListener('input', updateDescriptionCount);

    document.getElementById('assignment-form-button')?.addEventListener('click', function () {
        if (form) {
            form.hidden = false;
            document.body.classList.add('assignment-modal-open');
            setTimeout(function () {
                document.getElementById('form-project')?.focus();
            }, 50);
        }
    });

    document.getElementById('close-assignment-form')?.addEventListener('click', function () {
        if (form) {
            form.hidden = true;
            document.body.classList.remove('assignment-modal-open');
        }
    });

    document.getElementById('cancel-assignment')?.addEventListener('click', function () {
        if (form) {
            form.hidden = true;
            document.body.classList.remove('assignment-modal-open');
        }
    });

    form?.addEventListener('click', function (event) {
        if (event.target === form) {
            form.hidden = true;
            document.body.classList.remove('assignment-modal-open');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && form && !form.hidden) {
            form.hidden = true;
            document.body.classList.remove('assignment-modal-open');
        }
    });

    formCompany?.addEventListener('change', filterFormProjects);
    formProject?.addEventListener('change', filterFormActivities);
    formActivity?.addEventListener('change', updateActivityFields);

    updateDescriptionCount();
    filterFormProjects();
    renderAssignments();

    if (statusToast) {
        setTimeout(function () {
            statusToast.hidden = true;
        }, 3500);
    }
})();
