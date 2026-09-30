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
        <a class="primary-button" href="{{ route('web.activity-types.create') }}"><span>+</span>Add New Activity Type</a>
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

    <div class="activity-layout activity-list-only">
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
                                <td data-label="Number">{{ $loop->iteration }}</td>
                                <td data-label="Activity Type">
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
                <p id="activities-empty" class="empty-state" @if ($activities->isNotEmpty()) hidden @endif>No activity types found.</p>
            </div>

            <div class="company-footer">
                <label class="page-size">Show <select id="activity-page-size"><option selected>10</option></select> entries</label>
                <span id="activity-count" class="company-count">{{ $activities->count() }} activity types</span>
            </div>
        </section>


    </div>
@endsection

@push('scripts')
    <script>
        window.activityTypesData = @json($activities);
    </script>
    <script src="{{ asset('js/activity-types.js') }}"></script>
@endpush
