<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\ActivitySession;
use App\Models\ActivitySubmission;
use App\Models\ActivityUpdate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ActivityUpdatePageController extends Controller
{
    public function index(): View
    {
        $weekStart = now()->subDays(7);

        $updates = ActivityUpdate::query()
            ->with([
                'session.assignment.project',
                'session.assignment.projectActivity.activityType',
                'worker',
            ])
            ->latest('server_timestamp')
            ->latest('id')
            ->get();

        $submissions = ActivitySubmission::query()
            ->with([
                'assignment.project',
                'assignment.projectActivity.activityType',
                'worker',
            ])
            ->latest('server_timestamp')
            ->latest('id')
            ->get();

        $sessions = ActivitySession::query()
            ->with([
                'assignment.project',
                'assignment.projectActivity.activityType',
                'worker',
            ])
            ->latest('started_at')
            ->latest('id')
            ->get();

        $rows = collect()
            ->merge($updates->map(fn (ActivityUpdate $update): array => $this->row($update)))
            ->merge($submissions->map(fn (ActivitySubmission $submission): array => $this->submissionRow($submission)))
            ->merge($sessions->flatMap(fn (ActivitySession $session): array => $this->sessionRows($session)))
            ->sortByDesc('sort_time')
            ->values();

        $stats = [
            'total' => $rows->count(),
            'week' => $rows->where('sort_time', '>=', $weekStart)->count(),
            'active_workers' => $rows->pluck('worker_id')->filter()->unique()->count(),
            'active_workers_week' => $rows->where('sort_time', '>=', $weekStart)->pluck('worker_id')->filter()->unique()->count(),
            'with_photos' => $rows->where('has_photo', true)->count(),
            'with_photos_week' => $rows->where('sort_time', '>=', $weekStart)->where('has_photo', true)->count(),
            'with_remarks' => $rows->where('has_remark', true)->count(),
            'with_remarks_week' => $rows->where('sort_time', '>=', $weekStart)->where('has_remark', true)->count(),
        ];

        return view('activity_updates.index', [
            'stats' => $stats,
            'updates' => $rows,
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'workers' => User::query()->where('role', 'worker')->orderBy('name')->get(['id', 'name']),
            'activityTypes' => ActivityType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function row(ActivityUpdate $update): array
    {
        $session = $update->session;
        $assignment = $session?->assignment;
        $project = $assignment?->project;
        $activity = $assignment?->projectActivity;
        $activityType = $activity?->activityType;
        $worker = $update->worker;
        $time = $update->device_timestamp ?? $update->server_timestamp ?? $update->created_at;
        $status = $session?->status ?? 'in_progress';

        return [
            'id' => $update->id,
            'session_id' => $session?->id,
            'session_code' => $session ? 'SES' . str_pad((string) $session->id, 4, '0', STR_PAD_LEFT) : 'SES---',
            'photo' => $update->image_url ?: asset('images/admin-construction.jpg'),
            'worker' => $worker?->name ?? 'Worker',
            'worker_id' => $worker?->id,
            'worker_code' => $worker ? 'USR' . str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : 'USR---',
            'worker_initial' => strtoupper(substr($worker?->name ?? 'W', 0, 1)),
            'project' => $project?->name ?? 'Internal Project',
            'project_id' => $project?->id,
            'activity_type' => $activityType?->name ?? 'Activity',
            'activity_type_id' => $activityType?->id,
            'remark' => $update->remark ?: 'No remark added.',
            'location' => $this->location($update),
            'gps' => $update->latitude . ', ' . $update->longitude,
            'latitude' => $update->latitude,
            'longitude' => $update->longitude,
            'accuracy' => $update->location_accuracy,
            'date' => $time?->toDateString(),
            'time' => $time?->format('d M Y h:i A') ?? 'N/A',
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'has_photo' => filled($update->image_path),
            'has_remark' => filled($update->remark),
            'sort_time' => $time,
            'search' => Str::lower(implode(' ', [
                $worker?->name,
                $project?->name,
                $activityType?->name,
                $update->remark,
                $this->location($update),
                $this->statusLabel($status),
            ])),
        ];
    }

    private function submissionRow(ActivitySubmission $submission): array
    {
        $assignment = $submission->assignment;
        $project = $assignment?->project;
        $activity = $assignment?->projectActivity;
        $activityType = $activity?->activityType;
        $worker = $submission->worker;
        $time = $submission->device_timestamp ?? $submission->server_timestamp ?? $submission->created_at;
        $remark = $submission->remark ?: 'Photo submission sent for review.';
        $status = $submission->approval_status;

        return [
            'id' => 'submission-' . $submission->id,
            'session_id' => null,
            'session_code' => 'SUB' . str_pad((string) $submission->id, 4, '0', STR_PAD_LEFT),
            'photo' => $submission->image_url ?: asset('images/admin-construction.jpg'),
            'worker' => $worker?->name ?? 'Worker',
            'worker_id' => $worker?->id,
            'worker_code' => $worker ? 'USR' . str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : 'USR---',
            'worker_initial' => strtoupper(substr($worker?->name ?? 'W', 0, 1)),
            'project' => $project?->name ?? 'Internal Project',
            'project_id' => $project?->id,
            'activity_type' => $activityType?->name ?? 'Submission',
            'activity_type_id' => $activityType?->id,
            'remark' => $remark,
            'location' => $this->submissionLocation($submission),
            'gps' => $submission->latitude . ', ' . $submission->longitude,
            'latitude' => $submission->latitude,
            'longitude' => $submission->longitude,
            'accuracy' => $submission->location_accuracy,
            'date' => $time?->toDateString(),
            'time' => $time?->format('d M Y h:i A') ?? 'N/A',
            'status' => $status,
            'status_label' => $this->submissionStatusLabel($status),
            'has_photo' => filled($submission->image_path),
            'has_remark' => filled($submission->remark),
            'sort_time' => $time,
            'search' => Str::lower(implode(' ', [
                $worker?->name,
                $project?->name,
                $activityType?->name,
                $remark,
                $this->submissionLocation($submission),
                $this->submissionStatusLabel($status),
            ])),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sessionRows(ActivitySession $session): array
    {
        $rows = [];

        if ($session->start_latitude && $session->start_longitude) {
            $rows[] = $this->sessionEvidenceRow($session, 'start');
        }

        if ($session->end_latitude && $session->end_longitude) {
            $rows[] = $this->sessionEvidenceRow($session, 'end');
        }

        return $rows;
    }

    private function sessionEvidenceRow(ActivitySession $session, string $point): array
    {
        $assignment = $session->assignment;
        $project = $assignment?->project;
        $activity = $assignment?->projectActivity;
        $activityType = $activity?->activityType;
        $worker = $session->worker;
        $isStart = $point === 'start';
        $time = $isStart
            ? ($session->start_device_timestamp ?? $session->started_at ?? $session->created_at)
            : ($session->end_device_timestamp ?? $session->completed_at ?? $session->updated_at);
        $imageUrl = $isStart ? $session->start_image_url : $session->end_image_url;
        $latitude = $isStart ? $session->start_latitude : $session->end_latitude;
        $longitude = $isStart ? $session->start_longitude : $session->end_longitude;
        $accuracy = $isStart ? $session->start_location_accuracy : $session->end_location_accuracy;
        $remark = $isStart ? 'Worker started activity with location evidence.' : ($session->remark ?: 'Worker ended activity with location evidence.');

        return [
            'id' => 'session-' . $point . '-' . $session->id,
            'session_id' => $session->id,
            'session_code' => 'SES' . str_pad((string) $session->id, 4, '0', STR_PAD_LEFT),
            'photo' => $imageUrl ?: asset('images/admin-construction.jpg'),
            'worker' => $worker?->name ?? 'Worker',
            'worker_id' => $worker?->id,
            'worker_code' => $worker ? 'USR' . str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : 'USR---',
            'worker_initial' => strtoupper(substr($worker?->name ?? 'W', 0, 1)),
            'project' => $project?->name ?? 'Internal Project',
            'project_id' => $project?->id,
            'activity_type' => $activityType?->name ?? ($isStart ? 'Session Start' : 'Session End'),
            'activity_type_id' => $activityType?->id,
            'remark' => $remark,
            'location' => $this->sessionLocation($session),
            'gps' => $latitude . ', ' . $longitude,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'date' => $time?->toDateString(),
            'time' => $time?->format('d M Y h:i A') ?? 'N/A',
            'status' => $session->status,
            'status_label' => $this->statusLabel($session->status),
            'has_photo' => filled($imageUrl),
            'has_remark' => filled($session->remark),
            'sort_time' => $time,
            'search' => Str::lower(implode(' ', [
                $worker?->name,
                $project?->name,
                $activityType?->name,
                $remark,
                $this->sessionLocation($session),
                $this->statusLabel($session->status),
            ])),
        ];
    }

    private function location(ActivityUpdate $update): string
    {
        $project = $update->session?->assignment?->project;
        $parts = array_filter([$project?->area, $project?->city, $project?->state]);

        return $parts ? implode(', ', $parts) : $update->latitude . ', ' . $update->longitude;
    }

    private function submissionLocation(ActivitySubmission $submission): string
    {
        $project = $submission->assignment?->project;
        $parts = array_filter([$project?->area, $project?->city, $project?->state]);

        return $parts ? implode(', ', $parts) : $submission->latitude . ', ' . $submission->longitude;
    }

    private function sessionLocation(ActivitySession $session): string
    {
        $project = $session->assignment?->project;
        $parts = array_filter([$project?->area, $project?->city, $project?->state]);

        return $parts ? implode(', ', $parts) : $session->start_latitude . ', ' . $session->start_longitude;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending_approval' => 'Pending',
            'approved' => 'Completed',
            'rejected' => 'Rejected',
            default => 'Active',
        };
    }

    private function submissionStatusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'Completed',
            'rejected' => 'Rejected',
            default => 'Pending',
        };
    }
}
