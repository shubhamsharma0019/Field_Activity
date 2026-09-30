@extends('layout.app')

@section('title', 'Users')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('content')
    {{-- Breadcrumb --}}
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('web.dashboard') }}">
            <svg class="icon" aria-hidden="true"><use href="#home"/></svg>
            Dashboard
        </a>
        <span>&rsaquo;</span>
        <span aria-current="page">Users</span>
    </nav>

    {{-- Page heading --}}
    <div class="page-heading users-heading">
        <div>
            <h1>Users</h1>
            <p>View and manage all system users (admin, company, worker).</p>
        </div>

        <a class="primary-button" href="{{ route('web.users.create') }}">
            <span>+</span>
            Add User
        </a>
    </div>

    {{-- User summary cards --}}
    <div class="user-stats">
        <article class="user-stat blue">
            <span class="user-stat-icon"><svg class="icon"><use href="#users"/></svg></span>
            <div><h2>Total Users</h2><strong>{{ number_format($summary['total']) }}</strong><p>+ {{ number_format($summary['total_month']) }} this month</p></div>
        </article>
        <article class="user-stat green">
            <span class="user-stat-icon"><svg class="icon"><use href="#users"/></svg></span>
            <div><h2>Admins</h2><strong>{{ number_format($summary['admins']) }}</strong><p>+ {{ number_format($summary['admins_month']) }} this month</p></div>
        </article>
        <article class="user-stat orange">
            <span class="user-stat-icon"><svg class="icon"><use href="#users"/></svg></span>
            <div><h2>Company Users</h2><strong>{{ number_format($summary['company_users']) }}</strong><p>+ {{ number_format($summary['company_users_month']) }} this month</p></div>
        </article>
        <article class="user-stat purple">
            <span class="user-stat-icon"><svg class="icon"><use href="#users"/></svg></span>
            <div><h2>Workers</h2><strong>{{ number_format($summary['workers']) }}</strong><p>{{ number_format($summary['active_workers']) }} active</p></div>
        </article>
    </div>

    {{-- Filters --}}
    <div class="user-filters">
        <label class="company-search">
            <svg class="icon" aria-hidden="true"><use href="#search"/></svg>
            <input id="user-search" type="search" aria-label="Search users by name, email or mobile" placeholder="Search users by name, email or mobile...">
        </label>

        <select id="user-role" aria-label="Filter users by role">
            <option value="all">All Roles</option>
            <option value="super_admin">Super Admin</option>
            <option value="admin">Admin</option>
            <option value="company">Company</option>
            <option value="worker">Worker</option>
        </select>

        <select id="user-company" aria-label="Filter users by company">
            <option value="all">All Companies</option>
            @foreach ($companies as $company)
                <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
            @endforeach
        </select>

        <select id="user-status" aria-label="Filter users by status">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <button class="reset-button" id="reset-users" type="button">Reset</button>
    </div>

    {{-- User table. Rows are created by users.js. --}}
    <section class="user-panel">
        <div class="company-table-scroll">
            <table class="user-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" aria-label="Select all users"></th>
                        <th>#</th><th>Name</th><th>Email</th><th>Mobile</th><th>Role</th>
                        <th>Company</th><th>Status</th><th>Created Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="user-rows"></tbody>
            </table>
            <p class="empty-state" id="users-empty" hidden>No users found.</p>
        </div>

        <div class="company-footer">
            <label class="page-size">
                Show
                <select id="user-page-size"><option>5</option><option selected>10</option></select>
                entries
            </label>
            <span id="user-count" class="company-count"></span>
            <nav class="pagination" id="user-pagination" aria-label="User pages"></nav>
        </div>
    </section>

    {{-- Small popup used by View and Edit buttons --}}
    <dialog id="user-dialog">
        <button class="dialog-close" id="close-user-dialog" type="button" aria-label="Close">×</button>
        <h2 id="user-dialog-title"></h2>
        <p id="user-dialog-text"></p>
    </dialog>

    <dialog id="delete-user-dialog" aria-labelledby="delete-user-title" aria-describedby="delete-user-message">
        <h2 id="delete-user-title">Delete user?</h2>
        <p id="delete-user-message"></p>
        <div class="user-dialog-actions">
            <button class="secondary-button" id="cancel-user-delete" type="button" autofocus>Cancel</button>
            <button class="danger-button" id="confirm-user-delete" type="button">Delete</button>
        </div>
    </dialog>
    <div class="company-toast" id="user-toast" role="status" hidden></div>
@endsection

@push('scripts')
    <script>
        window.usersData = @json($users);
    </script>
    <script src="{{ asset('js/users.js') }}"></script>
@endpush
