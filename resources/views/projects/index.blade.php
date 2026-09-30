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
        <div><h1>Projects</h1><p>Manage company and internal projects, assign workers and track target progress.</p></div>
        <a class="primary-button" href="{{ route('web.projects.create') }}"><span>+</span>Add Project</a>
    </div>

    @if (session('status'))
        <div class="company-toast">{{ session('status') }}</div>
    @endif

    <div class="project-stats">
        <article class="project-stat blue"><span><svg class="icon"><use href="#folder"/></svg></span><div><h2>Total Projects</h2><strong>{{ number_format($summary['total']) }}</strong><p>+ {{ number_format($summary['total_month']) }} this month</p></div></article>
        <article class="project-stat green"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Active Projects</h2><strong>{{ number_format($summary['active']) }}</strong><p>+ {{ number_format($summary['active_month']) }} this month</p></div></article>
        <article class="project-stat orange"><span>Ⅱ</span><div><h2>On Hold</h2><strong>{{ number_format($summary['on_hold']) }}</strong><p>+ {{ number_format($summary['on_hold_month']) }} this month</p></div></article>
        <article class="project-stat purple"><span><svg class="icon"><use href="#check"/></svg></span><div><h2>Completed</h2><strong>{{ number_format($summary['completed']) }}</strong><p>+ {{ number_format($summary['completed_month']) }} this month</p></div></article>
    </div>

    <div class="project-filters">
        <label class="company-search"><svg class="icon"><use href="#search"/></svg><input id="project-search" type="search" placeholder="Search projects by name, company or location..."></label>
        <select id="project-company"><option value="all">All Companies</option><option value="internal">Internal Projects</option>@foreach ($companies as $company)<option value="{{ $company['id'] }}">{{ $company['name'] }}</option>@endforeach</select>
        <select id="project-status"><option value="all">All Status</option><option value="draft">Draft</option><option value="active">Active</option><option value="on_hold">On Hold</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>
        <input id="project-date" type="date" aria-label="Project start date">
        <button id="reset-projects" class="reset-button" type="button">Reset</button>
    </div>

    <section class="project-panel">
        <div class="company-table-scroll">
            <table class="project-table">
                <thead><tr><th><input id="select-all-projects" type="checkbox" aria-label="Select all projects"></th><th>#</th><th>Project Name</th><th>Type</th><th>Company</th><th>Location</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Progress</th><th>Workers</th><th>Actions</th></tr></thead>
                <tbody id="project-rows"></tbody>
            </table>
            <p class="empty-state" id="projects-empty" hidden>No projects found.</p>
        </div>
        <div class="company-footer"><label class="page-size">Show <select id="project-page-size"><option>5</option><option selected>10</option></select> entries</label><span class="company-count" id="project-count"></span><nav class="pagination" id="project-pagination" aria-label="Project pages"></nav></div>
    </section>
    <dialog id="project-dialog"><button class="dialog-close" id="close-project-dialog" type="button">×</button><h2 id="project-dialog-title"></h2><p id="project-dialog-text"></p></dialog>
    <dialog id="delete-project-dialog" aria-labelledby="delete-project-title" aria-describedby="delete-project-message">
        <h2 id="delete-project-title">Delete project?</h2><p id="delete-project-message"></p>
        <div class="project-dialog-actions"><button class="secondary-button" id="cancel-project-delete" type="button" autofocus>Cancel</button><button class="danger-button" id="confirm-project-delete" type="button">Delete</button></div>
    </dialog>
    <div class="company-toast" id="project-toast" role="status" hidden></div>
@endsection

@push('scripts')
    <script>
        window.projectsData = @json($projects);
    </script>
    <script src="{{ asset('js/projects.js') }}"></script>
@endpush
