@extends('layout.app')
@section('title', 'Add New Activity Type')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/activity-types.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-types-fix.css') }}">
@endpush
@section('content')
    <nav class="breadcrumbs"><a href="{{ route('web.activity-types.index') }}">Activity Types</a><span>&rsaquo;</span><span>Add New Activity Type</span></nav>
    <div class="page-heading activity-heading"><div><h1>Add New Activity Type</h1><p>Set up the activity mode and tracking preferences.</p></div><a class="secondary-button back-activity-types-button" href="{{ route('web.activity-types.index') }}">&larr; Back to Activity Types</a></div>
    @if ($errors->any())
        <div class="activity-create-errors" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
        <section class="activity-form-card activity-create-page">
            <div class="form-heading">
                <h2>Add New Activity Type</h2>
                
            </div>
            <form id="activity-form" method="POST" action="{{ route('web.activity-types.store') }}">
                @csrf
                <label>Activity Name <em>*</em>
                    <input id="activity-name" name="name" type="text" required maxlength="100" value="{{ old('name') }}" placeholder="Enter activity type name">
                </label>
                <label>Description
                    <textarea id="activity-description" maxlength="500" placeholder="Optional display note for admin planning">{{ old('description') }}</textarea>
                    <small id="activity-description-count">0/500</small>
                </label>
                <fieldset>
                    <legend>Default Activity Mode <em>*</em></legend>
                    <label><input type="radio" name="activity_mode" value="single_submission" @checked(old('activity_mode', 'single_submission') === 'single_submission')> Single Submission <small>Worker submits one time with photo and location</small></label>
                    <label><input type="radio" name="activity_mode" value="start_end" @checked(old('activity_mode') === 'start_end')> Start - End <small>Worker starts activity and ends it with time tracking</small></label>
                    <label><input type="radio" name="activity_mode" value="continuous_tracking" @checked(old('activity_mode') === 'continuous_tracking')> Continuous Tracking <small>Real-time location tracking during activity</small></label>
                </fieldset>
                <label class="switch-label">Tracking Required
                    <span><input id="tracking-required" name="tracking_required" value="1" type="checkbox" @checked(old('tracking_required'))><i></i> Required</span>
                </label>
                <label class="switch-label">Status <em>*</em>
                    <input name="status" value="inactive" type="hidden">
                    <span><input id="activity-active" name="status" value="active" type="checkbox" @checked(old('status', 'active') === 'active')><i></i> Active</span>
                </label>
                <div class="activity-form-actions">
                    <a class="secondary-button" href="{{ route('web.activity-types.index') }}">Cancel</a>
                    <button class="primary-button" type="submit">Create Activity Type</button>
                </div>
            </form>
        </section>
@endsection
@push('scripts')
    <script src="{{ asset('js/activity-types.js') }}"></script>
@endpush
