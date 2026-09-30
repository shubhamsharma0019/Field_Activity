@extends('layout.app')
@section('title', 'Submission Details')
@push('styles')<link rel="stylesheet" href="{{ asset('css/submissions.css') }}">@endpush
@section('content')
    <nav class="breadcrumbs"><a href="{{ route('web.submissions.index') }}">Submissions</a><span>&rsaquo;</span><span>Submission Details</span></nav>
    <div class="page-heading submissions-heading"><div><h1>{{ $submission['title'] }}</h1><p>{{ $submission['worker'] }} · {{ $submission['submitted_at'] }}</p></div><a class="export-button" href="{{ route('web.submissions.index') }}">Back to Submissions</a></div>
    @if (session('status'))<p class="review-alert" role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="review-alert review-alert-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="submission-review-page">
        <section class="submission-detail">
            <div class="detail-heading"><h2>Submitted Photo</h2><span class="submission-status {{ $submission['status'] }}">{{ $submission['status_label'] }}</span></div>
            <div class="submission-photo-preview">
                <a class="submission-photo-link" href="{{ $submission['image_url'] }}" target="_blank" rel="noopener"><img class="submission-full-photo" src="{{ $submission['image_url'] }}" alt="Photo for {{ $submission['title'] }}"></a>
                <div class="submission-photo-missing" hidden role="status">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 5-5 4 4 3-3 6 6"/></svg>
                    <strong>Photo unavailable</strong><p>The submitted photo could not be loaded.</p>
                </div>
            </div>
            <section class="detail-section"><h3>Worker Remark</h3><p class="detail-note">{{ $submission['remark'] }}</p></section>
            <form method="POST" action="{{ route('web.submissions.review', $submission['id']) }}">
                @csrf
                <section class="detail-section"><label for="review-comment">Review Comment</label><textarea id="review-comment" class="comment-box" name="rejection_reason" maxlength="500" placeholder="Add review comments...">{{ old('rejection_reason', $submission['rejection_reason']) }}</textarea></section>
                <div class="detail-actions"><button class="reject-button" type="submit" name="approval_status" value="rejected">Reject</button><button class="approve-button" type="submit" name="approval_status" value="approved">Approve</button></div>
            </form>
        </section>
        <section class="submission-detail">
            <div class="detail-heading"><h2>Submission Details</h2></div>
            <section class="detail-section"><table class="detail-table">
                @foreach (['Assignment' => $submission['title'], 'Project' => $submission['project'].' ('.$submission['project_code'].')', 'Activity Type' => $submission['activity_type'], 'Mode' => $submission['mode'], 'Worker' => $submission['worker'], 'Mobile' => $submission['worker_mobile'], 'Location' => $submission['location'], 'GPS' => $submission['latitude'].', '.$submission['longitude'], 'Submitted' => $submission['submitted_at'], 'Reviewed By' => $submission['reviewer'], 'Reviewed At' => $submission['reviewed_at']] as $label => $value)
                    <tr><td>{{ $label }}</td><td>{{ $value ?: '-' }}</td></tr>
                @endforeach
            </table></section>

        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/submission-review.js') }}"></script>
@endpush