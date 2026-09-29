<?php

namespace App\Http\Controllers;

use App\Models\ActivitySubmission;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportPageController extends Controller
{
    public function index(): View
    {
        $assignments = ProjectAssignment::query()
            ->with(['project.company', 'projectActivity.activityType', 'worker'])
            ->get();

        $submissions = ActivitySubmission::query()
            ->with(['assignment.project', 'assignment.projectActivity.activityType', 'worker'])
            ->latest('server_timestamp')
            ->limit(8)
            ->get();

        $stats = [
            'total' => $assignments->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'in_progress' => $assignments->whereIn('status', ['assigned', 'in_progress'])->count(),
            'pending' => $assignments->where('status', 'pending_approval')->count(),
            'rejected' => $submissions->where('approval_status', 'rejected')->count(),
            'month_total' => $assignments->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $activityDistribution = $assignments
            ->groupBy(fn (ProjectAssignment $assignment) => $assignment->projectActivity?->activityType?->name ?? 'Activity')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'count' => $items->count(),
                'percent' => $stats['total'] ? round($items->count() / $stats['total'] * 100) : 0,
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $topWorkers = User::query()
            ->where('role', 'worker')
            ->withCount(['assignments as completed_assignments_count' => fn ($query) => $query->where('status', 'completed')])
            ->orderByDesc('completed_assignments_count')
            ->limit(5)
            ->get();

        $projectPerformance = Project::query()
            ->withCount('assignments')
            ->withSum('assignments as completed_quantity', 'completed_quantity')
            ->withSum('assignments as target_quantity', 'target_quantity')
            ->orderByDesc('assignments_count')
            ->limit(6)
            ->get()
            ->map(function (Project $project): array {
                $target = (int) ($project->target_quantity ?? 0);
                $completed = (int) ($project->completed_quantity ?? 0);

                return [
                    'name' => $project->name,
                    'count' => (int) $project->assignments_count,
                    'percent' => $target > 0 ? min(100, (int) round($completed / $target * 100)) : 0,
                ];
            });

        $locationCounts = $assignments
            ->groupBy(fn (ProjectAssignment $assignment) => $assignment->project?->city ?: $assignment->project?->state ?: 'Unknown')
            ->map(fn ($items, $name) => ['name' => $name, 'count' => $items->count()])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $maxLocation = max(1, (int) $locationCounts->max('count'));

        $recentSubmissions = $submissions->map(function (ActivitySubmission $submission): array {
            $project = $submission->assignment?->project;

            return [
                'activity' => $submission->assignment?->projectActivity?->name ?? 'Activity',
                'worker' => $submission->worker?->name ?? 'Worker',
                'location' => $project?->area ?: $project?->city ?: 'GPS: ' . $submission->latitude . ', ' . $submission->longitude,
                'submitted_at' => ($submission->device_timestamp ?? $submission->server_timestamp ?? $submission->created_at)?->format('d M Y, h:i A') ?? 'N/A',
                'status' => $submission->approval_status,
                'status_label' => Str::title($submission->approval_status === 'pending' ? 'Pending Review' : $submission->approval_status),
            ];
        });

        $trend = collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = now()->subDays($daysAgo)->toDateString();

            return [
                'label' => now()->subDays($daysAgo)->format('d M'),
                'completed' => ProjectAssignment::whereDate('updated_at', $date)->where('status', 'completed')->count(),
                'in_progress' => ProjectAssignment::whereDate('updated_at', $date)->whereIn('status', ['assigned', 'in_progress'])->count(),
                'pending' => ProjectAssignment::whereDate('updated_at', $date)->where('status', 'pending_approval')->count(),
                'rejected' => ActivitySubmission::whereDate('updated_at', $date)->where('approval_status', 'rejected')->count(),
            ];
        });

        return view('reports.index', [
            'stats' => $stats,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'company_id']),
            'activityDistribution' => $activityDistribution,
            'topWorkers' => $topWorkers,
            'projectPerformance' => $projectPerformance,
            'locationCounts' => $locationCounts,
            'maxLocation' => $maxLocation,
            'recentSubmissions' => $recentSubmissions,
            'trend' => $trend,
        ]);
    }
}
