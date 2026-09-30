@extends('layout.app')

@section('title', 'Settings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endpush

@section('content')
<nav class="breadcrumbs">
    <a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a>
    <span>&rsaquo;</span>
    <span>Settings</span>
</nav>

<div class="settings-heading">
    <h1>Settings</h1>
    <p>Manage organization details, tracking rules and admin alerts.</p>
</div>

@if (session('status'))
    <div class="company-toast">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="company-toast error">{{ $errors->first() }}</div>
@endif

@unless ($canPersistSettings)
    <div class="company-toast">Settings are showing defaults. Run <strong>php artisan migrate</strong> once to enable saving.</div>
@endunless

<form id="settings-form" method="POST" action="{{ route('web.settings.update') }}" class="settings-page-form">
    @csrf

    <section class="settings-card">
        <div class="settings-card-head">
            <span><svg class="icon"><use href="#building"/></svg></span>
            <div>
                <h2>Organization Details</h2>
                <p>Shown in the admin panel and exported reports.</p>
            </div>
        </div>
        <div class="settings-fields">
            <label class="wide">Organization Name *<input name="organization_name" value="{{ old('organization_name', $settings['organization_name']) }}" required></label>
            <label>Email *<input name="email" type="email" value="{{ old('email', $settings['email']) }}" required></label>
            <label>Phone<input name="phone" value="{{ old('phone', $settings['phone']) }}"></label>
            <label>City<input name="city" value="{{ old('city', $settings['city']) }}"></label>
            <label>Timezone
                <select name="timezone">
                    <option value="Asia/Kolkata" @selected($settings['timezone'] === 'Asia/Kolkata')>Asia/Kolkata</option>
                    <option value="UTC" @selected($settings['timezone'] === 'UTC')>UTC</option>
                </select>
            </label>
        </div>
    </section>

    <section class="settings-card">
        <div class="settings-card-head">
            <span><svg class="icon"><use href="#pin"/></svg></span>
            <div>
                <h2>Field App Settings</h2>
                <p>Defaults used while workers submit activity proof.</p>
            </div>
        </div>
        <div class="settings-fields">
            <label>Map Provider
                <select name="map_provider">
                    <option @selected($settings['map_provider'] === 'OpenStreetMap')>OpenStreetMap</option>
                    <option @selected($settings['map_provider'] === 'Google Maps')>Google Maps</option>
                </select>
            </label>
            <label>Session Auto End (hours)<input name="session_auto_end_hours" type="number" min="1" max="72" value="{{ old('session_auto_end_hours', $settings['session_auto_end_hours']) }}"></label>
            <label>GPS Accuracy (meters)<input name="location_accuracy_meters" type="number" min="1" max="500" value="{{ old('location_accuracy_meters', $settings['location_accuracy_meters']) }}"></label>
        </div>
        <div class="settings-toggle-grid">
            <label class="switch-row">
                <span>Require GPS on photo</span>
                <span class="switch"><input name="require_location_submissions" value="1" type="checkbox" @checked(old('require_location_submissions', $settings['require_location_submissions']))><i></i></span>
            </label>
            <label class="switch-row">
                <span>Track sessions</span>
                <span class="switch"><input name="track_location_during_session" value="1" type="checkbox" @checked(old('track_location_during_session', $settings['track_location_during_session']))><i></i></span>
            </label>
        </div>
    </section>

    <section class="settings-card">
        <div class="settings-card-head">
            <span><svg class="icon"><use href="#bell"/></svg></span>
            <div>
                <h2>Admin Alerts</h2>
                <p>Choose which updates should notify the admin.</p>
            </div>
        </div>
        <div class="settings-toggle-grid">
            <label class="switch-row">
                <span>Photo submissions</span>
                <span class="switch"><input name="new_submission_alert" value="1" type="checkbox" @checked(old('new_submission_alert', $settings['new_submission_alert']))><i></i></span>
            </label>
            <label class="switch-row">
                <span>Assignment updates</span>
                <span class="switch"><input name="assignment_update_alert" value="1" type="checkbox" @checked(old('assignment_update_alert', $settings['assignment_update_alert']))><i></i></span>
            </label>
            <label class="switch-row">
                <span>Session start/end</span>
                <span class="switch"><input name="session_alert" value="1" type="checkbox" @checked(old('session_alert', $settings['session_alert']))><i></i></span>
            </label>
        </div>
    </section>

    <div class="settings-actions">
        <button class="save-settings" type="submit">Save Settings</button>
        <button class="test-button" type="button" id="test-notification">Test Alert</button>
    </div>
</form>

<div id="settings-toast" class="company-toast" hidden></div>
@endsection

@push('scripts')
<script src="{{ asset('js/settings.js') }}"></script>
@endpush
