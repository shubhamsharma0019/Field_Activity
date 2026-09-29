<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
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

        $stats = [
            'total' => $updates->count(),
            'week' => $updates->where('created_at', '>=', $weekStart)->count(),
            'active_workers' => $updates->pluck('worker_id')->unique()->count(),
            'active_workers_week' => $updates->where('created_at', '>=', $weekStart)->pluck('worker_id')->unique()->count(),
            'with_photos' => $updates->filter(fn (ActivityUpdate $update) => filled($update->image_path))->count(),
            'with_photos_week' => $updates->where('created_at', '>=', $weekStart)->filter(fn (ActivityUpdate $update) => filled($update->image_path))->count(),
            'with_remarks' => $updates->filter(fn (ActivityUpdate $update) => filled($update->remark))->count(),
            'with_remarks_week' => $updates->where('created_at', '>=', $weekStart)->filter(fn (ActivityUpdate $update) => filled($update->remark))->count(),
        ];

        return view('activity_updates.index', [
            'stats' => $stats,
            'updates' => $updates->map(fn (ActivityUpdate $update): array => $this->row($update))->values(),
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
            'accuracy' => $update->location_accuracy,
            'date' => $time?->toDateString(),
            'time' => $time?->format('d M Y h:i A') ?? 'N/A',
            'status' => $status,
            'status_label' => $this->statusLabel($status),
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

    private function location(ActivityUpdate $update): string
    {
        $project = $update->session?->assignment?->project;
        $parts = array_filter([$project?->area, $project?->city, $project?->state]);

        return $parts ? implode(', ', $parts) : $update->latitude . ', ' . $update->longitude;
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
}
