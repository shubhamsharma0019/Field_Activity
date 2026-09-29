@extends('layout.app')

@section('title', 'Settings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endpush

@section('content')
<nav class="breadcrumbs"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><span>Settings</span></nav>
<div class="settings-heading"><h1>Settings</h1><p>Manage your system settings, preferences and configurations.</p></div>
<nav class="settings-tabs"><b><svg class="icon"><use href="#settings"/></svg> General</b><span>User Management</span><span>Notifications</span><span>System Configuration</span><span>Security</span><span>Appearance</span><span>Integration</span></nav>

@if (session('status'))
    <div class="company-toast">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="company-toast error">{{ $errors->first() }}</div>
@endif

@unless ($canPersistSettings)
    <div class="company-toast">Settings are showing defaults. Run <strong>php artisan migrate</strong> once to enable saving.</div>
@endunless

<form id="settings-form" method="POST" action="{{ route('web.settings.update') }}">
    @csrf
    <div class="settings-grid">
        <section class="settings-card">
            <div class="settings-title"><span><svg class="icon"><use href="#building"/></svg></span><div><h2>Organization Information</h2><p>Basic information about your organization.</p></div></div>
            <div class="settings-fields">
                <label class="wide">Organization Name *<input name="organization_name" value="{{ old('organization_name', $settings['organization_name']) }}" required></label>
                <label class="wide">Organization Type<select name="organization_type"><option @selected($settings['organization_type'] === 'Company')>Company</option><option @selected($settings['organization_type'] === 'Internal')>Internal</option></select></label>
                <label>Email *<input name="email" type="email" value="{{ old('email', $settings['email']) }}" required></label>
                <label>Phone<input name="phone" value="{{ old('phone', $settings['phone']) }}"></label>
                <label class="wide">Address<textarea name="address">{{ old('address', $settings['address']) }}</textarea></label>
                <label>City<input name="city" value="{{ old('city', $settings['city']) }}"></label>
                <label>Country<input name="country" value="{{ old('country', $settings['country']) }}"></label>
                <label>Timezone<select name="timezone"><option value="Asia/Kolkata" @selected($settings['timezone'] === 'Asia/Kolkata')>(GMT+05:30) Asia/Kolkata</option><option value="UTC" @selected($settings['timezone'] === 'UTC')>UTC</option></select></label>
                <label>Date Format<select name="date_format"><option @selected($settings['date_format'] === 'DD MMM YYYY')>DD MMM YYYY</option><option @selected($settings['date_format'] === 'YYYY-MM-DD')>YYYY-MM-DD</option></select></label>
                <label>Time Format<select name="time_format"><option @selected($settings['time_format'] === '12 Hour (AM/PM)')>12 Hour (AM/PM)</option><option @selected($settings['time_format'] === '24 Hour')>24 Hour</option></select></label>
            </div>
            <button class="save-settings" type="submit">Save Changes</button>
        </section>

        <div class="settings-column">
            <section class="settings-card">
                <div class="settings-title"><span><svg class="icon"><use href="#list"/></svg></span><div><h2>System Preferences</h2><p>Configure system wide preferences.</p></div></div>
                <div class="settings-fields">
                    <label>Default Map Provider<select name="map_provider"><option @selected($settings['map_provider'] === 'Google Maps')>Google Maps</option><option @selected($settings['map_provider'] === 'OpenStreetMap')>OpenStreetMap</option></select></label>
                    <label>Default View<select name="default_view"><option @selected($settings['default_view'] === 'Map View')>Map View</option><option @selected($settings['default_view'] === 'Table View')>Table View</option></select></label>
                    <label class="wide">Session Auto End (hours)<input name="session_auto_end_hours" type="number" min="1" max="72" value="{{ old('session_auto_end_hours', $settings['session_auto_end_hours']) }}"></label>
                    <label class="wide">Photo Compression<select name="photo_compression"><option @selected($settings['photo_compression'] === 'Low')>Low</option><option @selected($settings['photo_compression'] === 'Medium')>Medium</option><option @selected($settings['photo_compression'] === 'High')>High</option></select></label>
                    <label class="wide">Default Language<select name="default_language"><option @selected($settings['default_language'] === 'English')>English</option><option @selected($settings['default_language'] === 'Hindi')>Hindi</option></select></label>
                </div>
            </section>
            <section class="settings-card">
                <div class="settings-title"><span><svg class="icon"><use href="#pin"/></svg></span><div><h2>Location Settings</h2><p>Configure location and tracking settings.</p></div></div>
                <label>Location Accuracy (meters)<input name="location_accuracy_meters" type="number" min="1" max="500" value="{{ old('location_accuracy_meters', $settings['location_accuracy_meters']) }}"></label>
                @foreach([
                    'require_location_submissions' => 'Require Location for Submissions',
                    'allow_manual_location' => 'Allow Manual Location',
                    'track_location_during_session' => 'Track User Location During Session',
                ] as $name => $label)
                    <div class="switch-row"><span>{{ $label }}</span><label class="switch"><input name="{{ $name }}" value="1" type="checkbox" @checked(old($name, $settings[$name]))><i></i></label></div>
                @endforeach
            </section>
        </div>

        <div class="settings-column">
            <section class="settings-card">
                <div class="settings-title"><span><svg class="icon"><use href="#image"/></svg></span><div><h2>System Summary</h2><p>Current data available in the admin system.</p></div></div>
                <div class="settings-summary">
                    @foreach($systemStats as $label => $value)
                        <div><strong>{{ $value }}</strong><span>{{ str($label)->headline() }}</span></div>
                    @endforeach
                </div>
            </section>
            <section class="settings-card">
                <div class="settings-title"><span><svg class="icon"><use href="#bell"/></svg></span><div><h2>Notification Settings</h2><p>Configure email and in-app notifications.</p></div></div>
                @foreach([
                    'new_submission_alert' => 'New Submission Alert',
                    'assignment_update_alert' => 'Assignment Update Alert',
                    'session_alert' => 'Session Start/End Alert',
                    'report_generation_alert' => 'Report Generation Alert',
                    'maintenance_alert' => 'System Maintenance Alert',
                ] as $name => $label)
                    <div class="switch-row"><span>{{ $label }}</span><label class="switch"><input name="{{ $name }}" value="1" type="checkbox" @checked(old($name, $settings[$name]))><i></i></label></div>
                @endforeach
                <button class="test-button" type="button" id="test-notification">Test Notification</button>
            </section>
        </div>
    </div>
</form>

<div class="settings-bottom">
    <section class="settings-card"><div class="settings-title"><span><svg class="icon"><use href="#file"/></svg></span><div><h2>Data Management</h2><p>Manage your system data.</p></div></div><div class="data-actions"><a href="{{ route('web.settings.export') }}">Export Settings</a><button type="button" id="backup-database">Backup Database</button><form method="POST" action="{{ route('web.settings.clear-cache') }}">@csrf<button type="submit">Clear Cache</button></form></div></section>
    <section class="settings-card danger"><div class="settings-title"><span>!</span><div><h2>Danger Zone</h2><p>Critical actions for system administration.</p></div></div><div class="danger-box"><button type="button" disabled>Reset System Disabled</button><p>Destructive reset actions are disabled for the current MVP admin panel.</p></div></section>
</div>
<div id="settings-toast" class="company-toast" hidden></div>
@endsection

@push('scripts')
<script src="{{ asset('js/settings.js') }}"></script>
@endpush
