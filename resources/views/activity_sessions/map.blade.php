@extends('layout.app')

@section('title', 'Full Map Tracking')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/full-map.css') }}">
@endpush

@section('content')
<nav class="breadcrumbs"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><a href="{{ route('web.activity-sessions.index') }}">Activity Sessions</a><span>&rsaquo;</span><span>Full Map</span></nav>
<div class="full-map-heading"><h1>Live Worker Location Map</h1><p>View current locations, submitted photo points and movement routes of field workers.</p></div>

<section class="full-map">
    <div class="map-filter-bar">
        <select id="map-project"><option value="all">All Projects</option>@foreach($projects as $project)<option value="{{ $project }}">{{ $project }}</option>@endforeach</select>
        <select id="map-worker"><option value="all">All Workers</option>@foreach($workers as $worker)<option value="{{ $worker['id'] }}">{{ $worker['name'] }}</option>@endforeach</select>
        <select id="map-status"><option value="all">All Status</option><option value="active">Active Workers</option><option value="break">Idle / Pending</option><option value="offline">Offline</option></select>
        <button type="button" id="refresh-map">Refresh Location</button>
    </div>

    <div class="map-tabs"><button type="button" data-layer="street">Map</button><button type="button" data-layer="satellite">Satellite</button></div>
    <div id="full-live-map"></div>
    <div class="map-empty-state" id="full-map-empty" hidden><strong>No GPS points available</strong><span>Workers appear here after mobile location or photo GPS is received.</span></div>

    <aside class="map-worker-list"><h2>Workers on Map</h2><div id="map-worker-list"></div><p id="map-workers-empty" hidden>No matching workers.</p></aside>
    <aside class="map-popup" id="map-detail"><strong id="popup-worker">Select worker</strong><p id="popup-details">Click a worker or marker to see activity and photo GPS.</p></aside>
    <div class="map-legend-full"><span>Active</span><span>Idle / Pending</span><span>Offline</span><span>CAM: Photo evidence</span></div>
</section>
<div class="company-toast" id="map-toast" hidden></div>
@endsection

@push('scripts')
    <script>
        window.fullMapWorkers = @json($workers);
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/full-map.js') }}"></script>
@endpush
