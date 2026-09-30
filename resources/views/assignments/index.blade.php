@extends('layout.app')

@section('title', 'Assignments')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/assignments.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span>
        <span>Assignments</span>
    </nav>

    <div class="page-heading assignments-heading">
        <div>
            <h1>Assignments</h1>
            <p>Create, manage and track field assignments for workers.</p>
        </div>
        <div class="assignment-heading-actions">
            <a class="secondary-button project-activity-cta" href="{{ route('web.project-activities.create') }}">Add Project Activity</a>
            <a class="primary-button" href="{{ route('web.assignments.create') }}"><span>+</span>Create New Assignment</a>
        </div>
    </div>

    @if (session('status'))
        <div class="company-toast">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="company-toast error">{{ $errors->first() }}</div>
    @endif

    <div class="assignment-stats">
        <article class="assignment-stat blue"><span><svg class="icon"><use href="#clipboard"/></svg></span><div><h2>Total Assignments</h2><strong>{{ $stats['total'] }}</strong><p>+ {{ $stats['total_month'] }} this month</p></div></article>
        <article class="assignment-stat green"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Active Assignments</h2><strong>{{ $stats['active'] }}</strong><p>+ {{ $stats['active_month'] }} this month</p></div></article>
        <article class="assignment-stat orange"><span><svg class="icon"><use href="#clock"/></svg></span><div><h2>Pending Review</h2><strong>{{ $stats['pending_review'] }}</strong><p>+ {{ $stats['pending_review_month'] }} this month</p></div></article>
        <article class="assignment-stat purple"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Completed</h2><strong>{{ $stats['completed'] }}</strong><p>+ {{ $stats['completed_month'] }} this month</p></div></article>
    </div>

    <div class="assignment-layout">
        <section class="assignment-table-card">
            <div class="assignment-filters">
                <label class="assignment-search"><svg class="icon"><use href="#search"/></svg><input id="assignment-search" placeholder="Search assignments by title, project, worker..."></label>
                <select id="assignment-company"><option value="all">All Companies</option><option value="internal">Internal</option>@foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select>
                <select id="assignment-project"><option value="all">All Projects</option>@foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select>
                <select id="assignment-activity-type"><option value="all">All Activity Types</option>@foreach ($activityTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                <select id="assignment-status"><option value="all">All Status</option><option value="assigned">Assigned</option><option value="in_progress">In Progress</option><option value="pending_approval">Pending Review</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>
                <button id="assignment-reset" type="button">Reset</button>
            </div>
            <div class="company-table-scroll">
                <table class="assignment-table">
                    <thead>
                        <tr><th>#</th><th>Assignment Title</th><th>Project</th><th>Worker</th><th>Activity Type</th><th>Mode</th><th>Due Date</th><th>Status</th><th>Progress</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="assignment-rows">
                        @foreach ($assignments as $assignment)
                            <tr data-search="{{ $assignment['search'] }}" data-company="{{ $assignment['company_id'] ?? 'internal' }}" data-project="{{ $assignment['project_id'] }}" data-activity-type="{{ $assignment['activity_type_id'] }}" data-status="{{ $assignment['status'] }}">
                                <td data-label="Number">{{ $loop->iteration }}</td>
                                <td data-label="Assignment"><strong>{{ $assignment['title'] }}</strong><small>{{ $assignment['code'] }}</small></td>
                                <td data-label="Project">{{ $assignment['project'] }}</td>
                                <td data-label="Worker"><strong>{{ $assignment['worker'] }}</strong><small>{{ $assignment['worker_code'] }}</small></td>
                                <td data-label="Activity Type"><span class="activity-badge">{{ $assignment['activity_type'] }}</span></td>
                                <td data-label="Mode"><span class="mode-badge">{{ $assignment['mode'] }}</span></td>
                                <td data-label="Due Date">{{ $assignment['due_date'] }}</td>
                                <td data-label="Status"><span class="assignment-status {{ str_replace('_', '-', $assignment['status']) }}">{{ $assignment['status_label'] }}</span></td>
                                <td data-label="Progress"><span class="assignment-progress"><i><b style="width: {{ $assignment['progress'] }}%"></b></i>{{ $assignment['completed'] }}/{{ $assignment['target'] ?: 0 }}</span></td>
                                <td class="assignment-actions-cell" data-label="Actions">
                                    <a class="assignment-action" href="{{ route('web.assignments.show', $assignment['id']) }}" aria-label="View {{ $assignment['title'] }}"><svg class="icon"><use href="#eye"/></svg></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p id="assignments-empty" class="empty-state" @if (count($assignments) > 0) hidden @endif>No assignments found.</p>
            </div>
            <div class="company-footer">
                <label class="page-size">Show <select><option>10</option></select> entries</label>
                <span id="assignment-count" class="company-count">{{ count($assignments) }} assignments</span>
            </div>
        </section>


    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/assignments.js') }}"></script>
@endpush
