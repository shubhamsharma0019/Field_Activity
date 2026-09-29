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
    </div>

    <form id="create-project-form" class="create-project-form">
        <section class="project-form-card">
            <div class="card-title"><span class="title-icon"><svg class="icon"><use href="#folder"/></svg></span><div><h2>Project Information</h2><p>Enter the basic details of the project.</p></div></div>
            <div class="project-fields">
                <label>Project Name <em>*</em><span class="project-input"><svg class="icon"><use href="#folder"/></svg><input name="project_name" required placeholder="Enter project name"></span></label>
                <label>Company <em>*</em><span class="project-input"><svg class="icon"><use href="#building"/></svg><select name="company" required><option value="" selected disabled>Select company</option><option>ABC Company</option><option>XYZ Pvt Ltd</option><option>GreenTech</option><option>BuildWell</option></select></span></label>
                <label>Project Code <span class="optional">(optional)</span><span class="project-input"><svg class="icon"><use href="#file"/></svg><input name="project_code" placeholder="Enter project code"></span></label>
                <label>Project Status <em>*</em><span class="project-input"><span class="status-dot"></span><select name="status"><option>Active</option><option>On Hold</option><option>Completed</option></select></span></label>
                <label class="wide-field">Project Description <span class="optional">(optional)</span><textarea id="project-description" name="description" maxlength="500" placeholder="Describe the project, goals and expected work..."></textarea><small id="project-description-count">0/500</small></label>
            </div>

            <div class="card-title location-title"><span class="title-icon"><svg class="icon"><use href="#pin"/></svg></span><div><h2>Project Location</h2><p>Location helps track field activity correctly.</p></div></div>
            <div class="project-fields">
                <label class="wide-field">Address <em>*</em><span class="project-input"><svg class="icon"><use href="#pin"/></svg><input name="address" required placeholder="Enter complete address"></span></label>
                <label>State <em>*</em><select name="state" required><option value="" selected disabled>Select state</option><option>Delhi</option><option>Gujarat</option><option>Maharashtra</option><option>Rajasthan</option></select></label>
                <label>City <em>*</em><select name="city" required><option value="" selected disabled>Select city</option><option>New Delhi</option><option>Ahmedabad</option><option>Mumbai</option><option>Jaipur</option></select></label>
                <label>Pincode <em>*</em><span class="project-input"><svg class="icon"><use href="#pin"/></svg><input name="pincode" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter pincode"></span></label>
            </div>
        </section>

        <aside class="project-side-panel">
            <section class="project-form-card"><div class="card-title"><span class="title-icon"><svg class="icon"><use href="#calendar"/></svg></span><div><h2>Project Timeline</h2><p>Select project dates and duration.</p></div></div><div class="timeline-fields"><label>Start Date <em>*</em><input name="start_date" type="date" required></label><label>Expected End Date <em>*</em><input name="end_date" type="date" required></label><label>Estimated Budget <span class="optional">(optional)</span><span class="project-input"><strong>₹</strong><input name="budget" inputmode="decimal" placeholder="Enter estimated budget"></span></label></div></section>
            <section class="project-form-card upload-card"><h2>Project Image</h2><label class="project-upload" for="project-image"><span id="project-image-placeholder"><strong>Upload Project Image</strong><small>PNG, JPG, JPEG (Max 2MB)</small><b>Choose File</b></span><img id="project-image-preview" hidden alt="Project image preview"></label><input id="project-image" type="file" accept="image/png,image/jpeg" hidden></section>
            <section class="project-help"><span>i</span><div><strong>Project Setup</strong><p>You can add activities and assign workers after saving this project.</p></div></section>
        </aside>
        <div class="save-project-bar"><a class="secondary-button" href="{{ route('web.projects.index') }}">Cancel</a><button class="primary-button" type="submit">Save Project</button></div>
    </form>
    <div id="project-create-toast" class="company-toast" role="status" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/project-create.js') }}"></script>
@endpush
