<div>
    <!-- Knowing is not enough; we must apply. Being willing is not enough; we must do. - Leonardo da Vinci -->
</div>
@extends('layout.app')

@section('title', 'Field Submissions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/submissions.css') }}">
@endpush

@section('content')
    @php
        $submissions = [
            ['Poster Installation', 'Karol Bagh, New Delhi', 'Ramesh Kumar', '28 Sep 2026, 10:15 AM', 'Pending Review', 'pending', '3 photos'],
            ['Shop Visit', 'Lajpat Nagar, New Delhi', 'Neha Sharma', '28 Sep 2026, 09:40 AM', 'Approved', 'approved', '1 photo'],
            ['Road Cleaning', 'Connaught Place, New Delhi', 'Amit Singh', '28 Sep 2026, 08:20 AM', 'Approved', 'approved', '5 photos'],
            ['Market Survey', 'Chandni Chowk, New Delhi', 'Priya Verma', '27 Sep 2026, 05:30 PM', 'Rejected', 'rejected', '2 photos'],
            ['Banner Installation', 'Saket, New Delhi', 'Suresh Patel', '27 Sep 2026, 04:10 PM', 'Approved', 'approved', '4 photos'],
            ['Plantation Drive', 'India Gate, New Delhi', 'Manish Yadav', '27 Sep 2026, 03:15 PM', 'Pending Review', 'pending', '2 photos'],
            ['Community Survey', 'Green Park, New Delhi', 'Pooja Mehta', '26 Sep 2026, 02:25 PM', 'Approved', 'approved', '3 photos'],
            ['Waste Collection', 'Saket, New Delhi', 'Vikram Singh', '26 Sep 2026, 01:10 PM', 'Pending Review', 'pending', '1 photo'],
            ['Awareness Drive', 'Connaught Place, New Delhi', 'Kavita Joshi', '25 Sep 2026, 04:45 PM', 'Approved', 'approved', '3 photos'],
        ];
    @endphp

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
        <button class="export-button" type="button" id="export-submissions">&#8681; Export</button>
    </div>

    <div class="submission-stats">
        <article class="submission-stat blue"><span><svg class="icon"><use href="#image"/></svg></span><div><h2>Total Submissions</h2><strong>42</strong><p>+ 18 this week</p></div></article>
        <article class="submission-stat green"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Approved</h2><strong>28</strong><p>+ 67%</p></div></article>
        <article class="submission-stat orange"><span><svg class="icon"><use href="#clock"/></svg></span><div><h2>Pending Review</h2><strong>9</strong><p>+ 21%</p></div></article>
        <article class="submission-stat purple"><span>&times;</span><div><h2>Rejected</h2><strong>5</strong><p>+ 12%</p></div></article>
    </div>

    <div class="submissions-layout">
        <section class="submission-main">
            <div class="submission-filters">
                <label class="submission-search"><svg class="icon"><use href="#search"/></svg><input id="submission-search" placeholder="Search by assignment, worker or location..."></label>
                <select><option>All Companies</option></select>
                <select><option>All Projects</option></select>
                <select><option>All Activity Types</option></select>
                <select><option>All Status</option></select>
                <button type="button">Reset</button>
            </div>

            <div class="submissions-card">
                <div class="submissions-card-header"><h2>Submissions (42)</h2><select><option>Newest First</option></select></div>
                <div class="submission-grid" id="submission-grid">
                    @foreach ($submissions as $submission)
                        <button class="submission-item {{ $loop->first ? 'selected' : '' }}" type="button" data-title="{{ $submission[0] }}" data-worker="{{ $submission[2] }}" data-status="{{ $submission[4] }}">
                            <div class="submission-photo" data-count="{{ $submission[6] }}"></div>
                            <h3>{{ $submission[0] }}</h3>
                            <p>&#9906; {{ $submission[1] }}</p>
                            <strong>{{ $submission[2] }}</strong>
                            <p>{{ $submission[3] }}</p>
                            <span class="submission-status {{ $submission[5] }}">{{ $submission[4] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <aside class="submission-detail">
            <div class="detail-heading"><h2>Submission Details</h2><button type="button" aria-label="Close details">&times;</button></div>
            <div class="detail-photo"></div>
            <section class="detail-section"><h3>Assignment</h3><table class="detail-table"><tr><td>Title</td><td id="detail-title">Poster Installation</td></tr><tr><td>Project</td><td>City Clean Drive (PRJ001)</td></tr><tr><td>Activity Type</td><td>Installation</td></tr><tr><td>Activity Mode</td><td>Single Submission</td></tr></table></section>
            <section class="detail-section"><h3>Worker Information</h3><table class="detail-table"><tr><td>Worker</td><td id="detail-worker">Ramesh Kumar (USR001)</td></tr><tr><td>Mobile</td><td>9876543210</td></tr></table></section>
            <section class="detail-section"><h3>Comments</h3><textarea class="comment-box" id="submission-comment" maxlength="500" placeholder="Add review comments..."></textarea><span class="comment-count" id="comment-count">0/500</span></section>
            <div class="detail-actions"><button class="reject-button" type="button">Reject</button><button class="approve-button" type="button">Approve</button><button class="request-button" type="button">Request Re-submission</button></div>
        </aside>
    </div>
    <div class="company-toast" id="submission-toast" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/submissions.js') }}"></script>
@endpush
