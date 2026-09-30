@extends('layout.app')

@section('title', 'Reports')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('content')
<nav class="breadcrumbs">
    <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
    <span>&rsaquo;</span>
    <span>Reports</span>
</nav>

<div class="page-heading reports-heading">
    <div>
        <h1>Reports</h1>
        <p>Analyze field activities, worker performance and project progress.</p>
    </div>
    <div class="report-tools">
        <input id="report-from" type="date" value="{{ $periodStart->toDateString() }}" aria-label="Report start date">
        <input id="report-to" type="date" value="{{ $periodEnd->toDateString() }}" aria-label="Report end date">
        <select id="report-company">
            <option value="all">All Companies</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
            @endforeach
        </select>
        <select id="report-project">
            <option value="all">All Projects</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}" data-company="{{ $project->company_id ?? 'internal' }}">{{ $project->name }}</option>
            @endforeach
        </select>
        <select id="report-worker">
            <option value="all">All Workers</option>
            @foreach($workers as $worker)
                <option value="{{ $worker['id'] }}">{{ $worker['name'] }}</option>
            @endforeach
        </select>
        <button type="button" id="download-report">Download Report</button>
    </div>
</div>

<div class="report-stats">
    <article class="report-stat blue"><span><svg class="icon"><use href="#list"/></svg></span><div><h2>Total Activities</h2><strong data-stat="total">0</strong><p data-stat-note="month_total">0 this month</p></div></article>
    <article class="report-stat green"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Completed</h2><strong data-stat="completed">0</strong><p data-stat-note="completed_percent">0%</p></div></article>
    <article class="report-stat orange"><span><svg class="icon"><use href="#clock"/></svg></span><div><h2>In Progress</h2><strong data-stat="in_progress">0</strong><p data-stat-note="in_progress_percent">0%</p></div></article>
    <article class="report-stat purple"><span>!</span><div><h2>Pending</h2><strong data-stat="pending">0</strong><p data-stat-note="pending_percent">0%</p></div></article>
    <article class="report-stat red"><span>x</span><div><h2>Rejected</h2><strong data-stat="rejected">0</strong><p data-stat-note="rejected_percent">0%</p></div></article>
</div>

<div class="report-grid-top">
    <section class="report-panel">
        <h2>Activity Completion Trend</h2>
        <div class="trend-chart" id="trend-chart"></div>
        <div class="chart-key"><span>Completed</span><span>In Progress</span><span>Pending</span><span>Rejected</span></div>
    </section>
    <section class="report-panel">
        <h2>Work Type Distribution</h2>
        <div class="donut-report" id="activity-donut"><div><strong id="donut-total">0</strong><small>Total</small></div></div>
        <div class="type-list" id="activity-type-list"></div>
    </section>
    <section class="report-panel">
        <h2>Top Workers by Activity</h2>
        <table class="worker-rank">
            <thead><tr><th>#</th><th>Worker</th><th>Completed</th></tr></thead>
            <tbody id="top-workers-body"></tbody>
        </table>
    </section>
</div>

<div class="report-grid-middle">
    <section class="report-panel">
        <h2>Project Wise Performance</h2>
        <div id="project-performance"></div>
    </section>
    <section class="report-panel">
        <h2>Location Wise Activity Count</h2>
        <div class="location-bars" id="location-bars"></div>
    </section>
</div>

<div class="report-grid-bottom">
    <section class="report-panel">
        <h2>Recent Field Activity</h2>
        <div class="submission-table-wrap">
            <table class="submission-table">
                <thead><tr><th>#</th><th>Type</th><th>Activity</th><th>Worker</th><th>Project</th><th>Location</th><th>Date</th><th>Status</th></tr></thead>
                <tbody id="recent-activity-body"></tbody>
            </table>
        </div>
    </section>
    <section class="report-panel">
        <h2>Activity Heat Map</h2>
        <div class="heat-map" id="heat-map"></div>
    </section>
</div>
<div id="report-toast" class="company-toast" hidden></div>
@endsection

@push('scripts')
<script>
window.reportRows = @json($reportRows);
</script>
<script src="{{ asset('js/reports.js') }}"></script>
@endpush
