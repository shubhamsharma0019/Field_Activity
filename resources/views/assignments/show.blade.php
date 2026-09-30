@extends('layout.app')
@section('title', 'Assignment '.$details['code'])
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/assignments.css') }}">
@endpush
@section('content')
    <nav class="breadcrumbs"><a href="{{ route('web.assignments.index') }}">Assignments</a><span>&rsaquo;</span><span>{{ $details['code'] }}</span></nav>
    <div class="page-heading assignments-heading"><div><h1>Assignment Details</h1><p>{{ $details['title'] }}</p></div><a class="secondary-button" href="{{ route('web.assignments.index') }}">Back to Assignments</a></div>
    <section class="assignment-form-card">
        <div class="form-heading"><h2>{{ $details['code'] }}</h2><span class="assignment-status {{ str_replace('_', '-', $details['status']) }}">{{ $details['status_label'] }}</span></div>
        <form aria-label="Assignment details">
            @foreach (['Company' => $details['company'], 'Project' => $details['project'], 'Project Activity' => $details['title'], 'Activity Type' => $details['activity_type'], 'Activity Mode' => $details['mode'], 'Worker' => $details['worker'], 'Worker Code' => $details['worker_code'], 'Target Quantity' => $details['target'], 'Completed Quantity' => $details['completed'], 'Assigned Date' => $assignment->assigned_date?->format('d M Y'), 'Due Date' => $details['due_date'], 'Tracking Required' => $assignment->tracking_required ? 'Yes' : 'No'] as $label => $value)
                <label>{{ $label }}<input readonly value="{{ $value ?? '-' }}"></label>
            @endforeach
            <label>Progress<input readonly value="{{ $details['progress'] }}%"></label>
            <label>Instructions<textarea readonly>{{ $assignment->projectActivity?->instructions ?: 'No instructions added.' }}</textarea></label>
            <div class="assignment-actions"><a class="secondary-button" href="{{ route('web.assignments.index') }}">Back to Assignments</a></div>
        </form>
    </section>
@endsection
