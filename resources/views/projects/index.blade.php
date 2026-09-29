@extends('layout.app')

@section('title', 'Projects')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/projects.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
        <span>&rsaquo;</span><span aria-current="page">Projects</span>
    </nav>

    <div class="page-heading projects-heading">
        <div><h1>Projects</h1><p>Manage all projects, assign workers and track progress.</p></div>
        <a class="primary-button" href="{{ route('web.projects.create') }}"><span>+</span>Add Project</a>
    </div>

    <div class="project-stats">
        <article class="project-stat blue"><span><svg class="icon"><use href="#folder"/></svg></span><div><h2>Total Projects</h2><strong>28</strong><p>+ 5 this month</p></div></article>
        <article class="project-stat green"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Active Projects</h2><strong>18</strong><p>+ 4 this month</p></div></article>
        <article class="project-stat orange"><span>Ⅱ</span><div><h2>On Hold</h2><strong>6</strong><p>+ 1 this month</p></div></article>
        <article class="project-stat purple"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Completed</h2><strong>4</strong><p>+ 0 this month</p></div></article>
    </div>

    <div class="project-filters">
        <label class="company-search"><svg class="icon"><use href="#search"/></svg><input id="project-search" type="search" placeholder="Search projects by name, company or location..."></label>
        <select id="project-company"><option value="all">All Companies</option><option>ABC Company</option><option>XYZ Pvt Ltd</option><option>GreenTech</option></select>
        <select id="project-status"><option value="all">All Status</option><option value="Active">Active</option><option value="On Hold">On Hold</option><option value="Completed">Completed</option></select>
        <input id="project-date" type="date" aria-label="Project start date">
        <button id="reset-projects" class="reset-button" type="button">Reset</button>
    </div>

    <section class="project-panel">
        <div class="company-table-scroll">
            <table class="project-table">
                <thead><tr><th><input id="select-all-projects" type="checkbox" aria-label="Select all projects"></th><th>#</th><th>Project Name</th><th>Company</th><th>Location</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Progress</th><th>Workers</th><th>Actions</th></tr></thead>
                <tbody id="project-rows"></tbody>
            </table>
            <p class="empty-state" id="projects-empty" hidden>No projects found.</p>
        </div>
        <div class="company-footer"><label class="page-size">Show <select id="project-page-size"><option>5</option><option selected>10</option></select> entries</label><span class="company-count" id="project-count"></span><nav class="pagination" id="project-pagination" aria-label="Project pages"></nav></div>
    </section>
    <dialog id="project-dialog"><button class="dialog-close" id="close-project-dialog" type="button">×</button><h2 id="project-dialog-title"></h2><p id="project-dialog-text"></p></dialog>
    <div class="company-toast" id="project-toast" role="status" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/projects.js') }}"></script>
@endpush
