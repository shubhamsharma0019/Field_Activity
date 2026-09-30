@extends('layout.app')
@section('title', 'Create New Assignment')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/assignments.css') }}">
@endpush
@section('content')
<nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.assignments.index') }}">Assignments</a><span>&rsaquo;</span><span>Create New Assignment</span></nav>
<div class="page-heading"><div><h1>Create New Assignment</h1><p>Select a project activity and assign it to a field worker.</p></div><a class="back-assignments-button" href="{{ route('web.assignments.index') }}">&larr; Back to Assignments</a></div>
@if ($errors->any())
<div role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
        <aside id="assignment-form" class="assignment-form-card">
            <div class="form-heading"><h2>Create New Assignment</h2><a href="{{ route('web.assignments.index') }}" aria-label="Back to assignments">&times;</a></div>
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
                <div class="assignment-actions"><a class="secondary-button" href="{{ route('web.assignments.index') }}">Cancel</a><button class="primary-button" type="submit">Create Assignment</button></div>
            </form>
        </aside>
@endsection
@push('scripts')
<script src="{{ asset('js/assignments.js') }}"></script>
@endpush
