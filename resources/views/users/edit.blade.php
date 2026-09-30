@extends('layout.app')
@section('title', 'Edit User')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user-create.css') }}">
@endpush
@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.users.index') }}">Users</a><span>&rsaquo;</span><a href="{{ route('web.users.show', $user) }}">{{ $user->name }}</a><span>&rsaquo;</span><span>Edit</span></nav>
    <div class="page-heading"><div><h1>Edit User</h1><p>Update contact information and account settings.</p></div></div>
    @if ($errors->any())
        <div role="alert" class="user-form-errors"><strong>Please check the following:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form action="{{ route('web.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        <section class="user-form-card">
            <div class="user-fields">
                <label>Full Name <em>*</em><span class="user-input"><input name="name" required maxlength="100" value="{{ old('name', $user->name) }}" autocomplete="name"></span></label>
                <label>Email <em>*</em><span class="user-input"><input name="email" type="email" required maxlength="150" value="{{ old('email', $user->email) }}" autocomplete="email"></span></label>
                <label>Mobile <em>*</em><span class="user-input"><input name="mobile" type="tel" required pattern="[0-9]{10}" maxlength="10" value="{{ old('mobile', $user->mobile) }}" autocomplete="tel"></span></label>
                <label>Status<span class="user-input"><select name="status"><option value="active" @selected(old('status', $user->status) === 'active')>Active</option>@unless(auth()->id() === $user->id)<option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>@endunless</select></span></label>
                <label>Role<span class="user-input"><input readonly value="{{ str($user->role)->replace('_', ' ')->title() }}"></span></label>
                <label>Company<span class="user-input"><input readonly value="{{ $user->company?->name ?? 'Not assigned' }}"></span></label>
                <label>New Password<span class="user-input"><input name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password"></span><small>Leave blank to keep the current password.</small></label>
                <label>Confirm New Password<span class="user-input"><input name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password"></span></label>
            </div>
        </section>
        <div class="save-user-bar"><a class="secondary-button" href="{{ route('web.users.show', $user) }}">Cancel</a><button class="primary-button" type="submit">Save Changes</button></div>
    </form>
@endsection
