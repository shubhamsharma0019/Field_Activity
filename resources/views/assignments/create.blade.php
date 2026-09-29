<div>
    <!-- Smile, breathe, and go slowly. - Thich Nhat Hanh -->
</div>
@extends('layout.app')

@section('title', 'Create Assignment')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/assignment-create.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs">
        <a href="{{ route('web.dashboard') }}">
            <svg class="icon"><use href="#home"/></svg>
            Dashboard
        </a>
        <span>&rsaquo;</span>
        <a href="{{ route('web.assignments.index') }}">Assignments</a>
        <span>&rsaquo;</span>
        <span>Create Assignment</span>
    </nav>

    <div class="create-assignment-heading">
        <h1>Create New Assignment</h1>
        <p>Create and assign a field activity to a worker.</p>
    </div>

    <form class="create-assignment-form" id="create-assignment-form">
        <section class="assignment-card">
            <div class="assignment-card-title">
                <span>
                    <svg class="icon"><use href="#clipboard"/></svg>
                </span>
                <div>
                    <h2>Assignment Information</h2>
                    <p>Choose the company, project, activity and worker.</p>
                </div>
            </div>

            <div class="assignment-fields">
                <label>
                    Company <em>*</em>
                    <span class="assignment-control">
                        <svg class="icon small"><use href="#building"/></svg>
                        <select required>
                            <option value="">Select company</option>
                            <option>ABC Company</option>
                            <option>XYZ Pvt Ltd</option>
                            <option>GreenTech</option>
                        </select>
                    </span>
                </label>

                <label>
                    Project <em>*</em>
                    <span class="assignment-control">
                        <svg class="icon small"><use href="#folder"/></svg>
                        <select required>
                            <option value="">Select project</option>
                            <option>City Clean Drive</option>
                            <option>Market Survey</option>
                            <option>River Awareness</option>
                        </select>
                    </span>
                </label>

                <label>
                    Assignment Title <em>*</em>
                    <span class="assignment-control">
                        <svg class="icon small"><use href="#file"/></svg>
                        <input required type="text" placeholder="Enter assignment title">
                    </span>
                </label>

                <label>
                    Activity Type <em>*</em>
                    <span class="assignment-control">
                        <svg class="icon small"><use href="#list"/></svg>
                        <select required>
                            <option value="">Select activity type</option>
                            <option>Installation</option>
                            <option>Survey</option>
                            <option>Cleaning</option>
                            <option>Campaign</option>
                        </select>
                    </span>
                </label>

                <label>
                    Assign to Worker <em>*</em>
                    <span class="assignment-control">
                        <svg class="icon small"><use href="#users"/></svg>
                        <select required>
                            <option value="">Select worker</option>
                            <option>Ramesh Kumar</option>
                            <option>Priya Sharma</option>
                            <option>Amit Singh</option>
                        </select>
                    </span>
                </label>

                <label>
                    Due Date <em>*</em>
                    <span class="assignment-control">
                        <svg class="icon small"><use href="#calendar"/></svg>
                        <input required type="date">
                    </span>
                </label>

                <label class="assignment-wide-field">
                    Description
                    <textarea id="assignment-notes" maxlength="500" placeholder="Enter assignment description (optional)"></textarea>
                    <small id="assignment-notes-count">0/500</small>
                </label>
            </div>
        </section>

        <aside class="assignment-side-panel">
            <section class="assignment-side-card">
                <h2>Activity Settings</h2>
                <p>Set how the worker will complete this assignment.</p>

                <label>
                    Activity Mode <em>*</em>
                    <select required>
                        <option>Single Submission (Photo + Location)</option>
                        <option>Start - End</option>
                        <option>Continuous Tracking</option>
                    </select>
                </label>

                <label>
                    Priority
                    <select>
                        <option>Normal</option>
                        <option>High</option>
                        <option>Low</option>
                    </select>
                </label>

                <label>
                    Status <em>*</em>
                    <select required>
                        <option>In Progress</option>
                        <option>Pending</option>
                        <option>On Hold</option>
                    </select>
                </label>
            </section>

            <section class="assignment-help">
                <span>i</span>
                <div>
                    <strong>Assignment Information</strong>
                    <p>The selected worker can see this task and submit activity updates from their account.</p>
                </div>
            </section>
        </aside>

        <div class="assignment-save-bar">
            <a class="assignment-cancel-button" href="{{ route('web.assignments.index') }}">Cancel</a>
            <button class="assignment-save-button" type="submit">Save Assignment</button>
        </div>
    </form>

    <div class="company-toast" id="assignment-create-toast" hidden></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/assignment-create.js') }}"></script>
@endpush
