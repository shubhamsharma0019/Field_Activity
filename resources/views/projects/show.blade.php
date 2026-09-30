@extends('layout.app')
@section('title', 'Project Details')
@push('styles')<link rel="stylesheet" href="{{ asset('css/user-create.css') }}">@endpush
@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.projects.index') }}">Projects</a><span>&rsaquo;</span><span>Project Details</span></nav>
    <div class="page-heading"><div><h1>{{ $project->name }}</h1><p>{{ $project->project_code }}</p></div><a class="primary-button" href="{{ route('web.projects.edit', $project) }}">Edit Project</a></div>
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    <section class="user-form-card"><h2>Project Information</h2>
        <dl class="user-fields user-detail-fields">
            @foreach (['Company' => $project->company?->name ?? 'Internal Project', 'Type' => ucfirst($project->project_type), 'Description' => $project->description, 'Location' => implode(', ', array_filter([$project->area, $project->city, $project->state])), 'Start Date' => $project->start_date?->format('d M Y'), 'End Date' => $project->end_date?->format('d M Y'), 'Status' => str($project->status)->replace('_', ' ')->title(), 'Created By' => $project->creator?->name, 'Activities' => $project->activities_count, 'Assignments' => $project->assignments_count, 'Workers' => $workers, 'Target' => $target, 'Approved' => $completed, 'Remaining' => max(0, $target - $completed), 'Progress' => ($target > 0 ? round(min(100, $completed / $target * 100)) : 0).'%'] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value ?? '-' }}</dd></div>
            @endforeach
        </dl>
    </section>
    <div class="save-user-bar"><a class="secondary-button" href="{{ route('web.projects.index') }}">Back to Projects</a></div>
@endsection
