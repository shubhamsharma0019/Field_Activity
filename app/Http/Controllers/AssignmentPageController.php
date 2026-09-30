<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssignmentPageController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();

        $assignments = ProjectAssignment::query()
            ->with([
                'project.company',
                'projectActivity.activityType',
                'worker',
            ])
            ->latest('assigned_date')
            ->latest('id')
            ->get();

        $stats = [
            'total' => $assignments->count(),
            'total_month' => $assignments->where('created_at', '>=', $monthStart)->count(),
            'active' => $assignments->whereIn('status', ['assigned', 'in_progress', 'pending_approval'])->count(),
            'active_month' => $assignments->where('created_at', '>=', $monthStart)->whereIn('status', ['assigned', 'in_progress', 'pending_approval'])->count(),
            'pending_review' => $assignments->where('status', 'pending_approval')->count(),
            'pending_review_month' => $assignments->where('created_at', '>=', $monthStart)->where('status', 'pending_approval')->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'completed_month' => $assignments->where('created_at', '>=', $monthStart)->where('status', 'completed')->count(),
        ];

        $rows = $assignments->map(fn (ProjectAssignment $assignment): array => $this->assignmentRow($assignment))->values();

        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $projects = Project::query()
            ->with('company:id,name')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'project_type']);

        $activities = ProjectActivity::query()
            ->with(['activityType:id,name,activity_mode,tracking_required', 'project:id,name,company_id'])
            ->whereIn('status', ['pending', 'active'])
            ->orderBy('name')
            ->get();

        $activityTypes = ActivityType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'activity_mode']);

        $workers = User::query()
            ->where('role', 'worker')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'mobile', 'email', 'company_id']);

        return view('assignments.index', [
            'stats' => $stats,
            'assignments' => $rows,
            'companies' => $companies,
            'projects' => $projects,
            'activities' => $activities,
            'activityTypes' => $activityTypes,
            'workers' => $workers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'project_activity_id' => ['required', 'exists:project_activities,id'],
            'worker_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'worker')),
            ],
            'target_quantity' => ['nullable', 'integer', 'min:1'],
            'tracking_required' => ['nullable', 'boolean'],
            'assigned_date' => ['required', 'date'],
        ]);

        $activity = ProjectActivity::with('activityType')->findOrFail($validated['project_activity_id']);
        $project = Project::findOrFail($validated['project_id']);
        $worker = User::findOrFail($validated['worker_id']);

        if ((int) $activity->project_id !== (int) $validated['project_id']) {
            return back()
                ->withErrors(['project_activity_id' => 'Selected activity does not belong to the selected project.'])
                ->withInput();
        }

        if (
            $project->project_type === 'company'
            && $project->company_id !== null
            && (int) $worker->company_id !== (int) $project->company_id
        ) {
            return back()
                ->withErrors(['worker_id' => 'Selected worker does not belong to this project company.'])
                ->withInput();
        }

        $duplicate = ProjectAssignment::query()
            ->where('project_activity_id', $activity->id)
            ->where('worker_id', $validated['worker_id'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['worker_id' => 'This worker already has an active assignment for the selected activity.'])
                ->withInput();
        }

        ProjectAssignment::create([
            'project_id' => $activity->project_id,
            'project_activity_id' => $activity->id,
            'worker_id' => $validated['worker_id'],
            'assigned_by' => $request->user()->id,
            'target_quantity' => $validated['target_quantity'] ?? $activity->target_quantity,
            'completed_quantity' => 0,
            'tracking_required' => $activity->activityType?->tracking_required || (bool) ($validated['tracking_required'] ?? false),
            'assigned_date' => $validated['assigned_date'],
            'status' => 'assigned',
        ]);

        return redirect()
            ->route('web.assignments.index')
            ->with('status', 'Assignment successfully created and assigned to worker.');
    }

    private function assignmentRow(ProjectAssignment $assignment): array
    {
        $activityType = $assignment->projectActivity?->activityType;
        $status = $assignment->status;
        $target = (int) ($assignment->target_quantity ?? 0);
        $completed = (int) ($assignment->completed_quantity ?? 0);

        return [
            'id' => $assignment->id,
            'code' => 'ASG' . str_pad((string) $assignment->id, 3, '0', STR_PAD_LEFT),
            'title' => $assignment->projectActivity?->name ?? 'Assignment #' . $assignment->id,
            'project' => $assignment->project?->name ?? 'Internal Project',
            'project_id' => $assignment->project_id,
            'company' => $assignment->project?->company?->name ?? 'Internal',
            'company_id' => $assignment->project?->company_id,
            'worker' => $assignment->worker?->name ?? 'Unassigned Worker',
            'worker_code' => $assignment->worker ? 'USR' . str_pad((string) $assignment->worker->id, 3, '0', STR_PAD_LEFT) : 'USR---',
            'activity_type' => $activityType?->name ?? 'Activity',
            'activity_type_id' => $activityType?->id,
            'mode' => $this->modeLabel((string) ($activityType?->activity_mode ?? 'single_submission')),
            'due_date' => $assignment->projectActivity?->end_date?->format('d M Y') ?? $assignment->assigned_date?->format('d M Y'),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'progress' => $target > 0 ? min(100, (int) round($completed / $target * 100)) : 0,
            'target' => $target,
            'completed' => $completed,
            'search' => Str::lower(implode(' ', [
                $assignment->projectActivity?->name,
                $assignment->project?->name,
                $assignment->worker?->name,
                $activityType?->name,
                $this->statusLabel($status),
            ])),
        ];
    }

    private function modeLabel(string $mode): string
    {
        return match ($mode) {
            'start_end' => 'Start-End',
            'continuous_tracking' => 'Continuous',
            default => 'Single',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'in_progress' => 'In Progress',
            'pending_approval' => 'Pending Review',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Assigned',
        };
    }
}
