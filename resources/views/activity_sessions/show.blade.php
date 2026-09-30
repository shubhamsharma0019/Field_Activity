@extends('layout.app')
@section('title', 'Activity Session '.$session['code'])
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/submissions.css') }}">
@endpush
@section('content')
    <nav class="breadcrumbs"><a href="{{ route('web.activity-sessions.index') }}">Activity Sessions</a><span>&rsaquo;</span><span>{{ $session['code'] }}</span></nav>
    <div class="page-heading submissions-heading">
        <div><h1>{{ $session['assignment'] }}</h1><p>{{ $session['worker'] }} &middot; {{ $session['code'] }} &middot; {{ $session['duration'] }}</p></div>
        <a class="export-button" href="{{ route('web.activity-sessions.index') }}">Back to Activity Sessions</a>
    </div>
    @if (session('status'))<p class="review-alert" role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="review-alert review-alert-error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="submission-review-page">
        <section class="submission-detail">
            <div class="detail-heading"><h2>Session Evidence</h2><span class="submission-status {{ $session['status'] === 'approved' ? 'approved' : ($session['status'] === 'rejected' ? 'rejected' : 'pending') }}">{{ $session['status_label'] }}</span></div>
            @foreach (['Start Evidence' => 'start', 'End Evidence' => 'end'] as $label => $phase)
                <section class="detail-section"><h3>{{ $label }}</h3><p class="detail-note">{{ $session[$phase.'_time'] }}</p></section>
                <div class="submission-photo-preview">
                    <a class="submission-photo-link" href="{{ $session[$phase.'_image_url'] }}" target="_blank" rel="noopener"><img class="submission-full-photo" src="{{ $session[$phase.'_image_url'] }}" alt="{{ $label }} for {{ $session['assignment'] }}"></a>
                    <div class="submission-photo-missing" hidden role="status"><strong>Photo unavailable</strong><p>The session photo could not be loaded.</p></div>
                </div>
            @endforeach
            <section class="detail-section"><h3>Worker Remark</h3><p class="detail-note">{{ $session['remark'] }}</p></section>
        </section>
        <section class="submission-detail">
            <div class="detail-heading"><h2>Session Details</h2></div>
            <section class="detail-section"><table class="detail-table">
                @foreach (['Session ID' => $session['code'], 'Worker' => $session['worker'], 'Mobile' => $session['worker_mobile'], 'Assignment' => $session['assignment'], 'Project' => $session['project'], 'Company' => $session['company'], 'Activity Mode' => $session['activity_mode'], 'Location' => $session['location'], 'Started At' => $session['start_time'], 'Ended At' => $session['end_time'], 'Duration' => $session['duration'], 'Start GPS' => $session['start_gps'], 'End GPS' => $session['end_gps'], 'Reviewed By' => $session['reviewer'], 'Reviewed At' => $session['reviewed_at']] as $label => $value)
                    <tr><td>{{ $label }}</td><td>{{ $value ?: '-' }}</td></tr>
                @endforeach
            </table></section>
            <form method="POST" action="{{ route('web.activity-sessions.review', $session['id']) }}">
                @csrf
                <section class="detail-section"><label for="session-comment">Review Comment</label><textarea id="session-comment" class="comment-box" name="rejection_reason" maxlength="500" placeholder="Add review comments...">{{ old('rejection_reason', $session['rejection_reason']) }}</textarea></section>
                <div class="detail-actions"><button class="reject-button" type="submit" name="status" value="rejected">Reject</button><button class="approve-button" type="submit" name="status" value="approved">Approve</button></div>
            </form>
        </section>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/submission-review.js') }}"></script>
@endpush