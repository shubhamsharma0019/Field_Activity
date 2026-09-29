// Design preview data. These changes stay in memory until the page is refreshed.
let companies = [
    { id: 1, name: 'ABC Company', email: 'abc@gmail.com', phone: '9876543210', contact: 'Ramesh Kumar', status: 'Active', date: '2026-09-01', logo: 'leaf', symbol: '❧' },
    { id: 2, name: 'XYZ Pvt Ltd', email: 'xyz@gmail.com', phone: '9876543211', contact: 'Suresh Patel', status: 'Active', date: '2026-08-15', logo: 'cross', symbol: '✕' },
    { id: 3, name: 'Sunrise Corp', email: 'sunrise@mail.com', phone: '9876543212', contact: 'Amit Singh', status: 'Inactive', date: '2026-08-10', logo: 'sun', symbol: '☀' },
    { id: 4, name: 'GreenTech', email: 'green@mail.com', phone: '9876543213', contact: 'Priya Sharma', status: 'Active', date: '2026-08-01', logo: 'leaf', symbol: '◒' },
    { id: 5, name: 'BuildWell', email: 'build@mail.com', phone: '9876543214', contact: 'Neha Verma', status: 'Active', date: '2026-07-28', logo: 'building', symbol: '' },
    { id: 6, name: 'Infra Solutions', email: 'infra@mail.com', phone: '9876543215', contact: 'Vikram Singh', status: 'Active', date: '2026-07-20', logo: 'triangle', symbol: '▲' },
    { id: 7, name: 'Metro Constructions', email: 'metro@mail.com', phone: '9876543216', contact: 'Anil Yadav', status: 'Active', date: '2026-07-12', logo: 'spiral', symbol: '◉' },
    { id: 8, name: 'Urban Developers', email: 'urban@mail.com', phone: '9876543217', contact: 'Kavita Joshi', status: 'Inactive', date: '2026-07-05', logo: 'wave', symbol: '≋' },
];
let companyPage = 1;
let deleteCompanyId = null;
let toastTimer;
const companySearch = document.getElementById('company-search');
const companyStatus = document.getElementById('company-status');
const companySort = document.getElementById('company-sort');
const companyPageSize = document.getElementById('company-page-size');
const companyFormDialog = document.getElementById('company-form-dialog');
const companyForm = document.getElementById('company-form');
const deleteDialog = document.getElementById('company-delete-dialog');

