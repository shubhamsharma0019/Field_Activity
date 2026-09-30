const companies = Array.isArray(window.companiesData) ? window.companiesData : [];
let companyPage = 1;
let selectedCompany = null;
let toastTimer;

const companySearch = document.getElementById('company-search');
const companyStatus = document.getElementById('company-status');
const companySort = document.getElementById('company-sort');
const companyPageSize = document.getElementById('company-page-size');
const rowsTarget = document.getElementById('company-rows');
const emptyState = document.getElementById('companies-empty');
const companyCount = document.getElementById('company-count');
const paginationTarget = document.getElementById('company-pagination');
const deleteDialog = document.getElementById('company-delete-dialog');

function escapeCompanyText(value) {
    const element = document.createElement('span');
    element.textContent = value === null || value === undefined || value === '' ? '-' : String(value);
    return element.innerHTML;
}

function companyActionIcon(action) {
    if (action === 'view') {
        return '<svg class="icon" aria-hidden="true"><use href="#eye"/></svg>';
    }
    if (action === 'edit') {
        return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 3 6 6M4 20l5-1L21 7a2 2 0 0 0-4-4L5 15ZM3 22h18"/></svg>';
    }
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 16h12l1-16M10 10v8m4-8v8"/></svg>';
}

function filteredCompanies() {
    const query = companySearch.value.trim().toLowerCase();

    return companies
        .filter(function (company) {
            const searchable = [
                company.name,
                company.company_code,
                company.email,
                company.phone,
                company.contact,
                company.city,
                company.state,
                company.address,
            ].join(' ').toLowerCase();

            return searchable.includes(query)
                && (companyStatus.value === 'all' || company.status === companyStatus.value);
        })
        .sort(function (first, second) {
            if (companySort.value === 'name') {
                return String(first.name || '').localeCompare(String(second.name || ''));
            }
            if (companySort.value === 'projects') {
                return Number(second.projects_count || 0) - Number(first.projects_count || 0);
            }
            if (companySort.value === 'workers') {
                return Number(second.workers_count || 0) - Number(first.workers_count || 0);
            }

            const firstDate = first.created_at || '';
            const secondDate = second.created_at || '';
            return companySort.value === 'oldest'
                ? firstDate.localeCompare(secondDate)
                : secondDate.localeCompare(firstDate);
        });
}

function renderCompanies() {
    const filtered = filteredCompanies();
    const pageSize = Number(companyPageSize.value);
    const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
    companyPage = Math.min(companyPage, pageCount);

    const start = (companyPage - 1) * pageSize;
    const visibleCompanies = filtered.slice(start, start + pageSize);

    rowsTarget.innerHTML = visibleCompanies.map(function (company, index) {
        const logo = company.logo === 'building'
            ? '<svg class="icon"><use href="#building"/></svg>'
            : escapeCompanyText(company.symbol);
        const inactiveClass = company.status === 'inactive' ? ' inactive' : '';
        const actions = ['view', 'edit', 'delete'].map(function (action) {
            return '<button class="company-action ' + action + '" data-company-action="' + action + '" data-id="' + company.id + '" aria-label="' + action + ' company ' + company.id + '">' + companyActionIcon(action) + '</button>';
        }).join('');

        return '<tr>'
            + '<td data-label="#">' + (start + index + 1) + '</td>'
            + '<td data-label="Logo"><span class="company-logo logo-' + escapeCompanyText(company.logo) + '" aria-hidden="true">' + logo + '</span></td>'
            + '<td data-label="Company Name">' + escapeCompanyText(company.name) + '</td>'
            + '<td data-label="Code">' + escapeCompanyText(company.company_code) + '</td>'
            + '<td data-label="Email">' + escapeCompanyText(company.email) + '</td>'
            + '<td data-label="Phone">' + escapeCompanyText(company.phone) + '</td>'
            + '<td data-label="Contact Person">' + escapeCompanyText(company.contact) + '</td>'
            + '<td data-label="Projects">' + Number(company.projects_count || 0) + '</td>'
            + '<td data-label="Workers">' + Number(company.workers_count || 0) + '</td>'
            + '<td data-label="Status"><span class="company-status' + inactiveClass + '">' + escapeCompanyText(company.status_label) + '</span></td>'
            + '<td data-label="Created Date">' + escapeCompanyText(company.created_date) + '</td>'
            + '<td data-label="Actions"><div class="company-actions">' + actions + '</div></td>'
            + '</tr>';
    }).join('');

    emptyState.hidden = filtered.length > 0;
    companyCount.textContent = filtered.length
        ? (start + 1) + '-' + Math.min(start + pageSize, filtered.length) + ' of ' + filtered.length + ' companies'
        : '0 companies';

    let pagination = '<button data-page="' + (companyPage - 1) + '" aria-label="Previous page" ' + (companyPage === 1 ? 'disabled' : '') + '>&lsaquo;</button>';
    for (let page = 1; page <= pageCount; page++) {
        pagination += '<button data-page="' + page + '" class="' + (page === companyPage ? 'active' : '') + '" ' + (page === companyPage ? 'aria-current="page"' : '') + '>' + page + '</button>';
    }
    pagination += '<button data-page="' + (companyPage + 1) + '" aria-label="Next page" ' + (companyPage === pageCount ? 'disabled' : '') + '>&rsaquo;</button>';
    paginationTarget.innerHTML = pagination;
}

