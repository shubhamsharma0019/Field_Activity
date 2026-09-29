<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityTypePageController extends Controller
{
    public function index(): View
    {
        $types = ActivityType::query()
            ->withCount('projectActivities')
            ->orderByDesc('created_at')
            ->get();

        $mostUsed = $types
            ->sortByDesc('project_activities_count')
            ->first();

        $stats = [
            'total' => $types->count(),
            'active' => $types->where('status', 'active')->count(),
            'inactive' => $types->where('status', 'inactive')->count(),
            'most_used_name' => $mostUsed?->name ?? 'No Activity',
            'most_used_count' => $mostUsed?->project_activities_count ?? 0,
        ];

        $activities = $types->map(function (ActivityType $type): array {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $this->descriptionFor($type),
                'activity_mode' => $type->activity_mode,
                'mode_label' => $this->modeLabel($type->activity_mode),
                'tracking_required' => (bool) $type->tracking_required,
                'status' => $type->status,
                'status_label' => Str::title($type->status),
                'assignments_count' => $type->project_activities_count,
                'icon' => $this->iconFor($type->activity_mode),
            ];
        })->values();

        return view('activity_types.index', [
            'stats' => $stats,
            'activities' => $activities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:activity_types,name'],
            'activity_mode' => [
                'required',
                Rule::in(['single_submission', 'start_end', 'continuous_tracking']),
            ],
            'tracking_required' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        if ($validated['activity_mode'] === 'continuous_tracking') {
            $validated['tracking_required'] = true;
        }

        $validated['tracking_required'] = (bool) ($validated['tracking_required'] ?? false);
        $validated['status'] = $validated['status'] ?? 'active';

        ActivityType::create($validated);

        return redirect()
            ->route('web.activity-types.index')
            ->with('status', 'Activity type created successfully.');
    }

    private function modeLabel(string $mode): string
    {
        return match ($mode) {
            'start_end' => 'Start - End',
            'continuous_tracking' => 'Continuous Tracking',
            default => 'Single Submission',
        };
    }

    private function iconFor(string $mode): string
    {
        return match ($mode) {
            'start_end' => 'pin',
            'continuous_tracking' => 'pulse',
            default => 'file',
        };
    }

    private function descriptionFor(ActivityType $type): string
    {
        return match ($type->activity_mode) {
            'start_end' => 'Worker starts activity and ends it with time tracking.',
            'continuous_tracking' => 'Real-time location tracking during assigned field work.',
            default => 'Worker submits one-time evidence with photo, location and timestamp.',
        };
    }
}
