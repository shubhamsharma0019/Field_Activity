@extends('layout.app')

@section('title', 'Companies')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/companies.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><span aria-current="page">Companies</span></nav>
    <div class="page-heading companies-heading">
        <div><h1>Companies</h1><p>Register and manage client companies for project intake.</p></div>
        <a class="primary-button" id="add-company" href="{{ route('web.companies.create') }}"><span>+</span> Add Company</a>
    </div>

    @if (session('status'))
        <div class="company-toast">{{ session('status') }}</div>
    @endif

    <div class="company-summary-grid">
        <article><strong>{{ number_format($summary['total']) }}</strong><span>Total Companies</span></article>
        <article><strong>{{ number_format($summary['active']) }}</strong><span>Active</span></article>
        <article><strong>{{ number_format($summary['inactive']) }}</strong><span>Inactive</span></article>
        <article><strong>{{ number_format($summary['projects']) }}</strong><span>Linked Projects</span></article>
        <article><strong>{{ number_format($summary['workers']) }}</strong><span>Linked Workers</span></article>
    </div>

    <div class="company-filters">
        <label class="company-search"><svg class="icon" aria-hidden="true"><use href="#search"/></svg><input type="search" id="company-search" placeholder="Search companies..." aria-label="Search companies"></label>
        <select id="company-status" aria-label="Filter by status"><option value="all">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
        <select id="company-sort" aria-label="Sort companies"><option value="latest">Sort by: Latest</option><option value="oldest">Sort by: Oldest</option><option value="name">Sort by: Name A-Z</option><option value="projects">Sort by: Projects</option><option value="workers">Sort by: Workers</option></select>
    </div>

    <section class="company-panel" aria-label="Company directory">
        <div class="company-table-scroll">
            <table class="company-table"><thead><tr><th>#</th><th>Logo</th><th>Company Name</th><th>Code</th><th>Email</th><th>Phone</th><th>Contact Person</th><th>Projects</th><th>Workers</th><th>Status</th><th>Created Date</th><th>Actions</th></tr></thead><tbody id="company-rows"></tbody></table>
            <p class="empty-state" id="companies-empty" hidden>No companies found. Try another search or add a company.</p>
        </div>
        <div class="company-footer">
            <label class="page-size">Show <select id="company-page-size"><option>5</option><option selected>10</option><option>25</option></select> entries</label>
            <span id="company-count" class="company-count" aria-live="polite"></span>
            <nav class="pagination" id="company-pagination" aria-label="Company pages"></nav>
        </div>
    </section>

    <dialog id="company-delete-dialog">
        <h2>Delete company?</h2><p id="delete-company-message"></p>
        <div class="company-dialog-actions"><button class="secondary-button" id="cancel-company-delete">Cancel</button><button class="danger-button" id="confirm-company-delete">Delete</button></div>
    </dialog>
    <div class="company-toast" id="company-toast" role="status" hidden></div>
@endsection

@push('scripts')
    <script>
        window.companiesData = @json($companies);
    </script>
    <script src="{{ asset('js/companies.js') }}"></script>
@endpush
