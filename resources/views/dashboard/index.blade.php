@extends('layout.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Sample data for the dashboard design. Replace with controller data later. --}}
    @php
        $stats = [
            ['Total Companies', 12, 2, 'building', 'blue'],
            ['Total Projects', 28, 5, 'folder', 'green'],
            ['Total Workers', 86, 8, 'users', 'orange'],
            ['Total Assignments', 142, 12, 'clipboard', 'purple'],
        ];
        $assignments = [
            ['Ramesh Kumar', 'RK', 'City Clean Drive', 'Poster Installation', 'In Progress', '28 Sep 2026', 'green'],
            ['Suresh Patel', 'SP', 'Market Survey', 'Survey', 'Completed', '28 Sep 2026', 'blue'],
            ['Amit Singh', 'AS', 'River Awareness', 'Road Show', 'Pending', '27 Sep 2026', 'orange'],
            ['Priya Sharma', 'PS', 'City Clean Drive', 'Install Posters', 'In Progress', '27 Sep 2026', 'green'],
            ['Neha Verma', 'NV', 'Market Survey', 'Visit Shops', 'Completed', '26 Sep 2026', 'blue'],
        ];
        $updates = [
            ['Photo submitted - Poster Installation', 'Ramesh Kumar', '10 mins ago', 'image', 'green'],
            ['Location update received', 'Suresh Patel', '25 mins ago', 'pin', 'blue'],
            ['Activity session started', 'Amit Singh', '1 hour ago', 'clock', 'purple'],
            ['Report submitted - Market Survey', 'Priya Sharma', '2 hours ago', 'file', 'orange'],
            ['Assignment completed', 'Neha Verma', '3 hours ago', 'check', 'green'],
        ];
        $shortcuts = [
            ['Manage Companies', 'Add, edit and manage companies', 'building', 'blue'],
            ['Create Project', 'Add new project and activities', 'folder', 'green'],
            ['Add User / Worker', 'Create and manage users', 'users', 'orange'],
            ['View Reports', 'Check analytics and reports', 'chart', 'purple'],
        ];
    @endphp
    <div class="page-heading">
        <div><h1>Good Afternoon, Admin 👋</h1><p>Here’s what’s happening with your field activity management system today.</p></div>
        <div class="date-card"><span class="date-icon"><svg class="icon"><use href="#calendar"/></svg></span><div><strong>Monday, 28 September 2026</strong><small>03:45 PM</small></div></div>
    </div>

    <div class="stats-grid">
        @foreach ($stats as [$label, $count, $increase, $icon, $color])
            <article class="stat-card {{ $color }}">
                <span class="stat-icon"><svg class="icon"><use href="#{{ $icon }}"/></svg></span>
                <div><h2>{{ $label }}</h2><strong class="stat-number">{{ $count }}</strong><p class="growth">↑ {{ $increase }} this month</p></div>
                <button class="more-button" aria-label="Details for {{ $label }}" data-detail="{{ $label }}" data-description="{{ $count }} total · {{ $increase }} added this month. Sample dashboard data.">⋮</button>
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
            <div class="panel-heading"><h2>Assignment Status</h2><span class="period-label" id="status-period">Last 7 Days</span></div>
            <div class="status-content">
                <div class="donut" id="assignment-donut" role="img"><div><strong id="assignment-total">0</strong><span>Total</span></div></div>
                <div class="status-legend" id="status-legend"></div>
            </div>
            <p class="chart-caption">Sample data · totals for the selected period</p>
        </section>
    </div>
    <div class="details-grid">
        <section class="panel assignments-panel">
            <div class="panel-heading"><h2>Recent Assignments</h2><button class="text-button" data-preview="All Assignments">View All →</button></div>
            <div class="table-scroll">
                <table><thead><tr><th>#</th><th>Worker</th><th>Project</th><th>Activity</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody id="assignment-rows">
                        @foreach ($assignments as [$name, $initials, $project, $activity, $status, $date, $color])
                            <tr><td>{{ $loop->iteration }}</td><td><span class="worker-name"><span class="avatar avatar-{{ $loop->iteration }}">{{ $initials }}</span>{{ $name }}</span></td><td>{{ $project }}</td><td>{{ $activity }}</td><td><span class="badge {{ $color }}">{{ $status }}</span></td><td>{{ $date }}</td><td><button class="view-button" aria-label="View assignment for {{ $name }}" data-detail="{{ $name }}" data-description="{{ $project }} · {{ $activity }} · {{ $status }} · {{ $date }}"><svg class="icon"><use href="#eye"/></svg></button></td></tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="empty-state" id="search-empty" hidden>No assignments match your search.</p>
            </div>
        </section>
        <section class="panel updates-panel">
            <div class="panel-heading"><h2>Latest Activity Updates</h2><button class="text-button" data-preview="All Activity Updates">View All →</button></div>
            <div class="updates-list">
                @foreach ($updates as [$title, $name, $time, $icon, $color])
                    <button class="update-row" data-detail="{{ $title }}" data-description="{{ $name }} · {{ $time }}. Sample activity update."><span class="update-icon {{ $color }}"><svg class="icon"><use href="#{{ $icon }}"/></svg></span><span><strong>{{ $title }}</strong><small>{{ $name }} · {{ $time }}</small></span></button>
                @endforeach
            </div>
        </section>
    </div>
    <div class="shortcuts-grid">
        @foreach ($shortcuts as [$title, $description, $icon, $color])
            <button class="shortcut {{ $color }}" data-preview="{{ $title }}"><span class="shortcut-icon"><svg class="icon"><use href="#{{ $icon }}"/></svg></span><span><strong>{{ $title }}</strong><small>{{ $description }}</small></span><span class="shortcut-arrow">→</span></button>
        @endforeach
    </div>
@endsection
