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
            display: flex;
            align-items: center;
            gap: 18px;
            margin: 3px 0 24px;
            color: #74839a;
            font-size: 13px;
        }

        .breadcrumbs a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #3e5a82;
        }

        .breadcrumbs .icon {
            width: 18px;
            color: #1768ee;
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

            .breadcrumbs {
                gap: 10px;
                margin: 2px 0 16px;
                font-size: 10px;
            }

            .breadcrumbs a {
                gap: 6px;
            }

            .breadcrumbs .icon {
                width: 15px;
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
                height: 56px;
                padding: 0 7px;
            }

            .topbar .icon {
                width: 16px;
                height: 16px;
            }

            .search-box {
                max-width: 155px;
                padding: 5px 9px;
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
        $topbarNotificationCount = $notificationCount ?? 0;
    @endphp
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="brand" href="{{ route('web.dashboard') }}">
        <svg class="brand-logo" viewBox="0 0 60 70" aria-hidden="true"><path d="M30 65S6 36 6 25a24 24 0 1 1 48 0c0 15-24 40-24 40" fill="#19bc99"/><path d="M30 1v64S7 39 7 25A24 24 0 0 1 30 1" fill="#64ddbd"/><circle cx="30" cy="24" r="8" fill="white"/><path d="M29 67C9 67 0 54 1 46c17-5 26 8 28 21m3 0c20 0 28-13 27-21-17-5-25 8-27 21" fill="#4dd79a"/></svg>
        <span><strong>Field Activity</strong><small>Management System</small></span>
    </a>
    @php
        $menuItems = [
            ['Dashboard', 'home'], ['Companies', 'building'], ['Users', 'users'],
            ['Projects', 'folder'], ['Activity Types', 'list'],
            ['Assignments', 'clipboard'], ['Submissions', 'image'], ['Activity Sessions', 'clock'],
            ['Activity Updates', 'pulse'], ['Reports', 'chart'], ['Settings', 'settings'],
        ];
    @endphp
    <nav class="navigation">
        @foreach ($menuItems as [$label, $icon])
            @if (in_array($label, ['Dashboard', 'Companies', 'Users', 'Projects', 'Activity Types', 'Assignments', 'Submissions', 'Activity Sessions', 'Activity Updates', 'Reports', 'Settings'], true))
                @php
                    $menuRoute = match ($label) {
                        'Dashboard' => 'web.dashboard',
                        'Companies' => 'web.companies.index',
                        'Users' => 'web.users.index',
                        'Activity Types' => 'web.activity-types.index',
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
</aside>
    <button class="sidebar-overlay" id="sidebar-overlay" aria-label="Close navigation"></button>
    <div class="workspace">
        <header class="topbar">
    <button id="menu-toggle" class="mobile-menu-toggle" type="button" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <label class="search-box" for="dashboard-search">
        <svg class="icon" aria-hidden="true"><use href="#search"/></svg>
        <input id="dashboard-search" type="search" placeholder="Search anything..." aria-label="Search current page">
        <kbd>Ctrl + K</kbd>
    </label>
    <div class="topbar-actions">
        <button class="icon-button notification-button" type="button" data-detail="Notifications" data-description="{{ $topbarNotificationCount }} pending items need attention." aria-label="Notifications, {{ $topbarNotificationCount }} pending items"><svg class="icon"><use href="#bell"/></svg>@if ($topbarNotificationCount > 0)<span class="notification-count">{{ $topbarNotificationCount }}</span>@endif</button>
        <button class="profile" type="button" data-detail="Admin profile" data-description="{{ $displayName }} · {{ $displayRole }}">
            <span class="avatar admin-avatar">{{ $avatarInitials }}</span>
            <span class="profile-text"><strong>{{ $displayName }}</strong><small>{{ $displayRole }}</small></span>
            <svg class="icon small"><use href="#chevron"/></svg>
        </button>
    </div>
</header>
        <main class="page-content">@yield('content')</main>
    </div>
    <dialog id="detail-dialog">
        <button class="dialog-close" id="close-dialog" aria-label="Close details">×</button>
        <h2 id="dialog-title"></h2>
        <p id="dialog-text"></p>
    </dialog>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    @stack('scripts')
</body>
</html>
