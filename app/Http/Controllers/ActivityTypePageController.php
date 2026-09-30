<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\Project;
use App\Models\ProjectActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'projects' => Project::query()
                ->whereIn('status', ['active', 'draft'])
                ->orderBy('name')
                ->get(['id', 'name', 'start_date', 'end_date']),
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
            'create_project_work' => ['nullable', 'boolean'],
            'project_id' => ['nullable', 'required_if:create_project_work,1', 'exists:projects,id'],
            'project_work_name' => ['nullable', 'required_if:create_project_work,1', 'string', 'max:150'],
            'target_quantity' => ['nullable', 'integer', 'min:1'],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        if (! empty($validated['create_project_work'])) {
            $project = Project::findOrFail($validated['project_id']);

            if (! empty($validated['start_date']) && $project->start_date && $validated['start_date'] < $project->start_date->toDateString()) {
                return back()->withErrors(['start_date' => 'Work start date cannot be before project start date.'])->withInput();
            }

            if (! empty($validated['end_date']) && $project->end_date && $validated['end_date'] > $project->end_date->toDateString()) {
                return back()->withErrors(['end_date' => 'Work end date cannot be after project end date.'])->withInput();
            }
        }

        $typeData = [
            'name' => $validated['name'],
            'activity_mode' => $validated['activity_mode'],
            'tracking_required' => $validated['activity_mode'] === 'continuous_tracking'
                ? true
                : (bool) ($validated['tracking_required'] ?? false),
            'status' => $validated['status'] ?? 'active',
        ];

        DB::transaction(function () use ($typeData, $validated): void {
            $activityType = ActivityType::create($typeData);

            if (empty($validated['create_project_work'])) {
                return;
            }

            ProjectActivity::create([
                'project_id' => $validated['project_id'],
                'activity_type_id' => $activityType->id,
                'name' => $validated['project_work_name'],
                'target_quantity' => $validated['target_quantity'] ?? null,
                'expected_duration_minutes' => $validated['expected_duration_minutes'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => 'active',
            ]);
        });

        return redirect()
            ->route('web.activity-types.index')
            ->with('status', empty($validated['create_project_work']) ? 'Work type created successfully.' : 'Work type and project work created successfully.');
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
