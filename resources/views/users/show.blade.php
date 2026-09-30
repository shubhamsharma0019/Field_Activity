@extends('layout.app')
@section('title', 'User Details')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user-create.css') }}">
@endpush
@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.users.index') }}">Users</a><span>&rsaquo;</span><span>User Details</span></nav>
    <div class="page-heading"><div><h1>{{ $user->name }}</h1><p>USR{{ str_pad((string) $user->id, 4, '0', STR_PAD_LEFT) }}</p></div><a class="primary-button" href="{{ route('web.users.edit', $user) }}">Edit User</a></div>
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    <section class="user-form-card">
        <h2>Account Details</h2>
        <dl class="user-fields user-detail-fields">
            @foreach (['Full Name' => $user->name, 'Email' => $user->email, 'Mobile' => $user->mobile, 'Role' => str($user->role)->replace('_', ' ')->title(), 'Company' => $user->company?->name, 'Status' => ucfirst($user->status), 'Created' => $user->created_at?->format('d M Y'), 'Last Updated' => $user->updated_at?->format('d M Y')] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value ?: '-' }}</dd></div>
            @endforeach
            @if ($user->role === 'worker')
                <div><dt>Assignments</dt><dd>{{ $user->assignments_count }}</dd></div>
                <div><dt>Submissions</dt><dd>{{ $user->activity_submissions_count }}</dd></div>
            @endif
        </dl>
    </section>
    <div class="save-user-bar"><a class="secondary-button" href="{{ route('web.users.index') }}">Back to Users</a></div>
@endsection
