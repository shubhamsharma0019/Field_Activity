@extends('layout.app')

@section('title', 'Add Activity Type')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/activity-type-create.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span><a href="{{ route('web.activity-types.index') }}">Activity Types</a>
        <span>&rsaquo;</span><span>Add Activity Type</span>
    </nav>

    <div class="page-heading create-type-heading">
        <div><h1>Add New Activity Type</h1><p>Create a field activity type for project assignments.</p></div>
    </div>

    @if ($errors->any())
        <div class="company-toast error">{{ $errors->first() }}</div>
    @endif

    <form id="create-activity-type-form" class="create-type-form" method="POST" action="{{ route('web.activity-types.store') }}">
        @csrf
        <section class="type-form-card">
            <div class="type-heading"><span><svg class="icon"><use href="#list"/></svg></span><div><h2>Activity Details</h2><p>Set up how workers will complete this activity.</p></div></div>
            <label>Activity Name <em>*</em><input name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Enter activity type name"></label>
            <label>Description <em>*</em><textarea id="create-type-description" maxlength="500" placeholder="Enter detailed description of this activity type...">{{ old('description') }}</textarea><small id="create-type-description-count">0/500</small></label>
            <fieldset><legend>Default Activity Mode <em>*</em></legend><label><input type="radio" name="activity_mode" value="single_submission" @checked(old('activity_mode', 'single_submission') === 'single_submission')><span><strong>Single Submission (Photo + Location)</strong><small>Worker submits one time with photo and location</small></span></label><label><input type="radio" name="activity_mode" value="start_end" @checked(old('activity_mode') === 'start_end')><span><strong>Start - End</strong><small>Worker starts activity and ends it with time tracking</small></span></label><label><input type="radio" name="activity_mode" value="continuous_tracking" @checked(old('activity_mode') === 'continuous_tracking')><span><strong>Continuous Tracking</strong><small>Real-time location tracking during activity</small></span></label></fieldset>
        </section>
        <aside class="type-side-panel">
            <section class="type-form-card"><h2>Choose an Icon</h2><p>Pick an icon that represents this activity.</p><div class="large-icon-preview" id="selected-icon-preview">🔧</div><div class="create-icon-options"><button type="button" class="create-icon selected" data-icon="🔧">🔧</button><button type="button" class="create-icon" data-icon="📄">📄</button><button type="button" class="create-icon" data-icon="🧹">🧹</button><button type="button" class="create-icon" data-icon="📍">📍</button><button type="button" class="create-icon" data-icon="📣">📣</button><button type="button" class="create-icon" data-icon="🏪">🏪</button></div></section>
            <section class="type-form-card"><h2>Status</h2><p>Control whether this activity type is available for use.</p><input name="status" value="inactive" type="hidden"><label class="status-switch"><input id="create-type-status" name="status" value="active" type="checkbox" @checked(old('status', 'active') === 'active')><i></i><span>Active</span></label></section>
            <section class="type-tip"><span>i</span><div><strong>Activity Type Tip</strong><p>After saving, you can add this activity type to any project and assign it to workers.</p></div></section>
        </aside>
        <div class="save-type-bar"><a class="secondary-button" href="{{ route('web.activity-types.index') }}">Cancel</a><button class="primary-button" type="submit">Create Activity Type</button></div>
    </form>
    <div id="create-type-toast" class="company-toast" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/activity-type-create.js') }}"></script>
@endpush
