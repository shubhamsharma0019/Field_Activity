<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | Field Activity</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @stack('styles')
    <style>
        /* Same compact typography is used on every admin page. */
        body {
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .page-content {
            font-size: 14px;
        }

        .breadcrumbs {
            display: none !important;
        }

        main h1 {
            margin-bottom: 8px !important;
            font-size: 31px !important;
            line-height: 1.1;
            letter-spacing: -0.6px;
        }

        .page-heading p,
        .updates-heading p,
        .settings-heading p,
        .reports-heading p,
        .tracking-heading p,
        .assignments-heading p,
        .submissions-heading p {
            font-size: 16px !important;
        }

        @media (max-width: 760px) {
            .page-content {
                padding: 16px 8px;
            }

            main h1 {
                font-size: 20px !important;
            }

            .page-heading,
            .updates-heading,
            .reports-heading,
            .settings-heading,
            .assignments-heading,
            .submissions-heading,
            .tracking-heading {
                margin-bottom: 16px;
            }

            .page-heading p,
            .updates-heading p,
            .settings-heading p,
            .reports-heading p,
            .tracking-heading p,
            .assignments-heading p,
            .submissions-heading p {
                font-size: 11px !important;
            }

            .page-heading > div,
            .page-heading > a,
            .page-heading > button {
                width: 100%;
            }

            .page-heading > a,
            .page-heading > button {
                justify-content: center;
            }

            .company-table-scroll,
            .table-scroll,
            .updates-table-card,
            .sessions-main {
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .topbar {
                height: 58px;
                padding: 0 12px;
            }

            .topbar .icon {
                width: 16px;
                height: 16px;
            }

            .search-box {
                max-width: none;
                padding: 8px 11px;
            }

            .search-box input {
                font-size: 9px;
            }

            .admin-avatar {
                width: 28px;
                height: 28px;
                font-size: 9px;
            }
        }
    </style>
</head>
<body>
    @include('layout.partials.icons')
    @php
        $authUser = auth()->user();
        $displayName = $authUser?->name ?? 'Admin';
        $displayRole = str((string) ($authUser?->role ?? 'admin'))->replace('_', ' ')->title();
        $avatarInitials = collect(explode(' ', trim($displayName)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('') ?: 'AD';
        $pendingSubmissions = \App\Models\ActivitySubmission::query()
            ->where('approval_status', 'pending')
            ->latest('server_timestamp')
            ->limit(3)
            ->get(['id', 'worker_id', 'assignment_id', 'server_timestamp']);
        $pendingSessions = \App\Models\ActivitySession::query()
            ->where('status', 'pending_approval')
            ->latest('completed_at')
            ->limit(3)
            ->get(['id', 'worker_id', 'assignment_id', 'completed_at']);
        $topbarNotificationCount = ($notificationCount ?? null)
            ?? (\App\Models\ActivitySubmission::query()->where('approval_status', 'pending')->count()
                + \App\Models\ActivitySession::query()->where('status', 'pending_approval')->count());
        $globalSearchItems = collect([
            ['label' => 'Dashboard', 'group' => 'Page', 'url' => route('web.dashboard')],
            ['label' => 'Companies', 'group' => 'Page', 'url' => route('web.companies.index')],
            ['label' => 'Users / Workers', 'group' => 'Page', 'url' => route('web.users.index')],
            ['label' => 'Projects', 'group' => 'Page', 'url' => route('web.projects.index')],
            ['label' => 'Work Types', 'group' => 'Page', 'url' => route('web.activity-types.index')],
            ['label' => 'Assignments', 'group' => 'Page', 'url' => route('web.assignments.index')],
            ['label' => 'Submissions', 'group' => 'Page', 'url' => route('web.submissions.index')],
            ['label' => 'Activity Sessions', 'group' => 'Page', 'url' => route('web.activity-sessions.index')],
            ['label' => 'Live Worker Tracking', 'group' => 'Map', 'url' => route('web.activity-sessions.live')],
            ['label' => 'Full Location Map', 'group' => 'Map', 'url' => route('web.activity-sessions.map')],
            ['label' => 'Activity Updates', 'group' => 'Page', 'url' => route('web.activity-updates.index')],
            ['label' => 'Reports', 'group' => 'Page', 'url' => route('web.reports.index')],
            ['label' => 'Settings', 'group' => 'Page', 'url' => route('web.settings.index')],
        ])
            ->merge(\App\Models\Project::query()->latest()->limit(6)->get(['id', 'name', 'project_code'])->map(fn ($project) => [
                'label' => $project->name,
                'group' => 'Project',
                'meta' => $project->project_code,
                'url' => route('web.projects.index'),
            ]))
            ->merge(\App\Models\User::query()->where('role', 'worker')->latest()->limit(6)->get(['id', 'name', 'mobile'])->map(fn ($worker) => [
                'label' => $worker->name,
                'group' => 'Worker',
                'meta' => $worker->mobile,
                'url' => route('web.users.index'),
            ]))
            ->values();
    @endphp
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="brand" href="{{ route('web.dashboard') }}">
        <svg class="brand-logo" viewBox="0 0 60 70" aria-hidden="true"><path d="M30 65S6 36 6 25a24 24 0 1 1 48 0c0 15-24 40-24 40" fill="#19bc99"/><path d="M30 1v64S7 39 7 25A24 24 0 0 1 30 1" fill="#64ddbd"/><circle cx="30" cy="24" r="8" fill="white"/><path d="M29 67C9 67 0 54 1 46c17-5 26 8 28 21m3 0c20 0 28-13 27-21-17-5-25 8-27 21" fill="#4dd79a"/></svg>
        <span><strong>Field Activity</strong><small>Management System</small></span>
    </a>
    @php
        $menuItems = [
            ['Dashboard', 'home'], ['Companies', 'building'], ['Users', 'users'],
            ['Projects', 'folder'], ['Work Types', 'list'],
            ['Assignments', 'clipboard'], ['Submissions', 'image'], ['Activity Sessions', 'clock'],
            ['Activity Updates', 'pulse'], ['Reports', 'chart'], ['Settings', 'settings'],
        ];
    @endphp
    <nav class="navigation">
        @foreach ($menuItems as [$label, $icon])
            @if (in_array($label, ['Dashboard', 'Companies', 'Users', 'Projects', 'Work Types', 'Assignments', 'Submissions', 'Activity Sessions', 'Activity Updates', 'Reports', 'Settings'], true))
                @php
                    $menuRoute = match ($label) {
                        'Dashboard' => 'web.dashboard',
                        'Companies' => 'web.companies.index',
                        'Users' => 'web.users.index',
                        'Work Types' => 'web.activity-types.index',
                        'Assignments' => 'web.assignments.index',
                        'Submissions' => 'web.submissions.index',
                        'Activity Sessions' => 'web.activity-sessions.index',
                        'Activity Updates' => 'web.activity-updates.index',
                        'Reports' => 'web.reports.index',
                        'Settings' => 'web.settings.index',
                        default => 'web.projects.index',
                    };
                    $isActive = match ($menuRoute) {
                        'web.companies.index' => request()->routeIs('web.companies.*'),
                        'web.users.index' => request()->routeIs('web.users.*'),
                        'web.projects.index' => request()->routeIs('web.projects.*'),
                        'web.activity-types.index' => request()->routeIs('web.activity-types.*'),
                        'web.assignments.index' => request()->routeIs('web.assignments.*'),
                        'web.submissions.index' => request()->routeIs('web.submissions.*'),
                        'web.activity-sessions.index' => request()->routeIs('web.activity-sessions.*'),
                        'web.activity-updates.index' => request()->routeIs('web.activity-updates.*'),
                        'web.reports.index' => request()->routeIs('web.reports.*'),
                        'web.settings.index' => request()->routeIs('web.settings.*'),
                        default => request()->routeIs($menuRoute),
                    };
                @endphp
                <a class="nav-item {{ $isActive ? 'active' : '' }}" href="{{ route($menuRoute) }}" @if ($isActive) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#{{ $icon }}"/></svg><span>{{ $label }}</span></a>
            @else
                <button class="nav-item" type="button" data-preview="{{ $label }}"><svg class="icon" aria-hidden="true"><use href="#{{ $icon }}"/></svg><span>{{ $label }}</span></button>
            @endif
        @endforeach
    </nav>
    <div class="sidebar-footer">
        <svg class="icon" aria-hidden="true"><use href="#users"/></svg>
        @if (request()->routeIs('web.companies.*'))
            <p><strong>Manage<br>Companies</strong><br><small>Create, edit and<br>track companies</small></p>
        @elseif (request()->routeIs('web.users.*'))
            <p><strong>Manage<br>Field Workforce</strong><br><small>Add users, assign<br>projects and track</small></p>
        @elseif (request()->routeIs('web.projects.*'))
            <p><strong>Manage<br>Projects</strong><br><small>Create, assign and<br>track activities</small></p>
        @elseif (request()->routeIs('web.activity-types.*'))
            <p><strong>Manage<br>Work Types</strong><br><small>Reusable templates<br>for project work</small></p>
        @elseif (request()->routeIs('web.assignments.*'))
            <p><strong>Assign &amp;<br>Track Field Work</strong><br><small>Manage your field<br>operations efficiently</small></p>
        @elseif (request()->routeIs('web.submissions.*'))
            <p><strong>Review Field<br>Submissions</strong><br><small>Verify photos, locations<br>and activity proofs</small></p>
        @elseif (request()->routeIs('web.activity-sessions.*'))
            <p><strong>Live Track<br>Field Workers</strong><br><small>Monitor real-time location<br>and activity progress</small></p>
        @elseif (request()->routeIs('web.activity-updates.*'))
            <p><strong>Track Your<br>Field Operations</strong><br><small>Real-time updates, better<br>visibility and productivity.</small></p>
        @elseif (request()->routeIs('web.reports.*'))
            <p><strong>Generate<br>Detailed Reports</strong><br><small>Analyze field performance<br>and get insights</small></p>
        @elseif (request()->routeIs('web.settings.*'))
            <p><strong>System Settings</strong><br><small>Configure system preferences<br>and manage your platform.</small></p>
        @else
            <p><strong>Track<br>Manage</strong><br>Field Activities</p>
        @endif
    </div>
</aside>
    <button class="sidebar-overlay" id="sidebar-overlay" aria-label="Close navigation"></button>
    <div class="workspace">
        <header class="topbar">
    <button class="icon-button menu-toggle" type="button" id="menu-toggle" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">
        <svg class="icon" aria-hidden="true"><use href="#menu"/></svg>
    </button>
    <div class="topbar-search-wrap">
    <label class="search-box" for="dashboard-search">
        <svg class="icon" aria-hidden="true"><use href="#search"/></svg>
        <input id="dashboard-search" type="search" placeholder="Search anything..." aria-label="Search pages, projects and workers" autocomplete="off">
        <kbd>Ctrl + K</kbd>
    </label>
        <div class="topbar-search-results" id="topbar-search-results" hidden></div>
    </div>
    <div class="topbar-actions">
        <div class="topbar-menu">
            <button class="icon-button notification-button" type="button" id="notification-toggle" aria-expanded="false" aria-controls="notification-menu" aria-label="Notifications, {{ $topbarNotificationCount }} pending items"><svg class="icon"><use href="#bell"/></svg>@if ($topbarNotificationCount > 0)<span class="notification-count">{{ $topbarNotificationCount }}</span>@endif</button>
            <div class="topbar-dropdown notification-menu" id="notification-menu" hidden>
                <div class="dropdown-heading"><strong>Notifications</strong><span>{{ $topbarNotificationCount }} pending</span></div>
                @forelse($pendingSubmissions as $submission)
                    <a href="{{ route('web.submissions.index') }}"><b>Submission review</b><small>Photo evidence is waiting for approval</small></a>
                @empty
                    @forelse($pendingSessions as $session)
                        <a href="{{ route('web.activity-sessions.index') }}"><b>Session review</b><small>Start/end activity needs checking</small></a>
                    @empty
                        <p class="dropdown-empty">No pending work right now.</p>
                    @endforelse
                @endforelse
                @if($pendingSubmissions->isNotEmpty() && $pendingSessions->isNotEmpty())
                    @foreach($pendingSessions as $session)
                        <a href="{{ route('web.activity-sessions.index') }}"><b>Session review</b><small>Start/end activity needs checking</small></a>
                    @endforeach
                @endif
            </div>
        </div>
        <button class="profile" type="button" data-detail="Admin profile" data-description="{{ $displayName }} · {{ $displayRole }}">
            <span class="avatar admin-avatar">{{ $avatarInitials }}</span>
            <span class="profile-text"><strong>{{ $displayName }}</strong><small>{{ $displayRole }}</small></span>
            <svg class="icon small"><use href="#chevron"/></svg>
        </button>
        <div class="topbar-dropdown profile-menu" id="profile-menu" hidden>
            <div class="dropdown-heading"><strong>{{ $displayName }}</strong><span>{{ $authUser?->email ?? $displayRole }}</span></div>
            <a href="{{ route('web.settings.index') }}"><b>Settings</b><small>System controls</small></a>
            <a href="{{ route('web.reports.index') }}"><b>Reports</b><small>Performance summary</small></a>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">Logout</button></form>
        </div>
    </div>
</header>
        <main class="page-content">@yield('content')</main>
    </div>
    <dialog id="detail-dialog">
        <button class="dialog-close" id="close-dialog" aria-label="Close details">×</button>
        <h2 id="dialog-title"></h2>
        <p id="dialog-text"></p>
    </dialog>
    <script>
        window.globalSearchItems = @json($globalSearchItems);
    </script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    @stack('scripts')
</body>
</html>
