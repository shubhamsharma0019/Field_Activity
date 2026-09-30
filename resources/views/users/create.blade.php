@extends('layout.app')

@section('title', 'Add User')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user-create.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span><a href="{{ route('web.users.index') }}">Users</a>
        <span>&rsaquo;</span><span>Add User</span>
    </nav>

    <div class="page-heading create-user-heading">
        <div><h1>Add User</h1><p>Create a new user account in the system.</p></div>
        <a class="back-users-button" href="{{ route('web.users.index') }}">&larr; Back to Users</a>
    </div>

    <form id="create-user-form" class="create-user-form">
        <section class="user-form-card">
            <div class="card-title">
                <span class="title-icon"><svg class="icon"><use href="#users"/></svg></span>
                <div><h2>Basic Information</h2><p>Enter the user's personal and account details.</p></div>
            </div>
            <div class="user-fields">
                <label>Full Name <em>*</em><span class="user-input"><svg class="icon"><use href="#users"/></svg><input name="name" required placeholder="Enter full name"></span></label>
                <label>Role <em>*</em><span class="user-input"><svg class="icon"><use href="#users"/></svg><select id="new-user-role" name="role" required><option value="" selected disabled>Select role</option><option value="admin">Admin</option><option value="company">Company</option><option value="worker">Worker</option></select></span></label>
                <label>Email <em>*</em><span class="user-input"><svg class="icon"><use href="#mail"/></svg><input name="email" type="email" required placeholder="Enter email address"></span></label>
                <label>Company <em id="company-required">*</em><span class="user-input"><svg class="icon"><use href="#building"/></svg><select id="new-user-company" name="company" required><option value="" selected disabled>Select company</option><option>ABC Company</option><option>XYZ Pvt Ltd</option><option>GreenTech</option></select></span><small>Not required for Admin role</small></label>
                <label>Mobile Number <em>*</em><span class="user-input"><svg class="icon"><use href="#phone"/></svg><input name="mobile" type="tel" required placeholder="Enter mobile number"></span></label>
                <label>Employee Code<span class="user-input"><svg class="icon"><use href="#file"/></svg><input name="employee_code" placeholder="Enter employee code (optional)"></span></label>
                <label>Password <em>*</em><span class="user-input"><svg class="icon"><use href="#lock"/></svg><input id="new-user-password" name="password" type="password" required placeholder="Enter password"><button class="eye-button" type="button" data-password="new-user-password"><svg class="icon"><use href="#eye"/></svg></button></span></label>
                <label>Status <em>*</em><span class="user-input"><span class="status-dot"></span><select name="status"><option>Active</option><option>Inactive</option></select></span></label>
                <label>Confirm Password <em>*</em><span class="user-input"><svg class="icon"><use href="#lock"/></svg><input id="confirm-user-password" name="confirm_password" type="password" required placeholder="Confirm password"><button class="eye-button" type="button" data-password="confirm-user-password"><svg class="icon"><use href="#eye"/></svg></button></span></label>
                <label>Profile Photo<span class="profile-upload"><span id="photo-placeholder"><strong>Upload Profile Photo</strong><small>PNG, JPG, JPEG (Max 2MB)</small><b>Choose File</b></span><img id="photo-preview" hidden alt="Profile photo preview"></span><input id="profile-photo" type="file" accept="image/png,image/jpeg" hidden></label>
            </div>
        </section>

        <aside class="user-side-panel">
            <section class="user-form-card roles-card"><h2>User Roles &amp; Permissions</h2><article><b class="admin-role">A</b><div><strong>Admin</strong><small>Full system access and management</small></div></article><article><b class="company-role">C</b><div><strong>Company</strong><small>Manage own projects, workers and activities</small></div></article><article><b class="worker-role">W</b><div><strong>Worker</strong><small>View assigned tasks and submit field activities</small></div></article></section>
            <section class="user-form-card"><div class="card-title"><span class="title-icon"><svg class="icon"><use href="#file"/></svg></span><div><h2>Additional Information</h2><p>Optional details for better tracking and management.</p></div></div><div class="additional-fields"><label>Date of Birth<input type="date" name="birth_date"></label><label>Gender<select name="gender"><option value="" selected>Select gender</option><option>Male</option><option>Female</option><option>Other</option></select></label><label>Address<input name="address" placeholder="Enter address (optional)"></label><label>Notes<textarea id="user-notes" name="notes" maxlength="500" placeholder="Enter any additional notes (optional)"></textarea><small id="user-notes-count">0/500</small></label></div></section>
        </aside>
        <div class="save-user-bar"><a class="secondary-button" href="{{ route('web.users.index') }}">Cancel</a><button class="primary-button" type="submit">Save User</button></div>
    </form>
    <div id="create-user-toast" class="company-toast" role="status" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/user-create.js') }}"></script>
@endpush
