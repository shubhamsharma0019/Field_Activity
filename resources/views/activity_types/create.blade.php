@extends('layout.app')

@section('title', 'Add Work Type')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/activity-type-create.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span><a href="{{ route('web.activity-types.index') }}">Work Types</a>
        <span>&rsaquo;</span><span>Add Work Type</span>
    </nav>

    <div class="page-heading create-type-heading">
        <div><h1>Add Work Type</h1><p>Create a reusable work template for projects.</p></div>
    </div>

    @if ($errors->any())
        <div class="company-toast error">{{ $errors->first() }}</div>
    @endif

    <form id="create-activity-type-form" class="create-type-form" method="POST" action="{{ route('web.activity-types.store') }}">
        @csrf
        <section class="type-form-card">
            <div class="type-heading"><span><svg class="icon"><use href="#list"/></svg></span><div><h2>Activity Details</h2><p>Set up how workers will complete this activity.</p></div></div>
            <label>Work Type Name <em>*</em><input name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Enter work type name"></label>
            <label>Description <em>*</em><textarea id="create-type-description" maxlength="500" placeholder="Enter short description...">{{ old('description') }}</textarea><small id="create-type-description-count">0/500</small></label>
            <fieldset><legend>Default Work Mode <em>*</em></legend><label><input type="radio" name="activity_mode" value="single_submission" @checked(old('activity_mode', 'single_submission') === 'single_submission')><span><strong>Single Submission (Photo + Location)</strong><small>Worker submits one time with photo and location</small></span></label><label><input type="radio" name="activity_mode" value="start_end" @checked(old('activity_mode') === 'start_end')><span><strong>Start - End</strong><small>Worker starts work and ends it with time tracking</small></span></label><label><input type="radio" name="activity_mode" value="continuous_tracking" @checked(old('activity_mode') === 'continuous_tracking')><span><strong>Continuous Tracking</strong><small>Real-time location tracking during work</small></span></label></fieldset>
        </section>
        <aside class="type-side-panel">
            <section class="type-form-card"><h2>Choose an Icon</h2><p>Pick an icon that represents this activity.</p><div class="large-icon-preview" id="selected-icon-preview">🔧</div><div class="create-icon-options"><button type="button" class="create-icon selected" data-icon="🔧">🔧</button><button type="button" class="create-icon" data-icon="📄">📄</button><button type="button" class="create-icon" data-icon="🧹">🧹</button><button type="button" class="create-icon" data-icon="📍">📍</button><button type="button" class="create-icon" data-icon="📣">📣</button><button type="button" class="create-icon" data-icon="🏪">🏪</button></div></section>
            <section class="type-form-card"><h2>Status</h2><p>Control whether this work type is available.</p><input name="status" value="inactive" type="hidden"><label class="status-switch"><input id="create-type-status" name="status" value="active" type="checkbox" @checked(old('status', 'active') === 'active')><i></i><span>Active</span></label></section>
            <section class="type-tip"><span>i</span><div><strong>Flow</strong><p>Work Type -> Project Work -> Assignment.</p></div></section>
        </aside>
        <div class="save-type-bar"><a class="secondary-button" href="{{ route('web.activity-types.index') }}">Cancel</a><button class="primary-button" type="submit">Create Work Type</button></div>
    </form>
    <div id="create-type-toast" class="company-toast" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/activity-type-create.js') }}"></script>
@endpush
