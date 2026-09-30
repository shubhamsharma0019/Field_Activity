@extends('layout.app')

@section('title', 'Add Project Work')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/assignments.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><a href="{{ route('web.assignments.index') }}">Assignments</a><span>&rsaquo;</span><span>Add Project Work</span></nav>
    <div class="page-heading assignments-heading"><div><h1>Add Project Work</h1><p>Select a project and define the work that can be assigned to workers.</p></div></div>

    @if ($errors->any())
        <div class="company-toast error">{{ $errors->first() }}</div>
    @endif

    <section class="assignment-form-card project-activity-create-card">
        <div class="form-heading"><h2>Project Work</h2></div>
        <form class="project-activity-create-form" method="POST" action="{{ route('web.project-activities.store') }}">
            @csrf
            <label>Project <em>*</em><select name="project_id" required><option value="" selected disabled>Select project</option>@foreach($projects as $project)<option value="{{ $project->id }}" data-start-date="{{ $project->start_date?->toDateString() }}" data-end-date="{{ $project->end_date?->toDateString() }}" @selected((string) old('project_id', $selectedProjectId ?? '') === (string) $project->id)>{{ $project->name }}</option>@endforeach</select></label>
            <label>Work Type <em>*</em><select name="activity_type_id" required><option value="" selected disabled>Select work type</option>@foreach($activityTypes as $type)<option value="{{ $type->id }}" @selected((string) old('activity_type_id') === (string) $type->id)>{{ $type->name }} - {{ str($type->activity_mode)->replace('_', ' ')->title() }}</option>@endforeach</select></label>
            <label>Work Name <em>*</em><input name="name" value="{{ old('name') }}" required maxlength="150" placeholder="Example: Poster pasting in Sector 18"></label>
            <label>Target Quantity<input name="target_quantity" value="{{ old('target_quantity') }}" type="number" min="1" placeholder="Example: 50"></label>
            <label>Expected Duration Minutes<input name="expected_duration_minutes" value="{{ old('expected_duration_minutes') }}" type="number" min="1" placeholder="Optional"></label>
            <label>Start Date<input name="start_date" value="{{ old('start_date') }}" type="date"><small class="project-date-note"></small></label>
            <label>End Date<input name="end_date" value="{{ old('end_date') }}" type="date"></label>
            <label>Status<select name="status"><option value="active" @selected(old('status', 'active') === 'active')>Active</option><option value="pending" @selected(old('status') === 'pending')>Pending</option><option value="on_hold" @selected(old('status') === 'on_hold')>On Hold</option></select></label>
            <label class="wide-field">Instructions<textarea name="instructions" maxlength="1000" placeholder="Worker instructions...">{{ old('instructions') }}</textarea></label>
            <div class="assignment-actions"><a class="secondary-button" href="{{ route('web.assignments.index') }}">Cancel</a><button class="primary-button" type="submit">Save Project Work</button></div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const projectSelect = document.querySelector('[name="project_id"]');
            const startInput = document.querySelector('[name="start_date"]');
            const endInput = document.querySelector('[name="end_date"]');
            const note = document.querySelector('.project-date-note');

            function syncProjectDates() {
                const selected = projectSelect?.selectedOptions?.[0];
                if (!selected || !selected.value) {
                    return;
                }

                const projectStart = selected.dataset.startDate || '';
                const projectEnd = selected.dataset.endDate || '';

                startInput.min = projectStart;
                startInput.max = projectEnd;
                endInput.min = projectStart;
                endInput.max = projectEnd;

                if (!startInput.value || (projectStart && startInput.value < projectStart)) {
                    startInput.value = projectStart;
                }

                if (!endInput.value || (projectEnd && endInput.value > projectEnd) || (startInput.value && endInput.value < startInput.value)) {
                    endInput.value = projectEnd || startInput.value;
                }

                endInput.min = startInput.value || projectStart;
                note.textContent = projectStart && projectEnd ? `Project range: ${projectStart} to ${projectEnd}` : '';
            }

            projectSelect?.addEventListener('change', syncProjectDates);
            startInput?.addEventListener('change', () => {
                if (endInput.value && endInput.value < startInput.value) {
                    endInput.value = startInput.value;
                }
                endInput.min = startInput.value || startInput.min;
            });

            syncProjectDates();
        });
    </script>
@endpush