// Escape text before putting user-entered values into table HTML.
function escapeCompanyText(value) {
    const element = document.createElement('span');
    element.textContent = String(value);
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

function renderCompanies() {
    const query = companySearch.value.trim().toLowerCase();
    const filtered = companies.filter(function (company) {
        const matchesSearch = [company.name, company.email, company.phone, company.contact].join(' ').toLowerCase().includes(query);
        return matchesSearch && (companyStatus.value === 'all' || company.status === companyStatus.value);
    });
    filtered.sort(function (first, second) {
        if (companySort.value === 'name') { return first.name.localeCompare(second.name); }
        return companySort.value === 'oldest' ? first.date.localeCompare(second.date) : second.date.localeCompare(first.date);
    });
    const pageSize = Number(companyPageSize.value);
    const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
    companyPage = Math.min(companyPage, pageCount);
    const start = (companyPage - 1) * pageSize;
    const visibleCompanies = filtered.slice(start, start + pageSize);
    document.getElementById('company-rows').innerHTML = visibleCompanies.map(function (company, index) {
        const date = new Date(company.date + 'T12:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const logo = company.logo === 'building' ? '<svg class="icon"><use href="#building"/></svg>' : escapeCompanyText(company.symbol);
        const actions = ['view', 'edit', 'delete'].map(function (action) {
            return '<button class="company-action ' + action + '" data-company-action="' + action + '" data-id="' + company.id + '" aria-label="' + action + ' company ' + company.id + '">' + companyActionIcon(action) + '</button>';
        }).join('');
        return '<tr><td>' + (start + index + 1) + '</td><td><span class="company-logo logo-' + company.logo + '" aria-hidden="true">' + logo + '</span></td><td>' + escapeCompanyText(company.name) + '</td><td>' + escapeCompanyText(company.email) + '</td><td>' + escapeCompanyText(company.phone) + '</td><td>' + escapeCompanyText(company.contact) + '</td><td><span class="company-status ' + (company.status === 'Inactive' ? 'inactive' : '') + '">' + company.status + '</span></td><td>' + date + '</td><td><div class="company-actions">' + actions + '</div></td></tr>';
    }).join('');
    document.getElementById('companies-empty').hidden = filtered.length > 0;
    document.getElementById('company-count').textContent = filtered.length ? (start + 1) + '–' + Math.min(start + pageSize, filtered.length) + ' of ' + filtered.length + ' companies' : '0 companies';
    let pagination = '<button data-page="' + (companyPage - 1) + '" aria-label="Previous page" ' + (companyPage === 1 ? 'disabled' : '') + '>‹</button>';
    for (let page = 1; page <= pageCount; page++) {
        pagination += '<button data-page="' + page + '" class="' + (page === companyPage ? 'active' : '') + '" ' + (page === companyPage ? 'aria-current="page"' : '') + '>' + page + '</button>';
    }
    pagination += '<button data-page="' + (companyPage + 1) + '" aria-label="Next page" ' + (companyPage === pageCount ? 'disabled' : '') + '>›</button>';
    document.getElementById('company-pagination').innerHTML = pagination;
}

function resetCompanyPage() { companyPage = 1; renderCompanies(); }
companySearch.addEventListener('input', function () { document.getElementById('dashboard-search').value = companySearch.value; resetCompanyPage(); });
document.getElementById('dashboard-search').addEventListener('input', function (event) { companySearch.value = event.target.value; resetCompanyPage(); });
[companyStatus, companySort, companyPageSize].forEach(function (control) { control.addEventListener('change', resetCompanyPage); });
document.getElementById('company-pagination').addEventListener('click', function (event) {
    const button = event.target.closest('[data-page]');
    if (button && !button.disabled) { companyPage = Number(button.dataset.page); renderCompanies(); }
});

function openCompanyForm(company) {
    companyForm.reset();
    document.getElementById('company-form-title').textContent = company ? 'Edit Company' : 'Add Company';
    document.getElementById('company-id').value = company ? company.id : '';
    ['name', 'email', 'phone', 'contact'].forEach(function (field) { document.getElementById('company-' + field).value = company ? company[field] : ''; });
    document.getElementById('company-form-status').value = company ? company.status : 'Active';
    companyFormDialog.showModal();
}
document.getElementById('add-company').addEventListener('click', function () {
    window.location.href = '/companies/create';
});
document.querySelectorAll('[data-close-company]').forEach(function (button) { button.addEventListener('click', function () { companyFormDialog.close(); }); });

function showCompanyToast(message) {
    const toast = document.getElementById('company-toast');
    clearTimeout(toastTimer);
    toast.textContent = message;
    toast.hidden = false;
    toastTimer = setTimeout(function () { toast.hidden = true; }, 3000);
}

companyForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const id = Number(document.getElementById('company-id').value);
    const existing = companies.find(function (company) { return company.id === id; });
    const company = existing || { id: Math.max(0, ...companies.map(function (item) { return item.id; })) + 1, date: new Date().toISOString().slice(0, 10), logo: 'initials' };
    for (const field of ['name', 'email', 'phone', 'contact']) {
        const input = document.getElementById('company-' + field);
        if (!input.value.trim()) { input.focus(); return; }
    }
    ['name', 'email', 'phone', 'contact'].forEach(function (field) { company[field] = document.getElementById('company-' + field).value.trim(); });
    company.status = document.getElementById('company-form-status').value;
    if (!existing) { company.symbol = company.name.slice(0, 2).toUpperCase(); companies.push(company); }
    companyFormDialog.close();
    companySearch.value = '';
    document.getElementById('dashboard-search').value = '';
    companyStatus.value = 'all';
    companySort.value = 'latest';
    resetCompanyPage();
    showCompanyToast(existing ? 'Company updated in preview.' : 'Company added to preview.');
});

document.getElementById('company-rows').addEventListener('click', function (event) {
    const button = event.target.closest('[data-company-action]');
    if (!button) { return; }
    const company = companies.find(function (item) { return item.id === Number(button.dataset.id); });
    if (!company) { return; }
    if (button.dataset.companyAction === 'edit') { openCompanyForm(company); }
    if (button.dataset.companyAction === 'view') {
        document.getElementById('dialog-title').textContent = company.name;
        document.getElementById('dialog-text').textContent = company.email + ' · ' + company.phone + ' · Contact: ' + company.contact + ' · ' + company.status;
        document.getElementById('detail-dialog').showModal();
    }
    if (button.dataset.companyAction === 'delete') {
        deleteCompanyId = company.id;
        document.getElementById('delete-company-message').textContent = 'Remove ' + company.name + ' from this preview?';
        deleteDialog.showModal();
    }
});
document.getElementById('cancel-company-delete').addEventListener('click', function () { deleteDialog.close(); });
document.getElementById('confirm-company-delete').addEventListener('click', function () {
    companies = companies.filter(function (company) { return company.id !== deleteCompanyId; });
    deleteDialog.close();
    renderCompanies();
    showCompanyToast('Company removed from preview.');
});
renderCompanies();
