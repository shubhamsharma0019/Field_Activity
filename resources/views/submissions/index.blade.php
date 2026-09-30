@extends('layout.app')

@section('title', 'Field Submissions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/submissions.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span>
        <span>Submissions</span>
    </nav>

    <div class="page-heading submissions-heading">
        <div>
            <h1>Field Submissions</h1>
            <p>Review and verify photo submissions from field workers.</p>
        </div>
        <button class="export-button" type="button" id="export-submissions"><svg class="icon" aria-hidden="true"><use href="#file"/></svg>Export CSV</button>
    </div>

    @if (session('status'))
        <div class="company-toast">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="company-toast error">{{ $errors->first() }}</div>
    @endif

    <div class="submission-stats">
        <article class="submission-stat blue"><span><svg class="icon"><use href="#image"/></svg></span><div><h2>Total Submissions</h2><strong>{{ $stats['total'] }}</strong><p>+ {{ $stats['week'] }} this week</p></div></article>
        <article class="submission-stat green"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Approved</h2><strong>{{ $stats['approved'] }}</strong><p>{{ $stats['approved_percent'] }}%</p></div></article>
        <article class="submission-stat orange"><span><svg class="icon"><use href="#clock"/></svg></span><div><h2>Pending Review</h2><strong>{{ $stats['pending'] }}</strong><p>{{ $stats['pending_percent'] }}%</p></div></article>
        <article class="submission-stat purple"><span><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></span><div><h2>Rejected</h2><strong>{{ $stats['rejected'] }}</strong><p>{{ $stats['rejected_percent'] }}%</p></div></article>
    </div>

    <div class="submissions-layout submissions-list-only">
        <section class="submission-main">
            <div class="submission-filters">
                <label class="submission-search"><svg class="icon"><use href="#search"/></svg><input id="submission-search" placeholder="Search by assignment, worker or location..."></label>
                <select id="submission-company"><option value="all">All Companies</option><option value="internal">Internal</option>@foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select>
                <select id="submission-project"><option value="all">All Projects</option>@foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select>
                <select id="submission-activity-type"><option value="all">All Activity Types</option>@foreach ($activityTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                <select id="submission-status"><option value="all">All Status</option><option value="pending">Pending Review</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select>
                <button type="button" id="submission-reset">Reset</button>
            </div>

            <div class="submissions-card">
                <div class="submissions-card-header">
                    <h2 id="submission-count">Submissions ({{ count($submissions) }})</h2>
                    <select id="submission-sort"><option value="newest">Newest First</option><option value="oldest">Oldest First</option></select>
                </div>
                <div class="submission-grid" id="submission-grid">
                    @foreach ($submissions as $submission)
                        <a class="submission-item" href="{{ route('web.submissions.show', $submission['id']) }}"
                            data-id="{{ $submission['id'] }}"
                            data-title="{{ $submission['title'] }}"
                            data-project="{{ $submission['project'] }}"
                            data-project-code="{{ $submission['project_code'] }}"
                            data-project-id="{{ $submission['project_id'] }}"
                            data-company="{{ $submission['company_id'] ?? 'internal' }}"
                            data-activity-type="{{ $submission['activity_type'] }}"
                            data-activity-type-id="{{ $submission['activity_type_id'] }}"
                            data-mode="{{ $submission['mode'] }}"
                            data-worker="{{ $submission['worker'] }}"
                            data-worker-code="{{ $submission['worker_code'] }}"
                            data-worker-mobile="{{ $submission['worker_mobile'] }}"
                            data-location="{{ $submission['location'] }}"
                            data-latitude="{{ $submission['latitude'] }}"
                            data-longitude="{{ $submission['longitude'] }}"
                            data-accuracy="{{ $submission['accuracy'] }}"
                            data-submitted-at="{{ $submission['submitted_at'] }}"
                            data-status="{{ $submission['status'] }}"
                            data-status-label="{{ $submission['status_label'] }}"
                            data-image="{{ $submission['image_url'] }}"
                            data-remark="{{ $submission['remark'] }}"
                            data-reviewer="{{ $submission['reviewer'] }}"
                            data-reviewed-at="{{ $submission['reviewed_at'] }}"
                            data-rejection-reason="{{ $submission['rejection_reason'] }}"
                            data-search="{{ $submission['search'] }}">
                            <div class="submission-photo" data-count="{{ $submission['photo_count'] }}" style="background-image: linear-gradient(135deg, #162d41aa, #2e9a79aa), url('{{ $submission['image_url'] }}')"></div>
                            <h3>{{ $submission['title'] }}</h3>
                            <p>{{ $submission['location'] }}</p>
                            <strong>{{ $submission['worker'] }}</strong>
                            <p>{{ $submission['submitted_at'] }}</p>
                            <span class="submission-status {{ $submission['status'] }}">{{ $submission['status_label'] }}</span>
                        </a>
                    @endforeach
                </div>
                <p id="submissions-empty" class="empty-state" @if (count($submissions) > 0) hidden @endif>No submissions found.</p>
            </div>
        </section>


    </div>
@endsection

@push('scripts')
    <script>
        window.submissionReviewBaseUrl = @json(url('/submissions'));
    </script>
    <script src="{{ asset('js/submissions.js') }}"></script>
@endpush
