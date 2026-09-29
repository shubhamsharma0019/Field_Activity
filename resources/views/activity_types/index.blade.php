@extends('layout.app')

@section('title', 'Activity Types')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/activity-types.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-types-fix.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span>
        <span>Activity Types</span>
    </nav>

    <div class="page-heading activity-heading">
        <div>
            <h1>Activity Types</h1>
            <p>Create and manage different types of field activities for assignments.</p>
        </div>
        <button class="primary-button" id="open-activity-form" type="button"><span>+</span>Add New Activity Type</button>
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
            <div><h2>Total Activity Types</h2><strong>{{ $stats['total'] }}</strong></div>
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
                    <input id="activity-search" type="search" placeholder="Search activity types...">
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
                            <th>Activity Type</th>
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
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="activity-name">
                                        <span class="activity-icon"><svg class="icon"><use href="#{{ $activity['icon'] }}"/></svg></span>
                                        {{ $activity['name'] }}
                                    </span>
                                </td>
                                <td>{{ $activity['description'] }}</td>
                                <td><span class="mode-badge {{ $activity['activity_mode'] }}">{{ $activity['mode_label'] }}</span></td>
                                <td><span class="activity-status {{ $activity['status'] === 'inactive' ? 'inactive' : '' }}">{{ $activity['status_label'] }}</span></td>
                                <td>{{ $activity['assignments_count'] }}</td>
                                <td>
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
                <p id="activities-empty" class="empty-state" @if ($activities->isNotEmpty()) hidden @endif>No activity types found.</p>
            </div>

            <div class="company-footer">
                <label class="page-size">Show <select id="activity-page-size"><option selected>10</option></select> entries</label>
                <span id="activity-count" class="company-count">{{ $activities->count() }} activity types</span>
            </div>
        </section>

        <aside id="activity-form-panel" class="activity-form-card">
            <div class="form-heading">
                <h2>Add New Activity Type</h2>
                <button id="close-activity-form" type="button" aria-label="Close activity type form">x</button>
            </div>
            <form id="activity-form" method="POST" action="{{ route('web.activity-types.store') }}">
                @csrf
                <label>Activity Name <em>*</em>
                    <input id="activity-name" name="name" type="text" required maxlength="100" value="{{ old('name') }}" placeholder="Enter activity type name">
                </label>
                <label>Description
                    <textarea id="activity-description" maxlength="500" placeholder="Optional display note for admin planning">{{ old('description') }}</textarea>
                    <small id="activity-description-count">0/500</small>
                </label>
                <fieldset>
                    <legend>Default Activity Mode <em>*</em></legend>
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
                <div class="activity-form-actions">
                    <button id="cancel-activity" class="secondary-button" type="button">Cancel</button>
                    <button class="primary-button" type="submit">Create Activity Type</button>
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
