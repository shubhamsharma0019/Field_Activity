@extends('layout.app')

@section('title', 'Live Worker Tracking')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/activity-sessions.css') }}">
@endpush

@section('content')
<nav class="breadcrumbs"><a href="{{ route('web.activity-sessions.index') }}">Activity Sessions</a><span>&rsaquo;</span><span>Live Tracking</span></nav>

<div class="page-heading tracking-heading"><div><h1>Live Worker Tracking</h1><p>Monitor real-time location, activity sessions and field movement of your team.</p></div><div class="tracking-tools"><a class="map-button" href="{{ route('web.activity-sessions.map') }}">View on Full Map</a></div></div>

<div class="tracking-stats">
    <article class="tracking-stat blue"><span><svg class="icon"><use href="#users"/></svg></span><div><h2>Total Workers</h2><strong>{{ $stats['total'] }}</strong><p>{{ $stats['active'] }} active now</p></div></article>
    <article class="tracking-stat green"><span><svg class="icon"><use href="#pin"/></svg></span><div><h2>Active Now</h2><strong>{{ $stats['active'] }}</strong><p>{{ $stats['active_percent'] }}%</p></div></article>
    <article class="tracking-stat orange"><span><svg class="icon"><use href="#clock"/></svg></span><div><h2>Idle / Pending</h2><strong>{{ $stats['break'] }}</strong><p>Needs attention</p></div></article>
    <article class="tracking-stat purple"><span>×</span><div><h2>Offline</h2><strong>{{ $stats['offline'] }}</strong><p>No recent GPS</p></div></article>
</div>

<div class="tracking-layout">
    <aside class="tracking-list">
        <h2>Workers (<span id="worker-count">{{ count($workers) }}</span>)</h2>
        <div class="worker-filter"><input id="worker-search" placeholder="Search worker..."><select id="worker-status"><option value="all">All Status</option><option value="active">Active</option><option value="break">Idle</option><option value="offline">Offline</option></select></div>
        <div id="worker-list">
            @foreach($workers as $worker)
                <button class="worker-row {{ $loop->first ? 'active' : '' }} {{ $worker['status'] }}" type="button" data-worker-id="{{ $worker['id'] }}">
                    <span>{{ $worker['initials'] }}</span>
                    <div><strong>{{ $worker['name'] }}</strong><small>{{ $worker['code'] }} · {{ $worker['status_label'] }}<br>{{ $worker['assignment'] }}</small></div>
                    <i></i>
                </button>
            @endforeach
        </div>
        <p id="workers-empty" class="tracking-empty" @if(count($workers) > 0) hidden @endif>No workers found.</p>
    </aside>

    <section class="map-panel">
        <div class="map-tabs"><button type="button" data-layer="street">Map</button><button type="button" data-layer="satellite">Satellite</button></div>
        <div id="live-map"></div>
        <div class="map-empty-state" id="map-empty-state" hidden><strong>No live GPS points</strong><span>Workers will appear on the map after the mobile app sends location.</span></div>
        <div class="map-legend"><span>Active</span><span>Idle / Pending</span><span>Offline</span></div>
    </section>

    <aside class="worker-detail">
        <h2>Worker Details</h2>
        <div class="detail-profile"><span id="detail-initials">--</span><div><strong id="detail-name">Select worker</strong><small id="detail-meta">N/A</small></div></div>
        <section class="detail-section-session"><h3>Current Activity</h3><div class="detail-activity"><img id="detail-image" src="{{ asset('images/admin-construction.jpg') }}" alt="Worker evidence"><div><strong id="detail-assignment">N/A</strong><p id="detail-activity-meta">N/A</p></div></div></section>
        <section class="detail-section-session"><h3>Latest Photo Evidence</h3><div class="route-list" id="detail-photo">No photo submitted yet.</div></section>
        <section class="detail-section-session"><h3>Location</h3><div class="route-list" id="detail-location">N/A</div></section>
    </aside>
</div>
@endsection

@push('scripts')
    <script>
        window.liveWorkers = @json($workers);
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/live-tracking.js') }}"></script>
@endpush
