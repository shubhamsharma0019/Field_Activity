(() => {
    // Sample users. Backend database data can replace this array later.
    const users = [
        ['Ramesh Kumar', 'ramesh@mail.com', '9876543210', 'worker', 'ABC Company', 'Active', '28 Sep 2026'],
        ['Suresh Patel', 'suresh@mail.com', '9876543211', 'worker', 'XYZ Pvt Ltd', 'Active', '28 Sep 2026'],
        ['Amit Singh', 'amit@mail.com', '9876543212', 'worker', 'Sunrise Corp', 'Inactive', '27 Sep 2026'],
        ['Priya Sharma', 'priya@mail.com', '9876543213', 'company', 'GreenTech', 'Active', '27 Sep 2026'],
        ['Neha Verma', 'neha@mail.com', '9876543214', 'worker', 'ABC Company', 'Active', '26 Sep 2026'],
        ['Vikram Singh', 'vikram@mail.com', '9876543215', 'worker', 'Market Survey', 'Active', '25 Sep 2026'],
        ['Anil Yadav', 'anil@mail.com', '9876543216', 'admin', '-', 'Active', '25 Sep 2026'],
        ['Kavita Joshi', 'kavita@mail.com', '9876543217', 'worker', 'River Awareness', 'Active', '24 Sep 2026'],
        ['Manish Gupta', 'manish@mail.com', '9876543218', 'company', 'BuildWell', 'Inactive', '23 Sep 2026'],
        ['Pooja Mehta', 'pooja@mail.com', '9876543219', 'worker', 'Green City Initiative', 'Active', '22 Sep 2026'],
    ];

    let currentPage = 1;

    const searchInput = document.getElementById('user-search');
    const roleSelect = document.getElementById('user-role');
    const companySelect = document.getElementById('user-company');
    const statusSelect = document.getElementById('user-status');
    const pageSizeSelect = document.getElementById('user-page-size');

    // Return users that match all selected filters.
    function getFilteredUsers() {
        const searchText = searchInput.value.toLowerCase();

        return users.filter(function (user) {
            const userText = user.join(' ').toLowerCase();
            const matchesSearch = userText.includes(searchText);
            const matchesRole = roleSelect.value === 'all' || user[3] === roleSelect.value;
            const matchesCompany = companySelect.value === 'all' || user[4] === companySelect.value;
            const matchesStatus = statusSelect.value === 'all' || user[5] === statusSelect.value;

            return matchesSearch && matchesRole && matchesCompany && matchesStatus;
        });
    }

    // Create the user table based on filters and selected page.
    function renderUsers() {
        const filteredUsers = getFilteredUsers();
        const pageSize = Number(pageSizeSelect.value);
        const totalPages = Math.max(1, Math.ceil(filteredUsers.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);

        const startIndex = (currentPage - 1) * pageSize;
        const usersForThisPage = filteredUsers.slice(startIndex, startIndex + pageSize);
        const rows = document.getElementById('user-rows');

        rows.innerHTML = '';

        usersForThisPage.forEach(function (user, index) {
            const originalIndex = users.indexOf(user);
            const initials = user[0].split(' ').map(function (part) { return part[0]; }).join('');
            const row = document.createElement('tr');

            row.innerHTML = `
                <td><input type="checkbox" aria-label="Select ${user[0]}"></td>
                <td>${startIndex + index + 1}</td>
                <td><span class="user-name"><span class="avatar user-avatar">${initials}</span><span>${user[0]}<small>USR${String(originalIndex + 1).padStart(3, '0')}</small></span></span></td>
                <td>${user[1]}</td><td>${user[2]}</td>
                <td><span class="role-badge ${user[3]}">${user[3]}</span></td>
                <td>${user[4]}</td>
                <td><span class="company-status ${user[5] === 'Inactive' ? 'inactive' : ''}">${user[5]}</span></td>
                <td>${user[6]}</td>
                <td><div class="company-actions"><button class="company-action user-action" data-user-index="${originalIndex}" type="button">View</button><button class="company-action edit user-action" data-user-index="${originalIndex}" type="button">Edit</button></div></td>
            `;

            rows.appendChild(row);
        });

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
        pagination.innerHTML = '';

        for (let page = 1; page <= totalPages; page++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = page;
            button.dataset.page = page;

            if (page === currentPage) {
                button.classList.add('active');
            }

            pagination.appendChild(button);
        }
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

    // Show a simple preview for View and Edit buttons.
    document.getElementById('user-rows').addEventListener('click', function (event) {
        const button = event.target.closest('[data-user-index]');

        if (!button) {
            return;
        }

        const user = users[Number(button.dataset.userIndex)];
        document.getElementById('user-dialog-title').textContent = user[0];
        document.getElementById('user-dialog-text').textContent = `${user[1]} · ${user[2]} · ${user[3]} · ${user[5]}`;
        document.getElementById('user-dialog').showModal();
    });

    document.getElementById('close-user-dialog').addEventListener('click', function () {
        document.getElementById('user-dialog').close();
    });

    document.getElementById('add-user').addEventListener('click', function () {
        const toast = document.getElementById('user-toast');
        toast.textContent = 'Add User page will be connected next.';
        toast.hidden = false;

        setTimeout(function () {
            toast.hidden = true;
        }, 2500);
    });

    renderUsers();
})();
