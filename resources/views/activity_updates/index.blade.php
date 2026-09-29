@extends('layout.app')

@section('title', 'Activity Updates')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/activity-updates.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><span>Activity Updates</span></nav>
    <div class="page-heading updates-heading"><div><h1>Activity Updates</h1><p>View real-time updates from field workers including photos, location and remarks.</p></div><button class="updates-export" type="button" id="export-updates">Export</button></div>

    <div class="updates-stats">
        <article class="updates-stat blue"><span><svg class="icon"><use href="#pulse"/></svg></span><div><h2>Total Updates</h2><strong>{{ $stats['total'] }}</strong><p>+ {{ $stats['week'] }}<br>last 7 days</p></div></article>
        <article class="updates-stat green"><span><svg class="icon"><use href="#users"/></svg></span><div><h2>Active Workers</h2><strong>{{ $stats['active_workers'] }}</strong><p>+ {{ $stats['active_workers_week'] }}<br>last 7 days</p></div></article>
        <article class="updates-stat purple"><span><svg class="icon"><use href="#image"/></svg></span><div><h2>With Photos</h2><strong>{{ $stats['with_photos'] }}</strong><p>+ {{ $stats['with_photos_week'] }}<br>last 7 days</p></div></article>
        <article class="updates-stat orange"><span><svg class="icon"><use href="#list"/></svg></span><div><h2>With Remarks</h2><strong>{{ $stats['with_remarks'] }}</strong><p>+ {{ $stats['with_remarks_week'] }}<br>last 7 days</p></div></article>
    </div>

    <div class="updates-filters">
        <input id="update-search" placeholder="Search by worker name, project, remark...">
        <select id="update-project"><option value="all">Select Project</option>@foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select>
        <select id="update-worker"><option value="all">Select Worker</option>@foreach ($workers as $worker)<option value="{{ $worker->id }}">{{ $worker->name }}</option>@endforeach</select>
        <select id="update-activity-type"><option value="all">Select Activity Type</option>@foreach ($activityTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
        <input id="update-date" type="date">
        <button id="update-reset" type="button">Reset</button>
    </div>

    <section class="updates-table-card">
        <table class="updates-table">
            <thead><tr><th><input type="checkbox" aria-label="Select all updates"></th><th>#</th><th>Photo</th><th>Worker</th><th>Project</th><th>Activity Type</th><th>Remark / Update</th><th>Location</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="update-rows">
                @foreach ($updates as $update)
                    <tr data-search="{{ $update['search'] }}" data-project="{{ $update['project_id'] }}" data-worker="{{ $update['worker_id'] }}" data-activity-type="{{ $update['activity_type_id'] }}" data-date="{{ $update['date'] }}" data-status="{{ $update['status'] }}">
                        <td><input type="checkbox" aria-label="Select update {{ $update['id'] }}"></td>
                        <td>{{ $loop->iteration }}</td>
                        <td><div class="update-photo" style="background-image:url('{{ $update['photo'] }}')"></div></td>
                        <td><div class="update-worker"><span class="update-avatar">{{ $update['worker_initial'] }}</span><div><strong>{{ $update['worker'] }}</strong><small>{{ $update['worker_code'] }}</small></div></div></td>
                        <td>{{ $update['project'] }}</td>
                        <td><span class="update-type {{ $loop->even ? 'purple' : '' }}">{{ $update['activity_type'] }}</span></td>
                        <td>{{ $update['remark'] }}</td>
                        <td>{{ $update['location'] }}<small>{{ $update['gps'] }}{{ $update['accuracy'] ? ' | '.$update['accuracy'].'m' : '' }}</small></td>
                        <td>{{ $update['time'] }}</td>
                        <td><span class="update-status {{ $update['status'] }}">{{ $update['status_label'] }}</span></td>
                        <td><div class="update-actions"><button type="button" data-detail="Activity Update" data-description="{{ $update['worker'] }} - {{ $update['remark'] }} Location: {{ $update['location'] }}" aria-label="View update"><svg class="icon"><use href="#eye"/></svg></button></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p id="updates-empty" class="empty-state" @if (count($updates) > 0) hidden @endif>No activity updates found.</p>
        <div class="updates-footer"><span id="updates-count">Showing {{ count($updates) }} updates</span><div><button class="active">1</button></div></div>
    </section>
    <div id="update-toast" class="company-toast" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/activity-updates.js') }}"></script>
@endpush
