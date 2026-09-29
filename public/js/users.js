(() => {
    const users = Array.isArray(window.usersData) ? window.usersData : [];
    let currentPage = 1;

    const searchInput = document.getElementById('user-search');
    const roleSelect = document.getElementById('user-role');
    const companySelect = document.getElementById('user-company');
    const statusSelect = document.getElementById('user-status');
    const pageSizeSelect = document.getElementById('user-page-size');
    const rows = document.getElementById('user-rows');

    function escapeText(value) {
        const element = document.createElement('span');
        element.textContent = value === null || value === undefined || value === '' ? '-' : String(value);
        return element.innerHTML;
    }

    function initials(name) {
        return String(name || 'NA')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map(function (part) { return part[0] || ''; })
            .join('')
            .toUpperCase() || 'NA';
    }

    function getFilteredUsers() {
        const searchText = searchInput.value.toLowerCase().trim();

        return users.filter(function (user) {
            const userText = [
                user.name,
                user.code,
                user.email,
                user.mobile,
                user.role_label,
                user.company,
                user.status_label,
            ].join(' ').toLowerCase();

            const matchesSearch = userText.includes(searchText);
            const matchesRole = roleSelect.value === 'all' || user.role === roleSelect.value;
            const matchesCompany = companySelect.value === 'all' || String(user.company_id || '') === companySelect.value;
            const matchesStatus = statusSelect.value === 'all' || user.status === statusSelect.value;

            return matchesSearch && matchesRole && matchesCompany && matchesStatus;
        });
    }

    function renderUsers() {
        const filteredUsers = getFilteredUsers();
        const pageSize = Number(pageSizeSelect.value);
        const totalPages = Math.max(1, Math.ceil(filteredUsers.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);

        const startIndex = (currentPage - 1) * pageSize;
        const usersForThisPage = filteredUsers.slice(startIndex, startIndex + pageSize);

        rows.innerHTML = usersForThisPage.map(function (user, index) {
            const inactiveClass = user.status === 'inactive' ? ' inactive' : '';

            return '<tr>'
                + '<td><input type="checkbox" aria-label="Select ' + escapeText(user.name) + '"></td>'
                + '<td>' + (startIndex + index + 1) + '</td>'
                + '<td><span class="user-name"><span class="avatar user-avatar">' + escapeText(initials(user.name)) + '</span><span>' + escapeText(user.name) + '<small>' + escapeText(user.code) + '</small></span></span></td>'
                + '<td>' + escapeText(user.email) + '</td>'
                + '<td>' + escapeText(user.mobile) + '</td>'
                + '<td><span class="role-badge ' + escapeText(user.role) + '">' + escapeText(user.role_label) + '</span></td>'
                + '<td>' + escapeText(user.company) + '</td>'
                + '<td><span class="company-status' + inactiveClass + '">' + escapeText(user.status_label) + '</span></td>'
                + '<td>' + escapeText(user.created_date) + '</td>'
                + '<td><div class="company-actions"><button class="company-action user-action" data-user-id="' + user.id + '" data-user-action="view" type="button">View</button><button class="company-action edit user-action" data-user-id="' + user.id + '" data-user-action="edit" type="button">Edit</button></div></td>'
                + '</tr>';
        }).join('');

        document.getElementById('users-empty').hidden = filteredUsers.length > 0;
        updateUserCount(filteredUsers.length, startIndex, pageSize);
        renderPagination(totalPages);
    }

    function updateUserCount(total, startIndex, pageSize) {
        const count = document.getElementById('user-count');
        count.textContent = total === 0 ? '0 users' : `${startIndex + 1}-${Math.min(startIndex + pageSize, total)} of ${total} users`;
    }

    function renderPagination(totalPages) {
        const pagination = document.getElementById('user-pagination');
        let html = '';

        for (let page = 1; page <= totalPages; page++) {
            html += '<button type="button" data-page="' + page + '" class="' + (page === currentPage ? 'active' : '') + '">' + page + '</button>';
        }

        pagination.innerHTML = html;
    }

    function resetFilters() {
        searchInput.value = '';
        roleSelect.value = 'all';
        companySelect.value = 'all';
        statusSelect.value = 'all';
        currentPage = 1;
        renderUsers();
    }

    [searchInput, roleSelect, companySelect, statusSelect, pageSizeSelect].forEach(function (input) {
        input.addEventListener(input === searchInput ? 'input' : 'change', function () {
            currentPage = 1;
            renderUsers();
        });
    });

    document.getElementById('dashboard-search')?.addEventListener('input', function (event) {
        searchInput.value = event.target.value;
        currentPage = 1;
        renderUsers();
    });

    searchInput.addEventListener('input', function () {
        const topbarSearch = document.getElementById('dashboard-search');
        if (topbarSearch) {
            topbarSearch.value = searchInput.value;
        }
    });

    document.getElementById('reset-users').addEventListener('click', resetFilters);

    document.getElementById('user-pagination').addEventListener('click', function (event) {
        const button = event.target.closest('[data-page]');
        if (button) {
            currentPage = Number(button.dataset.page);
            renderUsers();
        }
    });

    document.getElementById('select-all').addEventListener('change', function (event) {
        document.querySelectorAll('#user-rows input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.checked = event.target.checked;
        });
    });

    rows.addEventListener('click', function (event) {
        const button = event.target.closest('[data-user-id]');
        if (!button) {
            return;
        }

        const user = users.find(function (item) {
            return Number(item.id) === Number(button.dataset.userId);
        });
        if (!user) {
            return;
        }

        document.getElementById('user-dialog-title').textContent = user.name;
        document.getElementById('user-dialog-text').textContent = [
            'Code: ' + user.code,
            'Email: ' + (user.email || '-'),
            'Mobile: ' + (user.mobile || '-'),
            'Role: ' + user.role_label,
            'Company: ' + (user.company || '-'),
            'Status: ' + user.status_label,
            user.role === 'worker' ? 'Assignments: ' + Number(user.assignments_count || 0) : '',
            user.role === 'worker' ? 'Submissions: ' + Number(user.submissions_count || 0) : '',
        ].filter(Boolean).join(' | ');
        document.getElementById('user-dialog').showModal();

        if (button.dataset.userAction === 'edit') {
            const toast = document.getElementById('user-toast');
            toast.textContent = 'Edit action is not enabled yet. Showing current user details.';
            toast.hidden = false;
            setTimeout(function () { toast.hidden = true; }, 2500);
        }
    });

    document.getElementById('close-user-dialog').addEventListener('click', function () {
        document.getElementById('user-dialog').close();
    });

    renderUsers();
})();
