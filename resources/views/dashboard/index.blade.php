@extends('layout.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-heading">
        <div>
            <h1>{{ $greeting }}, {{ auth()->user()?->name ?? 'Admin' }} 👋</h1>
            <p>Here’s what’s happening with your field activity management system today.</p>
        </div>
        <div class="date-card">
            <span class="date-icon"><svg class="icon"><use href="#calendar"/></svg></span>
            <div><strong>{{ $currentDate }}</strong><small>{{ $currentTime }}</small></div>
        </div>
    </div>

    <div class="stats-grid">
        @foreach ($stats as $stat)
            <article class="stat-card {{ $stat['color'] }}">
                <span class="stat-icon"><svg class="icon"><use href="#{{ $stat['icon'] }}"/></svg></span>
                <div>
                    <h2>{{ $stat['label'] }}</h2>
                    <strong class="stat-number">{{ number_format($stat['count']) }}</strong>
                    <p class="growth">↑ {{ number_format($stat['increase']) }} this month</p>
                    <p class="stat-meta">{{ $stat['meta'] ?? '' }}</p>
                </div>
                <button class="more-button" aria-label="Details for {{ $stat['label'] }}" data-detail="{{ $stat['label'] }}" data-description="{{ number_format($stat['count']) }} total · {{ number_format($stat['increase']) }} added this month.">⋮</button>
            </article>
        @endforeach
    </div>

    <div class="charts-grid" id="dashboard-charts">
        <section class="panel activity-panel">
            <div class="panel-heading">
                <h2>Activity Overview</h2>
                <select class="period-label" id="chart-period" aria-label="Graph date range">
                    <option value="7">Last 7 Days</option>
                    <option value="14">Last 14 Days</option>
                    <option value="30">Last 30 Days</option>
                </select>
            </div>
            <div class="chart-legend"><span><i class="dot blue"></i>Completed</span><span><i class="dot green"></i>In Progress</span><span><i class="dot orange"></i>Pending</span></div>
            <svg class="activity-chart" id="activity-chart" viewBox="0 0 700 180" role="img" aria-label="Daily activity counts"></svg>
            <p class="chart-caption" id="chart-caption" aria-live="polite"></p>
        </section>
        <section class="panel status-panel">
            <div class="panel-heading"><h2>Assignment Status</h2><span class="period-label" id="status-period">All Time</span></div>
            <div class="status-content">
                <div class="donut" id="assignment-donut" role="img"><div><strong id="assignment-total">0</strong><span>Total</span></div></div>
                <div class="status-legend" id="status-legend"></div>
            </div>
            <p class="chart-caption">Live totals from current assignments</p>
        </section>
    </div>

    <div class="details-grid">
        <section class="panel">
            <div class="panel-heading"><h2>Evidence Review</h2><a class="text-button" href="{{ route('web.submissions.index') }}">Review Queue →</a></div>
            <div class="review-grid">
                <article class="review-card orange"><strong>{{ number_format($submissionSummary['pending']) }}</strong><span>Pending</span></article>
                <article class="review-card green"><strong>{{ number_format($submissionSummary['approved']) }}</strong><span>Approved</span></article>
                <article class="review-card purple"><strong>{{ number_format($submissionSummary['rejected']) }}</strong><span>Rejected</span></article>
            </div>
        </section>
        <section class="panel">
            <div class="panel-heading"><h2>Target Progress</h2><a class="text-button" href="{{ route('web.reports.index') }}">Reports →</a></div>
            <div class="progress-list">
                @forelse ($projectProgress as $project)
                    <article class="progress-row">
                        <div><strong>{{ $project['name'] }}</strong><small>{{ $project['company'] }} · {{ $project['assignments'] }} assignments</small></div>
                        <span>{{ $project['approved'] }}/{{ $project['target'] ?: '0' }}</span>
                        <meter min="0" max="100" value="{{ $project['percentage'] }}"></meter>
                        <small>{{ $project['percentage'] }}% · {{ $project['remaining'] }} remaining</small>
                    </article>
                @empty
                    <p class="empty-state">No target-based project progress found.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="details-grid">
        <section class="panel assignments-panel">
            <div class="panel-heading"><h2>Recent Assignments</h2><a class="text-button" href="{{ route('web.assignments.index') }}">View All →</a></div>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>#</th><th>Worker</th><th>Project</th><th>Activity</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody id="assignment-rows">
                        @forelse ($recentAssignments as $assignment)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="worker-name"><span class="avatar avatar-{{ (($loop->iteration - 1) % 5) + 1 }}">{{ $assignment['initials'] }}</span>{{ $assignment['worker'] }}</span></td>
                                <td>{{ $assignment['project'] }}</td>
                                <td>{{ $assignment['activity'] }}</td>
                                <td><span class="badge {{ $assignment['color'] }}">{{ $assignment['status'] }}</span></td>
                                <td>{{ $assignment['date'] }}</td>
                                <td><button class="view-button" aria-label="View assignment for {{ $assignment['worker'] }}" data-detail="{{ $assignment['worker'] }}" data-description="{{ $assignment['description'] }} · {{ $assignment['date'] }}"><svg class="icon"><use href="#eye"/></svg></button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No assignments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <p class="empty-state" id="search-empty" hidden>No assignments match your search.</p>
            </div>
        </section>
        <section class="panel updates-panel">
            <div class="panel-heading"><h2>Latest Activity Updates</h2><a class="text-button" href="{{ route('web.activity-updates.index') }}">View All →</a></div>
            <div class="updates-list">
                @forelse ($latestUpdates as $update)
                    <button class="update-row" data-detail="{{ $update['title'] }}" data-description="{{ $update['description'] }}"><span class="update-icon {{ $update['color'] }}"><svg class="icon"><use href="#{{ $update['icon'] }}"/></svg></span><span><strong>{{ $update['title'] }}</strong><small>{{ $update['name'] }} · {{ $update['time'] }}</small></span></button>
                @empty
                    <p class="empty-state">No recent activity updates.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="panel submissions-panel">
        <div class="panel-heading"><h2>Recent Submissions</h2><a class="text-button" href="{{ route('web.submissions.index') }}">View All →</a></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>#</th><th>Activity</th><th>Project</th><th>Worker</th><th>Location</th><th>Submitted At</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($recentSubmissions as $submission)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $submission['activity'] }}</td>
                            <td>{{ $submission['project'] }}</td>
                            <td>{{ $submission['worker'] }}</td>
                            <td>{{ $submission['location'] }}</td>
                            <td>{{ $submission['submitted_at'] }}</td>
                            <td><span class="badge {{ $submission['color'] }}">{{ $submission['status'] }}</span></td>
                            <td><button class="view-button" aria-label="View submission {{ $submission['id'] }}" data-detail="{{ $submission['activity'] }}" data-description="{{ $submission['description'] }}"><svg class="icon"><use href="#eye"/></svg></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No submissions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="shortcuts-grid">
        @foreach ($shortcuts as $shortcut)
            <a class="shortcut {{ $shortcut['color'] }}" href="{{ route($shortcut['route']) }}"><span class="shortcut-icon"><svg class="icon"><use href="#{{ $shortcut['icon'] }}"/></svg></span><span><strong>{{ $shortcut['title'] }}</strong><small>{{ $shortcut['description'] }}</small></span><span class="shortcut-arrow">→</span></a>
        @endforeach
    </div>
    <script>
        window.dashboardChartData = @json($chartData);
        window.dashboardStatusSummary = @json($statusSummary);
    </script>
@endsection
