@extends('layout.app')
@section('title', 'Edit Project')
@push('styles')<link rel="stylesheet" href="{{ asset('css/user-create.css') }}">@endpush
@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.projects.index') }}">Projects</a><span>&rsaquo;</span><a href="{{ route('web.projects.show', $project) }}">{{ $project->name }}</a><span>&rsaquo;</span><span>Edit</span></nav>
    <div class="page-heading"><div><h1>Edit Project</h1><p>Update project details, location and schedule.</p></div></div>
    @if ($errors->any())<div class="user-form-errors" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('web.projects.update', $project) }}">
        @csrf
        @method('PUT')
        <section class="user-form-card"><div class="user-fields">
            @foreach (['name' => ['Project Name', 150], 'area' => ['Address / Area', 150], 'city' => ['City', 100], 'state' => ['State', 100]] as $field => [$label, $max])
                <label>{{ $label }} <em>*</em><span class="user-input"><input name="{{ $field }}" required maxlength="{{ $max }}" value="{{ old($field, $project->$field) }}"></span></label>
            @endforeach
            @foreach (['start_date' => 'Start Date', 'end_date' => 'End Date'] as $field => $label)
                <label>{{ $label }} <em>*</em><span class="user-input"><input type="date" name="{{ $field }}" required value="{{ old($field, $project->$field?->toDateString()) }}"></span></label>
            @endforeach
            <label>Status<span class="user-input"><select name="status">@foreach (['active', 'on_hold', 'completed', 'draft'] as $status)<option value="{{ $status }}" @selected(old('status', $project->status) === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>@endforeach</select></span></label>
            <label>Company<span class="user-input"><input readonly value="{{ $project->company?->name ?? 'Internal Project' }}"></span></label>
            <label>Project Code<span class="user-input"><input readonly value="{{ $project->project_code }}"></span></label>
        </div><div class="additional-fields"><label>Description<textarea name="description" maxlength="500">{{ old('description', $project->description) }}</textarea></label></div></section>
        <div class="save-user-bar"><a class="secondary-button" href="{{ route('web.projects.show', $project) }}">Cancel</a><button class="primary-button" type="submit">Save Changes</button></div>
    </form>
@endsection
