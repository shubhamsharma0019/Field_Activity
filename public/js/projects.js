(() => {
    const projects = Array.isArray(window.projectsData) ? window.projectsData : [];
    let currentPage = 1;

    const searchInput = document.getElementById('project-search');
    const companySelect = document.getElementById('project-company');
    const statusSelect = document.getElementById('project-status');
    const dateInput = document.getElementById('project-date');
    const pageSizeSelect = document.getElementById('project-page-size');
    const rows = document.getElementById('project-rows');

    function escapeText(value) {
        const element = document.createElement('span');
        element.textContent = value === null || value === undefined || value === '' ? '-' : String(value);
        return element.innerHTML;
    }

    function getFilteredProjects() {
        const searchText = searchInput.value.toLowerCase().trim();

        return projects.filter(function (project) {
            const projectText = [
                project.name,
                project.project_code,
                project.project_type_label,
                project.company,
                project.location,
                project.status_label,
            ].join(' ').toLowerCase();

            const matchesSearch = projectText.includes(searchText);
            const matchesCompany = companySelect.value === 'all'
                || (companySelect.value === 'internal' && project.project_type === 'internal')
                || String(project.company_id || '') === companySelect.value;
            const matchesStatus = statusSelect.value === 'all' || project.status === statusSelect.value;
            const matchesDate = !dateInput.value || project.start_date === dateInput.value;

            return matchesSearch && matchesCompany && matchesStatus && matchesDate;
        });
    }

    function getStatusClass(status) {
        if (status === 'on_hold') return 'hold';
        if (status === 'completed') return 'completed';
        if (status === 'cancelled') return 'hold';
        return 'active';
    }

    function getProgressClass(progress) {
        if (progress >= 75) return 'high';
        if (progress <= 30) return 'low';
        return '';
    }

    function renderProjects() {
        const filteredProjects = getFilteredProjects();
        const pageSize = Number(pageSizeSelect.value);
        const totalPages = Math.max(1, Math.ceil(filteredProjects.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);

        const startIndex = (currentPage - 1) * pageSize;
        const projectsForPage = filteredProjects.slice(startIndex, startIndex + pageSize);

        rows.innerHTML = projectsForPage.map(function (project, index) {
            return '<tr>'
                + '<td data-label="Select"><input type="checkbox" aria-label="Select ' + escapeText(project.name) + '"></td>'
                + '<td data-label="Number">' + (startIndex + index + 1) + '</td>'
                + '<td data-label="Project"><span class="project-name"><img class="project-thumb" src="/images/admin-construction.jpg" alt=""><span>' + escapeText(project.name) + '<small>' + escapeText(project.project_code) + '</small></span></span></td>'
                + '<td data-label="Type">' + escapeText(project.project_type_label) + '</td>'
                + '<td data-label="Company">' + escapeText(project.company) + '</td>'
                + '<td data-label="Location">' + escapeText(project.location) + '</td>'
                + '<td data-label="Start Date">' + escapeText(project.start_date_label) + '</td>'
                + '<td data-label="End Date">' + escapeText(project.end_date_label) + '</td>'
                + '<td data-label="Status"><span class="project-status ' + getStatusClass(project.status) + '">' + escapeText(project.status_label) + '</span></td>'
                + '<td data-label="Progress"><span class="progress-wrap"><span class="progress-bar ' + getProgressClass(Number(project.progress || 0)) + '"><span style="width:' + Number(project.progress || 0) + '%"></span></span>' + Number(project.progress || 0) + '%</span><small class="progress-meta">' + Number(project.approved || 0) + '/' + Number(project.target || 0) + ' approved</small></td>'
                + '<td data-label="Workers"><span class="worker-total"><svg class="icon"><use href="#users"/></svg>' + Number(project.workers_count || 0) + '</span></td>'
                + '<td data-label="Actions"><div class="company-actions">'
                + '<button class="company-action" type="button" data-project-id="' + project.id + '" data-project-action="view" aria-label="View project" title="View project"><svg class="icon" aria-hidden="true"><use href="#eye"/></svg></button>'
                + '<button class="company-action edit" type="button" data-project-id="' + project.id + '" data-project-action="edit" aria-label="Edit project" title="Edit project"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 4 5 5M4 20l5-1L21 7a2 2 0 0 0-5-5L4 14Zm-1 3h18"/></svg></button>'
                + '<button class="company-action delete" type="button" data-project-id="' + project.id + '" data-project-action="delete" aria-label="Delete project" title="Delete project"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg></button>'
                + '</div></td>'
                + '</tr>';
        }).join('');

        document.getElementById('projects-empty').hidden = filteredProjects.length > 0;
        updateCount(filteredProjects.length, startIndex, pageSize);
        renderPagination(totalPages);
    }

    function updateCount(total, startIndex, pageSize) {
        const count = document.getElementById('project-count');
        count.textContent = total === 0 ? '0 projects' : `${startIndex + 1}-${Math.min(startIndex + pageSize, total)} of ${total} projects`;
    }

    function renderPagination(totalPages) {
        const pagination = document.getElementById('project-pagination');
        let html = '';
        for (let page = 1; page <= totalPages; page++) {
            html += '<button type="button" data-page="' + page + '" class="' + (page === currentPage ? 'active' : '') + '">' + page + '</button>';
        }
        pagination.innerHTML = html;
    }

    function resetFilters() {
        searchInput.value = '';
        companySelect.value = 'all';
        statusSelect.value = 'all';
        dateInput.value = '';
        currentPage = 1;
        renderProjects();
    }

    [searchInput, companySelect, statusSelect, dateInput, pageSizeSelect].forEach(function (input) {
        input.addEventListener(input === searchInput ? 'input' : 'change', function () {
            currentPage = 1;
            renderProjects();
        });
    });

    document.getElementById('dashboard-search')?.addEventListener('input', function (event) {
        searchInput.value = event.target.value;
        currentPage = 1;
        renderProjects();
    });

    searchInput.addEventListener('input', function () {
        const topbarSearch = document.getElementById('dashboard-search');
        if (topbarSearch) {
            topbarSearch.value = searchInput.value;
        }
    });

    document.getElementById('reset-projects').addEventListener('click', resetFilters);
    document.getElementById('project-pagination').addEventListener('click', function (event) {
        const button = event.target.closest('[data-page]');
        if (!button) return;
        currentPage = Number(button.dataset.page);
        renderProjects();
    });

    document.getElementById('select-all-projects').addEventListener('change', function (event) {
        document.querySelectorAll('#project-rows input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.checked = event.target.checked;
        });
    });

    rows.addEventListener('click', function (event) {
        const button = event.target.closest('[data-project-id]');
        if (!button) return;

        const project = projects.find(function (item) {
            return Number(item.id) === Number(button.dataset.projectId);
        });
        if (!project) return;

        if (button.dataset.projectAction === 'view' || button.dataset.projectAction === 'edit') {
            window.location.assign(button.dataset.projectAction === 'edit' ? project.edit_url : project.show_url);
            return;
        }

        if (button.dataset.projectAction === 'delete') {
            document.getElementById('delete-project-message').textContent = 'Are you sure you want to delete ' + project.name + '? A completed deletion cannot be undone.';
            document.getElementById('delete-project-dialog').showModal();
            return;
        }

        document.getElementById('project-dialog-title').textContent = project.name;
        document.getElementById('project-dialog-text').textContent = [
            'Code: ' + project.project_code,
            'Type: ' + project.project_type_label,
            'Company: ' + project.company,
            'Location: ' + project.location,
            'Activities: ' + Number(project.activities_count || 0),
            'Assignments: ' + Number(project.assignments_count || 0),
            'Workers: ' + Number(project.workers_count || 0),
            'Target: ' + Number(project.target || 0),
            'Approved: ' + Number(project.approved || 0),
            'Remaining: ' + Number(project.remaining || 0),
            'Progress: ' + Number(project.progress || 0) + '%',
            'Status: ' + project.status_label,
        ].join(' | ');
        document.getElementById('project-dialog').showModal();

        if (button.dataset.projectAction === 'edit') {
            const toast = document.getElementById('project-toast');
            toast.textContent = 'Edit action is not enabled yet. Showing current project details.';
            toast.hidden = false;
            setTimeout(function () { toast.hidden = true; }, 2500);
        }
    });

    document.getElementById('close-project-dialog').addEventListener('click', function () {
        document.getElementById('project-dialog').close();
    });

    const deleteDialog = document.getElementById('delete-project-dialog');
    document.getElementById('cancel-project-delete').addEventListener('click', () => deleteDialog.close());
    document.getElementById('confirm-project-delete').addEventListener('click', () => {
        deleteDialog.close();
        document.getElementById('project-dialog-title').textContent = 'Project deletion';
        document.getElementById('project-dialog-text').textContent = 'Project deletion is not connected yet. No project has been deleted.';
        document.getElementById('project-dialog').showModal();
    });
    renderProjects();
})();
