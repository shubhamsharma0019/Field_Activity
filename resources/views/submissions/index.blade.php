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
        <button class="export-button" type="button" id="export-submissions">Export</button>
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
        <article class="submission-stat purple"><span>x</span><div><h2>Rejected</h2><strong>{{ $stats['rejected'] }}</strong><p>{{ $stats['rejected_percent'] }}%</p></div></article>
    </div>

    <div class="submissions-layout">
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
                        <button class="submission-item {{ $loop->first ? 'selected' : '' }}" type="button"
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
                        </button>
                    @endforeach
                </div>
                <p id="submissions-empty" class="empty-state" @if (count($submissions) > 0) hidden @endif>No submissions found.</p>
            </div>
        </section>

        <aside class="submission-detail">
            <div class="detail-heading"><h2>Submission Details</h2><button type="button" aria-label="Close details">x</button></div>
            <div class="detail-photo" id="detail-photo"></div>
            <section class="detail-section"><h3>Assignment</h3><table class="detail-table"><tr><td>Title</td><td id="detail-title">Select a submission</td></tr><tr><td>Project</td><td id="detail-project">N/A</td></tr><tr><td>Activity Type</td><td id="detail-activity-type">N/A</td></tr><tr><td>Activity Mode</td><td id="detail-mode">N/A</td></tr></table></section>
            <section class="detail-section"><h3>Worker Information</h3><table class="detail-table"><tr><td>Worker</td><td id="detail-worker">N/A</td></tr><tr><td>Mobile</td><td id="detail-mobile">N/A</td></tr></table></section>
            <section class="detail-section"><h3>Location & Time</h3><table class="detail-table"><tr><td>Location</td><td id="detail-location">N/A</td></tr><tr><td>GPS</td><td id="detail-gps">N/A</td></tr><tr><td>Submitted</td><td id="detail-time">N/A</td></tr><tr><td>Status</td><td id="detail-status">N/A</td></tr></table></section>
            <section class="detail-section"><h3>Remark</h3><p class="detail-note" id="detail-remark">No remark added.</p></section>
            <section class="detail-section"><h3>Review Comment</h3><textarea class="comment-box" id="submission-comment" name="rejection_reason" form="submission-review-form" maxlength="500" placeholder="Add review comments..."></textarea><span class="comment-count" id="comment-count">0/500</span></section>
            <form id="submission-review-form" method="POST" action="">
                @csrf
                <input type="hidden" name="approval_status" id="review-status" value="approved">
                <div class="detail-actions">
                    <button class="reject-button" type="submit" data-status="rejected">Reject</button>
                    <button class="approve-button" type="submit" data-status="approved">Approve</button>
                </div>
            </form>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        window.submissionReviewBaseUrl = @json(url('/submissions'));
    </script>
    <script src="{{ asset('js/submissions.js') }}"></script>
@endpush
