@extends('layout.app')
@section('title', 'Companies')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/companies.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>›</span><span aria-current="page">Companies</span></nav>
    <div class="page-heading companies-heading">
        <div><h1>Companies</h1><p>Manage and view all companies in the system.</p></div>
        <button class="primary-button" id="add-company"><span>＋</span> Add Company</button>
    </div>

    <div class="company-filters">
        <label class="company-search"><svg class="icon" aria-hidden="true"><use href="#search"/></svg><input type="search" id="company-search" placeholder="Search companies..." aria-label="Search companies"></label>
        <select id="company-status" aria-label="Filter by status"><option value="all">All Status</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
        <select id="company-sort" aria-label="Sort companies"><option value="latest">Sort by: Latest</option><option value="oldest">Sort by: Oldest</option><option value="name">Sort by: Name A–Z</option></select>
    </div>

    <section class="company-panel" aria-label="Company directory">
        <div class="company-table-scroll">
            <table class="company-table"><thead><tr><th>#</th><th>Logo</th><th>Company Name</th><th>Email</th><th>Phone</th><th>Contact Person</th><th>Status</th><th>Created Date</th><th>Actions</th></tr></thead><tbody id="company-rows"></tbody></table>
            <p class="empty-state" id="companies-empty" hidden>No companies found. Try another search or add a company.</p>
        </div>
        <div class="company-footer">
            <label class="page-size">Show <select id="company-page-size"><option>5</option><option selected>10</option><option>25</option></select> entries</label>
            <span id="company-count" class="company-count" aria-live="polite"></span>
            <nav class="pagination" id="company-pagination" aria-label="Company pages"></nav>
        </div>
    </section>
    <p class="preview-note">Design preview · Changes are kept until you refresh this page.</p>

    <dialog id="company-form-dialog" class="company-dialog">
        <form id="company-form">
            <div class="company-dialog-heading"><h2 id="company-form-title">Add Company</h2><button type="button" class="icon-button" data-close-company aria-label="Close form">×</button></div>
            <input type="hidden" id="company-id">
            <div class="company-form-grid">
                <label>Company Name<input id="company-name" required maxlength="100" placeholder="Enter company name"></label>
                <label>Email<input id="company-email" type="email" required maxlength="150" placeholder="company@example.com"></label>
                <label>Phone<input id="company-phone" type="tel" required maxlength="20" pattern="[0-9+() .\-]{7,20}" placeholder="Enter phone number"></label>
                <label>Contact Person<input id="company-contact" required maxlength="100" placeholder="Enter contact name"></label>
                <label>Status<select id="company-form-status"><option>Active</option><option>Inactive</option></select></label>
            </div>
            <div class="company-dialog-actions"><button class="secondary-button" type="button" data-close-company>Cancel</button><button class="primary-button" type="submit">Save Company</button></div>
        </form>
    </dialog>
    <dialog id="company-delete-dialog">
        <h2>Delete company?</h2><p id="delete-company-message"></p>
        <div class="company-dialog-actions"><button class="secondary-button" id="cancel-company-delete">Cancel</button><button class="danger-button" id="confirm-company-delete">Delete</button></div>
    </dialog>
    <div class="company-toast" id="company-toast" role="status" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/companies.js') }}"></script>
@endpush
