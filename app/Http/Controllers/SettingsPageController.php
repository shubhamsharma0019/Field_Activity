<?php

namespace App\Http\Controllers;

use App\Models\ActivitySubmission;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsPageController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'settings' => $this->settings(),
            'canPersistSettings' => Schema::hasTable('system_settings'),
            'systemStats' => [
                'companies' => Company::count(),
                'projects' => Project::count(),
                'users' => User::count(),
                'assignments' => ProjectAssignment::count(),
                'submissions' => ActivitySubmission::count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:150'],
            'organization_type' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'timezone' => ['required', 'string', 'max:80'],
            'date_format' => ['required', 'string', 'max:30'],
            'time_format' => ['required', 'string', 'max:30'],
            'map_provider' => ['required', 'string', 'max:50'],
            'default_view' => ['required', 'string', 'max:50'],
            'session_auto_end_hours' => ['required', 'integer', 'min:1', 'max:72'],
            'photo_compression' => ['required', 'string', 'max:50'],
            'default_language' => ['required', 'string', 'max:50'],
            'location_accuracy_meters' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $booleanKeys = [
            'require_location_submissions',
            'allow_manual_location',
            'track_location_during_session',
            'new_submission_alert',
            'assignment_update_alert',
            'session_alert',
            'report_generation_alert',
            'maintenance_alert',
        ];

        foreach ($booleanKeys as $key) {
            $validated[$key] = $request->boolean($key);
        }

        if (!Schema::hasTable('system_settings')) {
            return back()
                ->withInput()
                ->with('status', 'Settings table is not migrated yet. Run php artisan migrate once, then save again.');
        }

        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'),
                ]
            );
        }

        return redirect()
            ->route('web.settings.index')
            ->with('status', 'Settings saved successfully.');
    }

    public function export(): StreamedResponse
    {
        $settings = $this->settings();

        return response()->streamDownload(function () use ($settings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Setting', 'Value']);
            foreach ($settings as $key => $value) {
                fputcsv($handle, [Str::headline($key), is_bool($value) ? ($value ? 'Yes' : 'No') : $value]);
            }
            fclose($handle);
        }, 'system-settings.csv');
    }

    public function clearCache(): RedirectResponse
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        return redirect()
            ->route('web.settings.index')
            ->with('status', 'Application cache cleared successfully.');
    }

    private function settings(): array
    {
        $settings = $this->defaults();

        if (!Schema::hasTable('system_settings')) {
            return $settings;
        }

        SystemSetting::query()->get()->each(function (SystemSetting $setting) use (&$settings) {
            $settings[$setting->key] = match ($setting->type) {
                'boolean' => (bool) $setting->value,
                'integer' => (int) $setting->value,
                default => $setting->value,
            };
        });

        return $settings;
    }

    private function defaults(): array
    {
        return [
            'organization_name' => config('app.name', 'Field Activity Management System'),
            'organization_type' => 'Company',
            'email' => auth()->user()?->email ?? 'admin@fieldactivity.com',
            'phone' => auth()->user()?->mobile ?? '',
            'address' => '123 Business Park, New Delhi, India',
            'city' => 'New Delhi',
            'country' => 'India',
            'timezone' => config('app.timezone', 'Asia/Kolkata'),
            'date_format' => 'DD MMM YYYY',
            'time_format' => '12 Hour (AM/PM)',
            'map_provider' => 'Google Maps',
            'default_view' => 'Map View',
            'session_auto_end_hours' => 8,
            'photo_compression' => 'Medium',
            'default_language' => 'English',
            'location_accuracy_meters' => 10,
            'require_location_submissions' => true,
            'allow_manual_location' => false,
            'track_location_during_session' => true,
            'new_submission_alert' => true,
            'assignment_update_alert' => true,
            'session_alert' => true,
            'report_generation_alert' => true,
            'maintenance_alert' => false,
        ];
    }
}
