<div>
    <!-- Well begun is half done. - Aristotle -->
</div>
@extends('layout.app')

@section('title', 'Full Map Tracking')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/full-map.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><a href="{{ route('web.activity-sessions.index') }}">Activity Sessions</a><span>&rsaquo;</span><span>Full Map</span></nav>
    <div class="full-map-heading"><h1>Live Worker Location Map</h1><p>View current locations and movement routes of active field workers.</p></div>

    <section class="full-map">
        <div class="map-filter-bar"><select><option>All Projects</option></select><select><option>All Workers</option></select><select><option>Active Workers</option></select><button type="button" id="refresh-map">&#8635; Refresh Location</button></div>
        <div class="map-route"></div>
        <button class="location-marker marker-ramesh" type="button" data-worker="Ramesh Kumar" data-activity="Poster Installation" data-location="Karol Bagh, New Delhi"><span>RK</span></button>
        <button class="location-marker green marker-priya" type="button" data-worker="Priya Sharma" data-activity="Market Survey" data-location="Connaught Place, New Delhi"><span>PS</span></button>
        <button class="location-marker orange marker-amit" type="button" data-worker="Amit Singh" data-activity="On Break" data-location="Lajpat Nagar, New Delhi"><span>AS</span></button>
        <button class="location-marker green marker-neha" type="button" data-worker="Neha Verma" data-activity="Shop Visit" data-location="Saket, New Delhi"><span>NV</span></button>
        <button class="location-marker red marker-suresh" type="button" data-worker="Suresh Patel" data-activity="Offline" data-location="Last location: India Gate"><span>SP</span></button>
        <div class="map-popup"><strong id="popup-worker">Ramesh Kumar</strong><p id="popup-details">Poster Installation<br>Karol Bagh, New Delhi<br>Last updated: just now</p></div>
        <aside class="map-worker-list"><h2>Workers on Map</h2><button class="map-worker-button" type="button" data-target="Ramesh Kumar"><span>RK</span><div><strong>Ramesh Kumar</strong><small>Active · Poster Installation</small></div></button><button class="map-worker-button" type="button" data-target="Priya Sharma"><span>PS</span><div><strong>Priya Sharma</strong><small>Active · Market Survey</small></div></button><button class="map-worker-button" type="button" data-target="Amit Singh"><span>AS</span><div><strong>Amit Singh</strong><small>On Break</small></div></button><button class="map-worker-button" type="button" data-target="Neha Verma"><span>NV</span><div><strong>Neha Verma</strong><small>Active · Shop Visit</small></div></button></aside>
        <div class="map-legend-full"><span>Active Workers</span><span>On Break</span><span>Offline</span><span>Dashed line: Movement route</span></div>
    </section>
    <div class="company-toast" id="map-toast" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/full-map.js') }}"></script>
@endpush
