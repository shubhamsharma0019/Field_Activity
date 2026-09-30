<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\ActivitySession;
use App\Models\ActivitySubmission;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportPageController extends Controller
{
    public function index(): View
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $assignments = ProjectAssignment::query()
            ->with(['project.company', 'projectActivity.activityType', 'worker'])
            ->get();

        $submissions = ActivitySubmission::query()
            ->with(['assignment.project', 'assignment.projectActivity.activityType', 'worker'])
            ->latest('server_timestamp')
            ->get();

        $sessions = ActivitySession::query()
            ->with(['assignment.project', 'assignment.projectActivity.activityType', 'worker'])
            ->get();

        $reports = ActivityReport::query()
            ->with(['assignment.project', 'assignment.projectActivity.activityType', 'worker'])
            ->latest()
            ->get();

        $workers = User::query()
            ->where('role', 'worker')
            ->orderBy('name')
            ->get();

        $reportRows = $assignments->map(fn (ProjectAssignment $assignment): array => $this->assignmentRow($assignment))
            ->merge($submissions->map(fn (ActivitySubmission $submission): array => $this->submissionRow($submission)))
            ->merge($sessions->map(fn (ActivitySession $session): array => $this->sessionRow($session)))
            ->merge($reports->map(fn (ActivityReport $report): array => $this->issueRow($report)))
            ->sortByDesc('timestamp')
            ->values();

        return view('reports.index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'company_id']),
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'workers' => $workers->map(fn (User $worker): array => [
                'id' => $worker->id,
                'name' => $worker->name,
                'code' => 'USR'.str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT),
            ]),
            'reportRows' => $reportRows,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentRow(ProjectAssignment $assignment): array
    {
        $project = $assignment->project;
        $worker = $assignment->worker;
        $status = $assignment->status;

        return [
            'id' => 'A'.$assignment->id,
            'type' => 'assignment',
            'title' => $assignment->projectActivity?->name ?? 'Assignment',
            'activity_type' => $assignment->projectActivity?->activityType?->name ?? $assignment->projectActivity?->name ?? 'Activity',
            'worker_id' => $worker?->id,
            'worker' => $worker?->name ?? 'Unassigned',
            'worker_code' => $worker ? 'USR'.str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : '-',
            'company_id' => $project?->company_id,
            'company' => $project?->company?->name ?? 'No Company',
            'project_id' => $project?->id,
            'project' => $project?->name ?? 'No Project',
            'location' => $this->projectLocation($project),
            'date' => ($assignment->assigned_date ?? $assignment->created_at)?->toDateString(),
            'timestamp' => ($assignment->completed_at ?? $assignment->started_at ?? $assignment->updated_at ?? $assignment->created_at)?->toDateTimeString(),
            'status' => $status,
            'status_group' => $this->statusGroup($status),
            'status_label' => $this->statusLabel($status),
            'target' => (int) $assignment->target_quantity,
            'completed' => (int) $assignment->completed_quantity,
            'has_photo' => false,
            'has_remark' => false,
            'description' => 'Target '.$assignment->completed_quantity.'/'.$assignment->target_quantity,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionRow(ActivitySubmission $submission): array
    {
        $assignment = $submission->assignment;
        $project = $assignment?->project;
        $worker = $submission->worker;
        $status = $submission->approval_status;

        return [
            'id' => 'S'.$submission->id,
            'type' => 'submission',
            'title' => $assignment?->projectActivity?->name ?? 'Photo Submission',
            'activity_type' => $assignment?->projectActivity?->activityType?->name ?? $assignment?->projectActivity?->name ?? 'Activity',
            'worker_id' => $worker?->id,
            'worker' => $worker?->name ?? 'Worker',
            'worker_code' => $worker ? 'USR'.str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : '-',
            'company_id' => $project?->company_id,
            'company' => $project?->company?->name ?? 'No Company',
            'project_id' => $project?->id,
            'project' => $project?->name ?? 'No Project',
            'location' => $this->recordLocation($submission->latitude, $submission->longitude, $project),
            'date' => ($submission->device_timestamp ?? $submission->server_timestamp ?? $submission->created_at)?->toDateString(),
            'timestamp' => ($submission->device_timestamp ?? $submission->server_timestamp ?? $submission->created_at)?->toDateTimeString(),
            'status' => $status,
            'status_group' => $this->statusGroup($status),
            'status_label' => $this->statusLabel($status),
            'target' => 0,
            'completed' => $status === 'approved' ? 1 : 0,
            'has_photo' => filled($submission->image_path),
            'has_remark' => filled($submission->remark),
            'description' => $submission->remark ?: 'Worker submitted field evidence.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionRow(ActivitySession $session): array
    {
        $assignment = $session->assignment;
        $project = $assignment?->project;
        $worker = $session->worker;
        $status = $session->status;

        return [
            'id' => 'SE'.$session->id,
            'type' => 'session',
            'title' => $assignment?->projectActivity?->name ?? 'Activity Session',
            'activity_type' => $assignment?->projectActivity?->activityType?->name ?? $assignment?->projectActivity?->name ?? 'Activity',
            'worker_id' => $worker?->id,
            'worker' => $worker?->name ?? 'Worker',
            'worker_code' => $worker ? 'USR'.str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : '-',
            'company_id' => $project?->company_id,
            'company' => $project?->company?->name ?? 'No Company',
            'project_id' => $project?->id,
            'project' => $project?->name ?? 'No Project',
            'location' => $this->recordLocation($session->end_latitude ?? $session->start_latitude, $session->end_longitude ?? $session->start_longitude, $project),
            'date' => ($session->completed_at ?? $session->started_at ?? $session->created_at)?->toDateString(),
            'timestamp' => ($session->completed_at ?? $session->started_at ?? $session->created_at)?->toDateTimeString(),
            'status' => $status,
            'status_group' => $this->statusGroup($status),
            'status_label' => $this->statusLabel($status),
            'target' => (int) $session->expected_duration_minutes,
            'completed' => $status === 'completed' ? 1 : 0,
            'has_photo' => filled($session->start_image_path) || filled($session->end_image_path),
            'has_remark' => filled($session->remark),
            'description' => $session->remark ?: 'Session duration: '.($session->actual_duration_minutes ?? 0).' minutes.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function issueRow(ActivityReport $report): array
    {
        $assignment = $report->assignment;
        $project = $assignment?->project;
        $worker = $report->worker;

        return [
            'id' => 'R'.$report->id,
            'type' => 'report',
            'title' => $report->title,
            'activity_type' => Str::of($report->report_type)->replace('_', ' ')->title()->toString(),
            'worker_id' => $worker?->id,
            'worker' => $worker?->name ?? 'Worker',
            'worker_code' => $worker ? 'USR'.str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : '-',
            'company_id' => $project?->company_id,
            'company' => $project?->company?->name ?? 'No Company',
            'project_id' => $project?->id,
            'project' => $project?->name ?? 'No Project',
            'location' => $this->recordLocation($report->latitude, $report->longitude, $project),
            'date' => $report->created_at?->toDateString(),
            'timestamp' => $report->created_at?->toDateTimeString(),
            'status' => $report->status,
            'status_group' => $this->statusGroup($report->status),
            'status_label' => $this->statusLabel($report->status),
            'target' => 0,
            'completed' => $report->status === 'resolved' ? 1 : 0,
            'has_photo' => filled($report->image_path),
            'has_remark' => true,
            'description' => $report->description,
        ];
    }

    private function statusGroup(?string $status): string
    {
        return match ($status) {
            'completed', 'approved', 'resolved' => 'completed',
            'assigned', 'in_progress', 'open', 'in_review' => 'in_progress',
            'pending', 'pending_approval' => 'pending',
            'rejected' => 'rejected',
            default => 'pending',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending_approval' => 'Pending Review',
            'in_progress' => 'In Progress',
            'in_review' => 'In Review',
            default => Str::of((string) $status)->replace('_', ' ')->title()->toString(),
        };
    }

    private function recordLocation(mixed $latitude, mixed $longitude, ?Project $project): string
    {
        if (filled($latitude) && filled($longitude)) {
            return $latitude.', '.$longitude;
        }

        return $this->projectLocation($project);
    }

    private function projectLocation(?Project $project): string
    {
        return collect([$project?->area, $project?->city, $project?->state])
            ->filter()
            ->join(', ') ?: 'Location not available';
    }
}
