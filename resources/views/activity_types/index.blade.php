@extends('layout.app')

@section('title', 'Work Types')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/activity-types.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-types-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-types-merge.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span>
        <span>Work Types</span>
    </nav>

    <div class="page-heading activity-heading">
        <div>
            <h1>Work Types</h1>
            <p>Reusable templates for field work, such as poster install, survey or tracking.</p>
        </div>
        <button class="primary-button" id="open-activity-form" type="button"><span>+</span>Add Work Type</button>
    </div>

    @if (session('status'))
        <div class="company-toast">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="company-toast error">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="activity-stats">
        <article class="activity-stat blue">
            <span><svg class="icon"><use href="#list"/></svg></span>
            <div><h2>Total Work Types</h2><strong>{{ $stats['total'] }}</strong></div>
        </article>
        <article class="activity-stat green">
            <span><svg class="icon"><use href="#check"/></svg></span>
            <div><h2>Active Types</h2><strong>{{ $stats['active'] }}</strong></div>
        </article>
        <article class="activity-stat red">
            <span>x</span>
            <div><h2>Inactive Types</h2><strong>{{ $stats['inactive'] }}</strong></div>
        </article>
        <article class="activity-stat purple">
            <span><svg class="icon"><use href="#clipboard"/></svg></span>
            <div>
                <h2>Most Used Type</h2>
                <strong class="type-name">{{ $stats['most_used_name'] }}</strong>
                <p>({{ $stats['most_used_count'] }} assignments)</p>
            </div>
        </article>
    </div>

    <div class="activity-layout">
        <section class="activity-table-card">
            <div class="activity-filters">
                <label class="company-search">
                    <svg class="icon"><use href="#search"/></svg>
                    <input id="activity-search" type="search" placeholder="Search work types...">
                </label>
                <select id="activity-status">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="company-table-scroll">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Work Type</th>
                            <th>Description</th>
                            <th>Default Mode</th>
                            <th>Status</th>
                            <th>Total Assignments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="activity-rows">
                        @foreach ($activities as $activity)
                            <tr data-status="{{ $activity['status'] }}" data-search="{{ strtolower($activity['name'].' '.$activity['description'].' '.$activity['mode_label'].' '.$activity['status_label']) }}">
                                <td data-label="#">{{ $loop->iteration }}</td>
                                <td data-label="Work Type">
                                    <span class="activity-name">
                                        <span class="activity-icon"><svg class="icon"><use href="#{{ $activity['icon'] }}"/></svg></span>
                                        {{ $activity['name'] }}
                                    </span>
                                </td>
                                <td data-label="Description">{{ $activity['description'] }}</td>
                                <td data-label="Default Mode"><span class="mode-badge {{ $activity['activity_mode'] }}">{{ $activity['mode_label'] }}</span></td>
                                <td data-label="Status"><span class="activity-status {{ $activity['status'] === 'inactive' ? 'inactive' : '' }}">{{ $activity['status_label'] }}</span></td>
                                <td data-label="Total Assignments">{{ $activity['assignments_count'] }}</td>
                                <td data-label="Actions">
                                    <div class="activity-actions">
                                        <button class="activity-action" type="button" data-detail="{{ $activity['name'] }}" data-description="Mode: {{ $activity['mode_label'] }}. Tracking required: {{ $activity['tracking_required'] ? 'Yes' : 'No' }}. Used in {{ $activity['assignments_count'] }} project activities." aria-label="View {{ $activity['name'] }}">
                                            <svg class="icon"><use href="#eye"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p id="activities-empty" class="empty-state" @if ($activities->isNotEmpty()) hidden @endif>No work types found.</p>
            </div>

            <div class="company-footer">
                <label class="page-size">Show <select id="activity-page-size"><option selected>10</option></select> entries</label>
                <span id="activity-count" class="company-count">{{ $activities->count() }} work types</span>
            </div>
        </section>

        <aside id="activity-form-panel" class="activity-form-card" @if (! $errors->any()) hidden @endif>
            <div class="form-heading">
                <h2>Add Work Type</h2>
                <button id="close-activity-form" type="button" aria-label="Close work type form">x</button>
            </div>
            <form id="activity-form" method="POST" action="{{ route('web.activity-types.store') }}">
                @csrf
                <label>Work Type Name <em>*</em>
                    <input id="activity-name" name="name" type="text" required maxlength="100" value="{{ old('name') }}" placeholder="Enter work type name">
                </label>
                <label>Description
                    <textarea id="activity-description" maxlength="500" placeholder="Optional display note for admin planning">{{ old('description') }}</textarea>
                    <small id="activity-description-count">0/500</small>
                </label>
                <fieldset>
                    <legend>Default Work Mode <em>*</em></legend>
                    <label><input type="radio" name="activity_mode" value="single_submission" @checked(old('activity_mode', 'single_submission') === 'single_submission')> Single Submission <small>Worker submits one time with photo and location</small></label>
                    <label><input type="radio" name="activity_mode" value="start_end" @checked(old('activity_mode') === 'start_end')> Start - End <small>Worker starts activity and ends it with time tracking</small></label>
                    <label><input type="radio" name="activity_mode" value="continuous_tracking" @checked(old('activity_mode') === 'continuous_tracking')> Continuous Tracking <small>Real-time location tracking during activity</small></label>
                </fieldset>
                <label class="switch-label">Tracking Required
                    <span><input id="tracking-required" name="tracking_required" value="1" type="checkbox" @checked(old('tracking_required'))><i></i> Required</span>
                </label>
                <label class="switch-label">Status <em>*</em>
                    <input name="status" value="inactive" type="hidden">
                    <span><input id="activity-active" name="status" value="active" type="checkbox" @checked(old('status', 'active') === 'active')><i></i> Active</span>
                </label>
                <label class="switch-label project-work-toggle">Add to Project Now
                    <span><input id="create-project-work" name="create_project_work" value="1" type="checkbox" @checked(old('create_project_work'))><i></i> Create Project Work</span>
                </label>
                <div class="project-work-fields" id="project-work-fields" @if (! old('create_project_work')) hidden @endif>
                    <label>Project <em>*</em>
                        <select name="project_id" id="project-work-project">
                            <option value="" selected disabled>Select project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" data-start-date="{{ $project->start_date?->toDateString() }}" data-end-date="{{ $project->end_date?->toDateString() }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Project Work Name <em>*</em>
                        <input name="project_work_name" value="{{ old('project_work_name') }}" maxlength="150" placeholder="Example: Poster pasting in Sector 18">
                    </label>
                    <label>Target Quantity
                        <input name="target_quantity" value="{{ old('target_quantity') }}" type="number" min="1" placeholder="Example: 50">
                    </label>
                    <label>Expected Minutes
                        <input name="expected_duration_minutes" value="{{ old('expected_duration_minutes') }}" type="number" min="1" placeholder="Optional">
                    </label>
                    <label>Start Date
                        <input name="start_date" value="{{ old('start_date') }}" type="date">
                        <small class="project-date-note"></small>
                    </label>
                    <label>End Date
                        <input name="end_date" value="{{ old('end_date') }}" type="date">
                    </label>
                    <label class="wide-field">Instructions
                        <textarea name="instructions" maxlength="1000" placeholder="Worker instructions...">{{ old('instructions') }}</textarea>
                    </label>
                </div>
                <div class="activity-form-actions">
                    <button id="cancel-activity" class="secondary-button" type="button">Cancel</button>
                    <button class="primary-button" type="submit">Create Work Type</button>
                </div>
            </form>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        window.activityTypesData = @json($activities);
    </script>
    <script src="{{ asset('js/activity-types.js') }}"></script>
@endpush
