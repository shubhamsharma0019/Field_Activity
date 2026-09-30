@extends('layout.app')

@section('title', 'Activity Updates')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/activity-updates.css') }}">
@endpush

@section('content')
    @php
        $updateGroups = collect($updates)
            ->groupBy(fn ($update) => implode('|', [
                $update['worker_id'] ?? 'worker',
                $update['project_id'] ?? 'project',
                $update['activity_type_id'] ?? 'type',
            ]))
            ->map(function ($items, $key) {
                $sorted = $items->sortByDesc(fn ($item) => $item['sort_time'] ?? $item['date'].' '.$item['time'])->values();
                $latest = $sorted->first();

                $latest['group_id'] = md5($key);
                $latest['items'] = $sorted->values();
                $latest['photo_count'] = $sorted->filter(fn ($item) => filled($item['photo'] ?? null))->count();
                $latest['update_count'] = $sorted->count();
                $latest['search'] = $sorted->map(fn ($item) => $item['search'] ?? '')->implode(' ');

                return $latest;
            })
            ->values();

        $groupPayload = $updateGroups->mapWithKeys(fn ($group) => [$group['group_id'] => $group['items']]);
    @endphp

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
        <select id="update-activity-type"><option value="all">Select Work Type</option>@foreach ($activityTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
        <select id="update-status"><option value="all">All Status</option><option value="in_progress">Active</option><option value="pending_approval">Pending Review</option><option value="pending">Pending</option><option value="approved">Completed</option><option value="rejected">Rejected</option></select>
        <input id="update-date" type="date">
        <button id="update-reset" type="button">Reset</button>
    </div>

    <section class="updates-table-card">
        <table class="updates-table">
            <thead><tr><th><input id="updates-select-all" type="checkbox" aria-label="Select all updates"></th><th>#</th><th>Photo</th><th>Worker</th><th>Project</th><th>Work Type</th><th>Remark / Update</th><th>Location</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="update-rows">
                @foreach ($updateGroups as $update)
                    <tr data-group-id="{{ $update['group_id'] }}" data-id="{{ $update['id'] }}" data-session="{{ $update['session_code'] }}" data-photo="{{ $update['photo'] }}" data-search="{{ $update['search'] }}" data-project="{{ $update['project_id'] }}" data-project-name="{{ $update['project'] }}" data-worker="{{ $update['worker_id'] }}" data-worker-name="{{ $update['worker'] }}" data-worker-code="{{ $update['worker_code'] }}" data-activity-type="{{ $update['activity_type_id'] }}" data-activity-type-name="{{ $update['activity_type'] }}" data-remark="{{ $update['remark'] }}" data-location="{{ $update['location'] }}" data-gps="{{ $update['gps'] }}" data-latitude="{{ $update['latitude'] }}" data-longitude="{{ $update['longitude'] }}" data-accuracy="{{ $update['accuracy'] }}" data-date="{{ $update['date'] }}" data-time="{{ $update['time'] }}" data-status="{{ $update['status'] }}" data-status-label="{{ $update['status_label'] }}">
                        <td><input class="update-checkbox" type="checkbox" aria-label="Select update group {{ $loop->iteration }}"></td>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="update-photo-wrap">
                                <div class="update-photo" style="background-image:url('{{ $update['photo'] }}')"></div>
                                @if ($update['photo_count'] > 1)
                                    <span class="update-photo-count">+{{ $update['photo_count'] - 1 }}</span>
                                @endif
                            </div>
                        </td>
                        <td><div class="update-worker"><span class="update-avatar">{{ $update['worker_initial'] }}</span><div><strong>{{ $update['worker'] }}</strong><small>{{ $update['worker_code'] }}</small></div></div></td>
                        <td>{{ $update['project'] }}</td>
                        <td><span class="update-type {{ $loop->even ? 'purple' : '' }}">{{ $update['activity_type'] }}</span></td>
                        <td>{{ $update['update_count'] }} update{{ $update['update_count'] > 1 ? 's' : '' }} / {{ $update['photo_count'] }} photo{{ $update['photo_count'] > 1 ? 's' : '' }}<small>{{ $update['remark'] }}</small></td>
                        <td>{{ $update['location'] }}<small>{{ $update['gps'] }}{{ $update['accuracy'] ? ' | '.$update['accuracy'].'m' : '' }}</small></td>
                        <td>{{ $update['time'] }}</td>
                        <td><span class="update-status {{ $update['status'] }}">{{ $update['status_label'] }}</span></td>
                        <td><div class="update-actions"><button class="view-update" type="button" aria-label="View update"><svg class="icon"><use href="#eye"/></svg></button></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p id="updates-empty" class="empty-state" @if ($updateGroups->count() > 0) hidden @endif>No activity updates found.</p>
        <div class="updates-footer"><span id="updates-count">Showing {{ $updateGroups->count() }} update groups</span><div><button class="active">1</button></div></div>
    </section>
    <aside class="update-detail-panel" id="update-detail-panel" hidden>
        <div class="update-detail-head"><h2>Update Details</h2><button id="close-update-detail" type="button" aria-label="Close update details">x</button></div>
        <div class="update-detail-photo" id="detail-update-photo"></div>
        <section><h3>Worker Update</h3><table><tr><td>Worker</td><td id="detail-update-worker">N/A</td></tr><tr><td>Session</td><td id="detail-update-session">N/A</td></tr><tr><td>Project</td><td id="detail-update-project">N/A</td></tr><tr><td>Work Type</td><td id="detail-update-type">N/A</td></tr></table></section>
        <section><h3>Location & Time</h3><table><tr><td>Location</td><td id="detail-update-location">N/A</td></tr><tr><td>GPS</td><td id="detail-update-gps">N/A</td></tr><tr><td>Time</td><td id="detail-update-time">N/A</td></tr><tr><td>Status</td><td id="detail-update-status">N/A</td></tr></table><a class="update-map-link" id="detail-update-map" href="#" target="_blank" rel="noopener" hidden>Open Location on Map</a></section>
        <section><h3>Remark</h3><p id="detail-update-remark">No remark added.</p></section>
    </aside>
    <div id="update-toast" class="company-toast" hidden></div>
@endsection

@push('scripts')
    <script>
        window.activityUpdateGroups = @json($groupPayload);
    </script>
    <script src="{{ asset('js/activity-updates.js') }}"></script>
@endpush
