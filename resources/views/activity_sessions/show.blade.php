@extends('layout.app')

@section('title', 'Activity Session')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/session-show.css') }}">
@endpush

@section('content')
<nav class="breadcrumbs">
    <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
    <span>&rsaquo;</span>
    <a href="{{ route('web.activity-sessions.index') }}">Activity Sessions</a>
    <span>&rsaquo;</span>
    <span>Session Details</span>
</nav>

<div class="page-heading session-show-heading">
    <div>
        <h1>Activity Session Details</h1>
        <p>Select a real activity session from the sessions page to view worker evidence, map location and timeline.</p>
    </div>
    <div class="session-show-actions">
        <a href="{{ route('web.activity-sessions.index') }}">Back to Sessions</a>
        <a href="{{ route('web.activity-sessions.map') }}">View Full Map</a>
    </div>
</div>

<section class="timeline-card">
    <h2>No demo session loaded</h2>
    <p class="empty-state">Demo worker data has been removed from this page.</p>
</section>
@endsection
