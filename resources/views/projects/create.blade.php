@extends('layout.app')

@section('title', 'Add Project')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/project-create.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span><a href="{{ route('web.projects.index') }}">Projects</a>
        <span>&rsaquo;</span><span>Add Project</span>
    </nav>

    <div class="page-heading create-project-heading">
        <div><h1>Add Project</h1><p>Create a new project and assign it to a company.</p></div>
        <a class="back-projects-button" href="{{ route('web.projects.index') }}">&larr; Back to Projects</a>
    </div>

    @if ($errors->any())
        <div class="company-toast error">{{ $errors->first() }}</div>
    @endif

    <form id="create-project-form" class="create-project-form" method="POST" action="{{ route('web.projects.store') }}">
        @csrf
        <section class="project-form-card">
            <div class="card-title"><span class="title-icon"><svg class="icon"><use href="#folder"/></svg></span><div><h2>Project Information</h2><p>Enter the basic details of the project.</p></div></div>
            <div class="project-fields">
                <label>Project Name <em>*</em><span class="project-input"><svg class="icon"><use href="#folder"/></svg><input name="project_name" value="{{ old('project_name') }}" required placeholder="Enter project name"></span></label>
                <label>Company <span class="optional">(optional for internal project)</span><span class="project-input"><svg class="icon"><use href="#building"/></svg><select name="company_id"><option value="">Internal/Admin Project</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->name }}</option>@endforeach</select></span></label>
                <label>Project Code <span class="optional">(optional)</span><span class="project-input"><svg class="icon"><use href="#file"/></svg><input name="project_code" value="{{ old('project_code') }}" placeholder="Auto generated if empty"></span></label>
                <label>Project Status <em>*</em><span class="project-input"><span class="status-dot"></span><select name="status"><option @selected(old('status', 'Active') === 'Active')>Active</option><option @selected(old('status') === 'On Hold')>On Hold</option><option @selected(old('status') === 'Completed')>Completed</option><option @selected(old('status') === 'Draft')>Draft</option></select></span></label>
                <label class="wide-field">Project Description <span class="optional">(optional)</span><textarea id="project-description" name="description" maxlength="500" placeholder="Describe the project, goals and expected work...">{{ old('description') }}</textarea><small id="project-description-count">0/500</small></label>
            </div>

            <div class="card-title location-title"><span class="title-icon"><svg class="icon"><use href="#pin"/></svg></span><div><h2>Project Location</h2><p>Location helps track field activity correctly.</p></div></div>
            <div class="project-fields">
                <label class="wide-field">Address <em>*</em><span class="project-input"><svg class="icon"><use href="#pin"/></svg><input name="address" value="{{ old('address') }}" required placeholder="Enter complete address"></span></label>
                <label>State <em>*</em><select name="state" required><option value="" selected disabled>Select state</option><option @selected(old('state') === 'Delhi')>Delhi</option><option @selected(old('state') === 'Gujarat')>Gujarat</option><option @selected(old('state') === 'Maharashtra')>Maharashtra</option><option @selected(old('state') === 'Rajasthan')>Rajasthan</option><option @selected(old('state') === 'Uttar Pradesh')>Uttar Pradesh</option><option @selected(old('state') === 'Haryana')>Haryana</option></select></label>
                <label>City <em>*</em><select name="city" required><option value="" selected disabled>Select city</option><option @selected(old('city') === 'New Delhi')>New Delhi</option><option @selected(old('city') === 'Ahmedabad')>Ahmedabad</option><option @selected(old('city') === 'Mumbai')>Mumbai</option><option @selected(old('city') === 'Jaipur')>Jaipur</option><option @selected(old('city') === 'Noida')>Noida</option><option @selected(old('city') === 'Gurgaon')>Gurgaon</option></select></label>
                <label>Pincode <em>*</em><span class="project-input"><svg class="icon"><use href="#pin"/></svg><input name="pincode" value="{{ old('pincode') }}" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter pincode"></span></label>
            </div>
        </section>

        <aside class="project-side-panel">
            <section class="project-form-card"><div class="card-title"><span class="title-icon"><svg class="icon"><use href="#calendar"/></svg></span><div><h2>Project Timeline</h2><p>Select project dates and duration.</p></div></div><div class="timeline-fields"><label>Start Date <em>*</em><input name="start_date" value="{{ old('start_date') }}" type="date" required></label><label>Expected End Date <em>*</em><input name="end_date" value="{{ old('end_date') }}" type="date" required></label><label>Estimated Budget <span class="optional">(optional)</span><span class="project-input"><strong>Rs</strong><input name="budget" value="{{ old('budget') }}" inputmode="decimal" placeholder="Enter estimated budget"></span></label></div></section>
            <section class="project-form-card upload-card"><h2>Project Image</h2><label class="project-upload" for="project-image"><span id="project-image-placeholder"><strong>Upload Project Image</strong><small>PNG, JPG, JPEG (Max 2MB)</small><b>Choose File</b></span><img id="project-image-preview" hidden alt="Project image preview"></label><input id="project-image" type="file" accept="image/png,image/jpeg" hidden></section>
            <section class="project-help"><span>i</span><div><strong>Project Setup</strong><p>You can add activities and assign workers after saving this project.</p></div></section>
        </aside>
        <div class="save-project-bar"><a class="secondary-button" href="{{ route('web.projects.index') }}">Cancel</a><button class="primary-button" type="submit">Save Project</button></div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/project-create.js') }}"></script>
@endpush
