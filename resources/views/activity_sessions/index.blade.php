@extends('layout.app')

@section('title', 'Activity Sessions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/session-list.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><span>Activity Sessions</span></nav>

    <div class="page-heading session-heading">
        <div><h1>Activity Sessions</h1><p>Monitor start-end field activities, working duration and review status.</p></div>
        <div class="session-links"><a href="{{ route('web.activity-sessions.live') }}">Live Worker Tracking</a><a href="{{ route('web.activity-sessions.map') }}">View Full Map</a></div>
    </div>

    @if (session('status'))
        <div class="company-toast">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="company-toast error">{{ $errors->first() }}</div>
    @endif

    <div class="session-stats">
        <article class="session-stat blue"><span><svg class="icon"><use href="#clock"/></svg></span><div><h2>Total Sessions</h2><strong>{{ $stats['total'] }}</strong><p>+ {{ $stats['total_month'] }} this month</p></div></article>
        <article class="session-stat green"><span><svg class="icon"><use href="#arrow"/></svg></span><div><h2>Active Now</h2><strong>{{ $stats['active'] }}</strong><p>+ {{ $stats['active_month'] }} this month</p></div></article>
        <article class="session-stat orange"><span><svg class="icon"><use href="#clock"/></svg></span><div><h2>Pending Review</h2><strong>{{ $stats['pending'] }}</strong><p>+ {{ $stats['pending_month'] }} this month</p></div></article>
        <article class="session-stat purple"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Completed</h2><strong>{{ $stats['completed'] }}</strong><p>+ {{ $stats['completed_month'] }} this month</p></div></article>
        <article class="session-stat blue"><span>x</span><div><h2>Rejected</h2><strong>{{ $stats['rejected'] }}</strong><p>+ {{ $stats['rejected_month'] }} this month</p></div></article>
    </div>

    <div class="session-layout session-list-only">
        <section class="sessions-main">
            <div class="session-filters">
                <input id="session-search" placeholder="Search by worker, assignment or session ID...">
                <select id="session-company"><option value="all">All Companies</option><option value="internal">Internal</option>@foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select>
                <select id="session-project"><option value="all">All Projects</option>@foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select>
                <select id="session-status"><option value="all">All Status</option><option value="in_progress">Active</option><option value="pending_approval">Pending Review</option><option value="approved">Completed</option><option value="rejected">Rejected</option></select>
                <input id="session-date" type="date">
                <button id="session-reset" type="button">Reset</button>
            </div>
            <h2 id="session-count" style="padding:0 14px;font-size:14px">Activity Sessions ({{ count($sessions) }})</h2>
            <table class="session-table">
                <thead><tr><th>#</th><th>Session ID</th><th>Worker</th><th>Assignment</th><th>Project</th><th>Start Time</th><th>Duration</th><th>End Time</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="session-rows">
                    @foreach ($sessions as $session)
                        <tr data-id="{{ $session['id'] }}"
                            data-code="{{ $session['code'] }}"
                            data-worker="{{ $session['worker'] }}"
                            data-worker-code="{{ $session['worker_code'] }}"
                            data-worker-mobile="{{ $session['worker_mobile'] }}"
                            data-worker-initials="{{ $session['worker_initials'] }}"
                            data-assignment="{{ $session['assignment'] }}"
                            data-project="{{ $session['project'] }}"
                            data-project-id="{{ $session['project_id'] }}"
                            data-company="{{ $session['company_id'] ?? 'internal' }}"
                            data-company-name="{{ $session['company'] }}"
                            data-mode="{{ $session['activity_mode'] }}"
                            data-start-time="{{ $session['start_time'] }}"
                            data-start-date="{{ $session['start_date'] }}"
                            data-end-time="{{ $session['end_time'] }}"
                            data-duration="{{ $session['duration'] }}"
                            data-status="{{ $session['status'] }}"
                            data-status-label="{{ $session['status_label'] }}"
                            data-location="{{ $session['location'] }}"
                            data-start-gps="{{ $session['start_gps'] }}"
                            data-end-gps="{{ $session['end_gps'] }}"
                            data-start-image="{{ $session['start_image_url'] }}"
                            data-end-image="{{ $session['end_image_url'] }}"
                            data-remark="{{ $session['remark'] }}"
                            data-reviewer="{{ $session['reviewer'] }}"
                            data-reviewed-at="{{ $session['reviewed_at'] }}"
                            data-rejection-reason="{{ $session['rejection_reason'] }}"
                            data-search="{{ $session['search'] }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $session['code'] }}</td>
                            <td><b>{{ $session['worker'] }}</b><small>{{ $session['worker_code'] }}</small></td>
                            <td>{{ $session['assignment'] }}</td>
                            <td>{{ $session['project'] }}</td>
                            <td>{{ $session['start_time'] }}</td>
                            <td>{{ $session['duration'] }}</td>
                            <td>{{ $session['end_time'] }}</td>
                            <td><span class="session-status {{ $session['status'] }}">{{ $session['status_label'] }}</span></td>
                            <td><a class="session-view" href="{{ route('web.activity-sessions.show', $session['id']) }}" aria-label="View {{ $session['code'] }}"><svg class="icon"><use href="#eye"/></svg></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p id="sessions-empty" class="empty-state" @if (count($sessions) > 0) hidden @endif>No activity sessions found.</p>
        </section>


    </div>
@endsection

@push('scripts')
    <script>window.sessionReviewBaseUrl = @json(url('/activity-sessions'));</script>
    <script src="{{ asset('js/activity-sessions.js') }}"></script>
@endpush