function resetCompanyPage() {
    companyPage = 1;
    renderCompanies();
}

function showCompanyToast(message) {
    const toast = document.getElementById('company-toast');
    clearTimeout(toastTimer);
    toast.textContent = message;
    toast.hidden = false;
    toastTimer = setTimeout(function () { toast.hidden = true; }, 3000);
}

companySearch.addEventListener('input', function () {
    const topbarSearch = document.getElementById('dashboard-search');
    if (topbarSearch) {
        topbarSearch.value = companySearch.value;
    }
    resetCompanyPage();
});

document.getElementById('dashboard-search')?.addEventListener('input', function (event) {
    companySearch.value = event.target.value;
    resetCompanyPage();
});

[companyStatus, companySort, companyPageSize].forEach(function (control) {
    control.addEventListener('change', resetCompanyPage);
});

paginationTarget.addEventListener('click', function (event) {
    const button = event.target.closest('[data-page]');
    if (button && !button.disabled) {
        companyPage = Number(button.dataset.page);
        renderCompanies();
    }
});

rowsTarget.addEventListener('click', function (event) {
    const button = event.target.closest('[data-company-action]');
    if (!button) {
        return;
    }

    const company = companies.find(function (item) {
        return Number(item.id) === Number(button.dataset.id);
    });

    if (!company) {
        return;
    }

    if (button.dataset.companyAction === 'view') {
        document.getElementById('dialog-title').textContent = company.name;
        document.getElementById('dialog-text').textContent = [
            'Code: ' + (company.company_code || '-'),
            'Email: ' + (company.email || '-'),
            'Phone: ' + (company.phone || '-'),
            'Contact: ' + (company.contact || '-'),
            'Location: ' + [company.city, company.state].filter(Boolean).join(', '),
            'Projects: ' + Number(company.projects_count || 0),
            'Workers: ' + Number(company.workers_count || 0),
            'Status: ' + company.status_label,
        ].filter(Boolean).join(' | ');
        document.getElementById('detail-dialog').showModal();
    }

    if (button.dataset.companyAction === 'edit') {
        showCompanyToast('Edit screen is not enabled yet. Use Add Company for new registrations.');
    }

    if (button.dataset.companyAction === 'delete') {
        selectedCompany = company;
        document.getElementById('delete-company-message').textContent =
            company.projects_count > 0 || company.users_count > 0
                ? company.name + ' cannot be deleted while projects or users are linked.'
                : 'Delete ' + company.name + '? This action should be confirmed by an admin.';
        deleteDialog.showModal();
    }
});

document.getElementById('cancel-company-delete')?.addEventListener('click', function () {
    deleteDialog.close();
});

document.getElementById('confirm-company-delete')?.addEventListener('click', function () {
    deleteDialog.close();
    if (!selectedCompany) {
        return;
    }
    showCompanyToast('Delete is intentionally disabled on this page until backend delete confirmation is wired.');
});

renderCompanies();
