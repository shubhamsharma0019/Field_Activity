@extends('layout.app')

@section('title', 'Reports')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('content')
<nav class="breadcrumbs"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><span>Reports</span></nav>
<div class="page-heading reports-heading"><div><h1>Reports</h1><p>Analyze field activities, worker performance and project progress.</p></div><div class="report-tools"><select id="report-period"><option>{{ now()->startOfMonth()->format('d M Y') }} - {{ now()->endOfMonth()->format('d M Y') }}</option></select><select id="report-company"><option value="all">All Companies</option>@foreach($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select><select id="report-project"><option value="all">All Projects</option>@foreach($projects as $project)<option value="{{ $project->id }}" data-company="{{ $project->company_id ?? 'internal' }}">{{ $project->name }}</option>@endforeach</select><button type="button" id="download-report">Download Report</button></div></div>

<div class="report-stats">
    <article class="report-stat blue"><span><svg class="icon"><use href="#list"/></svg></span><div><h2>Total Assignments</h2><strong>{{ $stats['total'] }}</strong><p>+ {{ $stats['month_total'] }} this month</p></div></article>
    <article class="report-stat green"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Completed</h2><strong>{{ $stats['completed'] }}</strong><p>{{ $stats['total'] ? round($stats['completed'] / $stats['total'] * 100) : 0 }}%</p></div></article>
    <article class="report-stat orange"><span><svg class="icon"><use href="#clock"/></svg></span><div><h2>In Progress</h2><strong>{{ $stats['in_progress'] }}</strong><p>{{ $stats['total'] ? round($stats['in_progress'] / $stats['total'] * 100) : 0 }}%</p></div></article>
    <article class="report-stat purple"><span>!</span><div><h2>Pending</h2><strong>{{ $stats['pending'] }}</strong><p>{{ $stats['total'] ? round($stats['pending'] / $stats['total'] * 100) : 0 }}%</p></div></article>
    <article class="report-stat blue"><span>x</span><div><h2>Rejected</h2><strong>{{ $stats['rejected'] }}</strong><p>submission rejects</p></div></article>
</div>

<div class="report-grid-top">
    <section class="report-panel"><h2>Activity Completion Trend</h2><div class="trend-chart" id="trend-chart"></div><div class="chart-key"><span>Completed</span><span>In Progress</span><span>Pending</span><span>Rejected</span></div></section>
    <section class="report-panel"><h2>Activity Type Distribution</h2><div class="donut-report" id="activity-donut"><div>{{ $stats['total'] }}<small>Total</small></div></div><div class="type-list">@forelse($activityDistribution as $type)<span><b><i></i>{{ $type['name'] }}</b>{{ $type['count'] }} ({{ $type['percent'] }}%)</span>@empty<span><b><i></i>No activity data</b>0</span>@endforelse</div></section>
    <section class="report-panel"><h2>Top Workers by Activity</h2><table class="worker-rank"><tr><th>#</th><th>Worker</th><th>Completed</th></tr>@forelse($topWorkers as $worker)<tr><td>{{ $loop->iteration }}</td><td>{{ $worker->name }}<br><small>USR{{ str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) }}</small></td><td>{{ $worker->completed_assignments_count }}</td></tr>@empty<tr><td colspan="3">No worker activity found.</td></tr>@endforelse</table></section>
</div>

<div class="report-grid-middle">
    <section class="report-panel"><h2>Project Wise Performance</h2>@forelse($projectPerformance as $item)<div class="performance-row"><span>{{ $item['name'] }}</span><div class="performance-bar"><span style="width:{{ $item['percent'] }}%"></span></div><b>{{ $item['count'] }}</b></div>@empty<p class="empty-state">No project performance found.</p>@endforelse</section>
    <section class="report-panel"><h2>Location Wise Activity Count</h2><div class="location-bars">@forelse($locationCounts as $location)<div>{{ $location['count'] }}<i style="--height:{{ max(8, round($location['count'] / $maxLocation * 100)) }}%"></i><small>{{ $location['name'] }}</small></div>@empty<div>0<i style="--height:8%"></i><small>No Data</small></div>@endforelse</div></section>
</div>

<div class="report-grid-bottom">
    <section class="report-panel"><h2>Recent Activity Submissions</h2><table class="submission-table"><tr><th>#</th><th>Activity</th><th>Worker</th><th>Location</th><th>Submitted At</th><th>Status</th></tr>@forelse($recentSubmissions as $submission)<tr><td>{{ $loop->iteration }}</td><td>{{ $submission['activity'] }}</td><td>{{ $submission['worker'] }}</td><td>{{ $submission['location'] }}</td><td>{{ $submission['submitted_at'] }}</td><td><span class="status-{{ $submission['status'] === 'pending' ? 'pending' : $submission['status'] }}">{{ $submission['status_label'] }}</span></td></tr>@empty<tr><td colspan="6">No recent submissions found.</td></tr>@endforelse</table></section>
    <section class="report-panel"><h2>Activity Heat Map</h2><div class="heat-map"></div></section>
</div>
<div id="report-toast" class="company-toast" hidden></div>
@endsection

@push('scripts')
<script>
window.reportTrendData = @json($trend);
window.reportDistributionData = @json($activityDistribution);
</script>
<script src="{{ asset('js/reports.js') }}"></script>
@endpush
