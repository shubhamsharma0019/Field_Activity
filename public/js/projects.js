(() => {
    // Sample project data for the design preview.
    const projects = [
        ['City Clean Drive', 'ABC Company', 'New Delhi, Delhi', '01 Sep 2026', '30 Sep 2026', 'Active', 75, 12],
        ['Market Survey', 'XYZ Pvt Ltd', 'Noida, UP', '15 Aug 2026', '30 Sep 2026', 'Active', 45, 8],
        ['River Awareness', 'Sunrise Corp', 'Gurgaon, HR', '01 Sep 2026', '15 Oct 2026', 'On Hold', 30, 6],
        ['Green City Initiative', 'GreenTech', 'Faridabad, HR', '01 Oct 2026', '31 Oct 2026', 'Active', 60, 10],
        ['Poster Installation', 'BuildWell', 'Ghaziabad, UP', '20 Aug 2026', '20 Sep 2026', 'Completed', 100, 15],
        ['Shop Visit Campaign', 'Infra Solutions', 'Lucknow, UP', '05 Sep 2026', '30 Sep 2026', 'Active', 40, 9],
        ['Road Show', 'Metro Constructions', 'Jaipur, RJ', '12 Jul 2026', '30 Sep 2026', 'Active', 55, 11],
        ['Visit Shops', 'Urban Developers', 'Indore, MP', '25 Aug 2026', '25 Sep 2026', 'On Hold', 20, 5],
    ];

    let currentPage = 1;
    const searchInput = document.getElementById('project-search');
    const companySelect = document.getElementById('project-company');
    const statusSelect = document.getElementById('project-status');
    const dateInput = document.getElementById('project-date');
    const pageSizeSelect = document.getElementById('project-page-size');

    function getFilteredProjects() {
        const searchText = searchInput.value.toLowerCase();

        return projects.filter(function (project) {
            const projectText = project.join(' ').toLowerCase();
            const matchesSearch = projectText.includes(searchText);
            const matchesCompany = companySelect.value === 'all' || project[1] === companySelect.value;
            const matchesStatus = statusSelect.value === 'all' || project[5] === statusSelect.value;

            return matchesSearch && matchesCompany && matchesStatus;
        });
    }

    function getStatusClass(status) {
        if (status === 'On Hold') return 'hold';
        if (status === 'Completed') return 'completed';
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
        const rows = document.getElementById('project-rows');
        rows.innerHTML = '';

        projectsForPage.forEach(function (project, index) {
            const imageNumber = (projects.indexOf(project) % 4) + 1;
            const row = document.createElement('tr');

            row.innerHTML = `
                <td><input type="checkbox" aria-label="Select ${project[0]}"></td>
                <td>${startIndex + index + 1}</td>
                <td><span class="project-name"><img class="project-thumb" src="/images/admin-construction.jpg" alt=""><span>${project[0]}<small>PRJ${String(projects.indexOf(project) + 1).padStart(3, '0')}</small></span></span></td>
                <td>${project[1]}</td><td>${project[2]}</td><td>${project[3]}</td><td>${project[4]}</td>
                <td><span class="project-status ${getStatusClass(project[5])}">${project[5]}</span></td>
                <td><span class="progress-wrap"><span class="progress-bar ${getProgressClass(project[6])}"><span style="width:${project[6]}%"></span></span>${project[6]}%</span></td>
                <td><span class="worker-total"><svg class="icon"><use href="#users"/></svg>${project[7]}</span></td>
                <td><div class="company-actions"><button class="company-action" type="button" data-project="${projects.indexOf(project)}">View</button><button class="company-action" type="button" data-project="${projects.indexOf(project)}">Edit</button></div></td>
            `;
            rows.appendChild(row);
        });

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
        pagination.innerHTML = '';

        for (let page = 1; page <= totalPages; page++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = page;
            button.dataset.page = page;
            if (page === currentPage) button.classList.add('active');
            pagination.appendChild(button);
        }
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

    document.getElementById('project-rows').addEventListener('click', function (event) {
        const button = event.target.closest('[data-project]');
        if (!button) return;
        const project = projects[Number(button.dataset.project)];
        document.getElementById('project-dialog-title').textContent = project[0];
        document.getElementById('project-dialog-text').textContent = `${project[1]} · ${project[2]} · ${project[5]} · ${project[6]}% complete`;
        document.getElementById('project-dialog').showModal();
    });

    document.getElementById('close-project-dialog').addEventListener('click', function () {
        document.getElementById('project-dialog').close();
    });

    renderProjects();
})();
