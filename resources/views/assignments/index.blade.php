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
            <button class="primary-button" id="assignment-form-button" type="button"><span>+</span>Create New Assignment</button>
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
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $assignment['title'] }}</strong><small>{{ $assignment['code'] }}</small></td>
                                <td>{{ $assignment['project'] }}</td>
                                <td><strong>{{ $assignment['worker'] }}</strong><small>{{ $assignment['worker_code'] }}</small></td>
                                <td><span class="activity-badge">{{ $assignment['activity_type'] }}</span></td>
                                <td><span class="mode-badge">{{ $assignment['mode'] }}</span></td>
                                <td>{{ $assignment['due_date'] }}</td>
                                <td><span class="assignment-status {{ str_replace('_', '-', $assignment['status']) }}">{{ $assignment['status_label'] }}</span></td>
                                <td><span class="assignment-progress"><i><b style="width: {{ $assignment['progress'] }}%"></b></i>{{ $assignment['completed'] }}/{{ $assignment['target'] ?: 0 }}</span></td>
                                <td class="assignment-actions-cell">
                                    <button class="assignment-action" type="button" data-detail="{{ $assignment['title'] }}" data-description="Project: {{ $assignment['project'] }}. Worker: {{ $assignment['worker'] }}. Progress: {{ $assignment['completed'] }}/{{ $assignment['target'] ?: 0 }}." aria-label="View {{ $assignment['title'] }}"><svg class="icon"><use href="#eye"/></svg></button>
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

        <aside id="assignment-form" class="assignment-form-card">
            <div class="form-heading"><h2>Create New Assignment</h2><button id="close-assignment-form" type="button" aria-label="Close assignment form">x</button></div>
            <form id="new-assignment-form" method="POST" action="{{ route('web.assignments.store') }}">
                @csrf
                <label>Company <em>*</em><select id="form-company"><option value="all">All Companies / Internal</option><option value="internal">Internal Project</option>@foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select></label>
                <label>Project <em>*</em><select id="form-project" name="project_id" required><option value="" selected disabled>Select project</option>@foreach ($projects as $project)<option value="{{ $project->id }}" data-company="{{ $project->company_id ?? 'internal' }}">{{ $project->name }}</option>@endforeach</select></label>
                <label>Project Activity <em>*</em><select id="form-project-activity" name="project_activity_id" required><option value="" selected disabled>Select project activity</option>@foreach ($activities as $activity)<option value="{{ $activity->id }}" data-project="{{ $activity->project_id }}" data-mode="{{ $activity->activityType?->activity_mode }}" data-tracking="{{ $activity->activityType?->tracking_required ? '1' : '0' }}" data-target="{{ $activity->target_quantity }}">{{ $activity->name }} - {{ $activity->activityType?->name }}</option>@endforeach</select></label>
                <label>Activity Mode <em>*</em><input id="form-activity-mode" value="Select project activity" readonly></label>
                <label>Assign to Worker <em>*</em><select id="form-worker" name="worker_id" required><option value="" selected disabled>Select worker</option>@foreach ($workers as $worker)<option value="{{ $worker->id }}" data-company="{{ $worker->company_id ?? 'internal' }}">{{ $worker->name }}{{ $worker->mobile ? ' - '.$worker->mobile : '' }}</option>@endforeach</select></label>
                <label>Target Quantity<input id="form-target" name="target_quantity" type="number" min="1" placeholder="Use project activity target"></label>
                <label>Assigned Date <em>*</em><input name="assigned_date" required type="date" value="{{ now()->toDateString() }}"></label>
                <label class="tracking-field"><input name="tracking_required" value="0" type="hidden"><span><input id="form-tracking" name="tracking_required" value="1" type="checkbox"> Tracking Required</span></label>
                <label>Description<textarea id="assignment-description" maxlength="500" placeholder="Visible note only; assignment instructions come from project activity."></textarea><small id="assignment-description-count">0/500</small></label>
                <div class="assignment-actions"><button class="secondary-button" id="cancel-assignment" type="button">Cancel</button><button class="primary-button" type="submit">Create Assignment</button></div>
            </form>
        </aside>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/assignments.js') }}"></script>
@endpush
