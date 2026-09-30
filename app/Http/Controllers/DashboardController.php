<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\ActivitySession;
use App\Models\ActivitySubmission;
use App\Models\ActivityUpdate;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();

        return view('dashboard.index', [
            'stats' => $this->stats($monthStart),
            'chartData' => $this->chartData(30),
            'statusSummary' => $this->statusSummary(),
            'recentAssignments' => $this->recentAssignments(),
            'recentSubmissions' => $this->recentSubmissions(),
            'latestUpdates' => $this->latestUpdates(),
            'submissionSummary' => $this->submissionSummary(),
            'projectProgress' => $this->projectProgress(),
            'shortcuts' => $this->shortcuts(),
            'greeting' => $this->greeting($today),
            'currentDate' => $today->format('l, d F Y'),
            'currentTime' => $today->format('h:i A'),
            'notificationCount' => $this->notificationCount(),
        ]);
    }

    private function stats(Carbon $monthStart): array
    {
        return [
            [
                'label' => 'Total Companies',
                'count' => Company::count(),
                'increase' => Company::where('created_at', '>=', $monthStart)->count(),
                'meta' => Company::where('status', 'active')->count() . ' active',
                'icon' => 'building',
                'color' => 'blue',
            ],
            [
                'label' => 'Total Projects',
                'count' => Project::count(),
                'increase' => Project::where('created_at', '>=', $monthStart)->count(),
                'meta' => Project::where('status', 'active')->count() . ' active',
                'icon' => 'folder',
                'color' => 'green',
            ],
            [
                'label' => 'Total Workers',
                'count' => User::where('role', 'worker')->count(),
                'increase' => User::where('role', 'worker')
                    ->where('created_at', '>=', $monthStart)
                    ->count(),
                'meta' => User::where('role', 'worker')->where('status', 'active')->count() . ' active',
                'icon' => 'users',
                'color' => 'orange',
            ],
            [
                'label' => 'Total Assignments',
                'count' => ProjectAssignment::count(),
                'increase' => ProjectAssignment::where('created_at', '>=', $monthStart)->count(),
                'meta' => ProjectAssignment::whereIn('status', ['assigned', 'in_progress', 'pending_approval'])->count() . ' active',
                'icon' => 'clipboard',
                'color' => 'purple',
            ],
            [
                'label' => 'Pending Submissions',
                'count' => ActivitySubmission::where('approval_status', 'pending')->count(),
                'increase' => ActivitySubmission::where('approval_status', 'pending')
                    ->where('created_at', '>=', $monthStart)
                    ->count(),
                'meta' => 'awaiting review',
                'icon' => 'image',
                'color' => 'orange',
            ],
            [
                'label' => 'Approved Evidence',
                'count' => ActivitySubmission::where('approval_status', 'approved')->count(),
                'increase' => ActivitySubmission::where('approval_status', 'approved')
                    ->where('created_at', '>=', $monthStart)
                    ->count(),
                'meta' => ActivitySubmission::where('approval_status', 'rejected')->count() . ' rejected',
                'icon' => 'check',
                'color' => 'green',
            ],
            [
                'label' => 'Open Reports',
                'count' => ActivityReport::whereIn('status', ['open', 'in_review'])->count(),
                'increase' => ActivityReport::whereIn('status', ['open', 'in_review'])
                    ->where('created_at', '>=', $monthStart)
                    ->count(),
                'meta' => 'field issues',
                'icon' => 'file',
                'color' => 'blue',
            ],
        ];
    }

    private function chartData(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $rows = ProjectAssignment::query()
            ->select(
                DB::raw('DATE(updated_at) as activity_date'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status IN ('assigned', 'in_progress') THEN 1 ELSE 0 END) as in_progress"),
                DB::raw("SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending")
            )
            ->whereBetween('updated_at', [$start, $end])
            ->groupBy('activity_date')
            ->get()
            ->keyBy('activity_date');

        $records = [];

        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            $key = $date->toDateString();
            $row = $rows->get($key);

            $records[] = [
                'date' => $key,
                'label' => $date->format('d M'),
                'completed' => (int) ($row->completed ?? 0),
                'in_progress' => (int) ($row->in_progress ?? 0),
                'pending' => (int) ($row->pending ?? 0),
            ];
        }

        return $records;
    }

    private function statusSummary(): array
    {
        $counts = ProjectAssignment::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'completed' => (int) ($counts['completed'] ?? 0),
            'in_progress' => (int) ($counts['assigned'] ?? 0) + (int) ($counts['in_progress'] ?? 0),
            'pending' => (int) ($counts['pending_approval'] ?? 0),
        ];
    }

    private function recentAssignments(): array
    {
        return ProjectAssignment::with([
            'worker',
            'project',
            'projectActivity',
        ])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (ProjectAssignment $assignment): array {
                return [
                    'id' => $assignment->id,
                    'worker' => $assignment->worker?->name ?? 'Unassigned Worker',
                    'initials' => $this->initials($assignment->worker?->name ?? 'Worker'),
                    'project' => $assignment->project?->name ?? 'No Project',
                    'activity' => $assignment->projectActivity?->name ?? 'No Activity',
                    'status' => $this->statusLabel($assignment->status),
                    'color' => $this->statusColor($assignment->status),
                    'date' => optional($assignment->assigned_date)->format('d M Y')
                        ?? $assignment->created_at?->format('d M Y')
                        ?? '-',
                    'description' => trim(
                        ($assignment->project?->name ?? 'No Project') . ' - '
                        . ($assignment->projectActivity?->name ?? 'No Activity') . ' - '
                        . $this->statusLabel($assignment->status)
                    ),
                ];
            })
            ->toArray();
    }

    private function recentSubmissions(): array
    {
        return ActivitySubmission::with([
            'worker',
            'assignment.project',
            'assignment.projectActivity',
        ])
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (ActivitySubmission $submission): array {
                $project = $submission->assignment?->project;
                $location = filled($submission->latitude) && filled($submission->longitude)
                    ? $submission->latitude . ', ' . $submission->longitude
                    : collect([$project?->area, $project?->city, $project?->state])->filter()->join(', ');

                return [
                    'id' => $submission->id,
                    'activity' => $submission->assignment?->projectActivity?->name ?? 'Evidence Submission',
                    'project' => $project?->name ?? 'No Project',
                    'worker' => $submission->worker?->name ?? 'Worker',
                    'submitted_at' => $submission->server_timestamp?->format('d M Y, h:i A')
                        ?? $submission->created_at?->format('d M Y, h:i A')
                        ?? '-',
                    'status' => $this->statusLabel($submission->approval_status),
                    'color' => $this->statusColor($submission->approval_status),
                    'location' => $location ?: 'Location not available',
                    'description' => 'Submission #' . $submission->submission_no
                        . ' - ' . $this->statusLabel($submission->approval_status)
                        . ' - ' . ($submission->remark ?: 'No remark'),
                ];
            })
            ->toArray();
    }

    private function submissionSummary(): array
    {
        return [
            'pending' => ActivitySubmission::where('approval_status', 'pending')->count(),
            'approved' => ActivitySubmission::where('approval_status', 'approved')->count(),
            'rejected' => ActivitySubmission::where('approval_status', 'rejected')->count(),
        ];
    }

    private function projectProgress(): array
    {
        return Project::query()
            ->with('company')
            ->withSum('assignments as target_quantity', 'target_quantity')
            ->withSum('assignments as completed_quantity', 'completed_quantity')
            ->withCount('assignments')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Project $project): array {
                $target = (int) ($project->target_quantity ?? 0);
                $completed = (int) ($project->completed_quantity ?? 0);
                $remaining = max($target - $completed, 0);
                $percentage = $target > 0 ? round(min(100, ($completed / $target) * 100), 2) : 0;

                return [
                    'name' => $project->name,
                    'company' => $project->company?->name ?? 'Internal Project',
                    'assignments' => (int) $project->assignments_count,
                    'target' => $target,
                    'approved' => $completed,
                    'remaining' => $remaining,
                    'percentage' => $percentage,
                    'status' => $this->statusLabel($project->status),
                    'color' => $this->statusColor($project->status),
                ];
            })
            ->toArray();
    }

    private function latestUpdates(): array
    {
        $items = collect();

        ActivitySubmission::with('worker')
            ->latest()
            ->limit(5)
            ->get()
            ->each(fn (ActivitySubmission $submission) => $items->push([
                'title' => 'Photo submitted',
                'name' => $submission->worker?->name ?? 'Worker',
                'time' => $submission->created_at?->diffForHumans() ?? '-',
                'icon' => 'image',
                'color' => $this->statusColor($submission->approval_status),
                'created_at' => $submission->created_at,
                'description' => 'Submission #' . $submission->submission_no . ' is ' . $submission->approval_status . '.',
            ]));

        ActivityUpdate::with('worker')
            ->latest()
            ->limit(5)
            ->get()
            ->each(fn (ActivityUpdate $update) => $items->push([
                'title' => 'Activity update received',
                'name' => $update->worker?->name ?? 'Worker',
                'time' => $update->created_at?->diffForHumans() ?? '-',
                'icon' => 'pulse',
                'color' => 'blue',
                'created_at' => $update->created_at,
                'description' => $update->remark ?: 'Field activity update received.',
            ]));

        ActivitySession::with('worker')
            ->latest()
            ->limit(5)
            ->get()
            ->each(fn (ActivitySession $session) => $items->push([
                'title' => 'Activity session ' . $this->statusLabel($session->status),
                'name' => $session->worker?->name ?? 'Worker',
                'time' => $session->created_at?->diffForHumans() ?? '-',
                'icon' => 'clock',
                'color' => $this->statusColor($session->status),
                'created_at' => $session->created_at,
                'description' => 'Session status: ' . $this->statusLabel($session->status),
            ]));

        ActivityReport::with('worker')
            ->latest()
            ->limit(5)
            ->get()
            ->each(fn (ActivityReport $report) => $items->push([
                'title' => $report->title ?: 'Activity report submitted',
                'name' => $report->worker?->name ?? 'Worker',
                'time' => $report->created_at?->diffForHumans() ?? '-',
                'icon' => 'file',
                'color' => $this->statusColor($report->status),
                'created_at' => $report->created_at,
                'description' => $report->description ?: 'Report status: ' . $this->statusLabel($report->status),
            ]));

        return $items
            ->sortByDesc('created_at')
            ->take(8)
            ->values()
            ->toArray();
    }

    private function shortcuts(): array
    {
        return [
            [
                'title' => 'Manage Companies',
                'description' => Company::count() . ' companies registered',
                'icon' => 'building',
                'color' => 'blue',
                'route' => 'web.companies.index',
            ],
            [
                'title' => 'Create Project',
                'description' => Project::where('status', 'active')->count() . ' active projects',
                'icon' => 'folder',
                'color' => 'green',
                'route' => 'web.projects.create',
            ],
            [
                'title' => 'Add User / Worker',
                'description' => User::where('role', 'worker')->where('status', 'active')->count() . ' active workers',
                'icon' => 'users',
                'color' => 'orange',
                'route' => 'web.users.create',
            ],
            [
                'title' => 'View Reports',
                'description' => ActivityReport::where('status', 'open')->count() . ' open reports',
                'icon' => 'chart',
                'color' => 'purple',
                'route' => 'web.reports.index',
            ],
        ];
    }

    private function notificationCount(): int
    {
        return ActivitySubmission::where('approval_status', 'pending')->count()
            + ActivitySession::where('status', 'pending_approval')->count()
            + ActivityReport::where('status', 'open')->count();
    }

    private function greeting(Carbon $time): string
    {
        return match (true) {
            $time->hour < 12 => 'Good Morning',
            $time->hour < 17 => 'Good Afternoon',
            default => 'Good Evening',
        };
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('') ?: 'NA';
    }

    private function statusLabel(?string $status): string
    {
        return str((string) $status)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function statusColor(?string $status): string
    {
        return match ($status) {
            'completed', 'approved', 'resolved' => 'green',
            'in_progress', 'active' => 'blue',
            'assigned', 'pending', 'pending_approval', 'open' => 'orange',
            default => 'purple',
        };
    }
}
