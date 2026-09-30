<?php

namespace App\Http\Controllers;

use App\Models\ActivitySession;
use App\Models\ActivitySubmission;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkerLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivitySessionPageController extends Controller
{
    public function liveTracking(): View
    {
        return view('activity_sessions.live', $this->liveTrackingPayload());
    }

    public function fullMap(): View
    {
        return view('activity_sessions.map', $this->liveTrackingPayload());
    }

    private function liveTrackingPayload(): array
    {
        $workers = User::query()
            ->with([
                'company:id,name',
                'assignments' => fn ($query) => $query
                    ->with([
                        'project:id,name,company_id,city,state',
                        'project.company:id,name',
                        'projectActivity:id,name,activity_type_id',
                        'projectActivity.activityType:id,name,activity_mode',
                    ])
                    ->whereIn('status', ['assigned', 'in_progress', 'pending_approval'])
                    ->latest('started_at')
                    ->latest('assigned_date'),
            ])
            ->where('role', 'worker')
            ->orderBy('name')
            ->get();

        $latestLocations = WorkerLocation::query()
            ->with([
                'assignment.project:id,name,company_id,city,state',
                'assignment.project.company:id,name',
                'assignment.projectActivity:id,name,activity_type_id',
                'assignment.projectActivity.activityType:id,name,activity_mode',
            ])
            ->whereIn('worker_id', $workers->pluck('id'))
            ->latest('recorded_at')
            ->get()
            ->unique('worker_id')
            ->keyBy('worker_id');

        $latestSessions = ActivitySession::query()
            ->with([
                'assignment.project:id,name,company_id,city,state',
                'assignment.project.company:id,name',
                'assignment.projectActivity:id,name,activity_type_id',
                'assignment.projectActivity.activityType:id,name,activity_mode',
            ])
            ->whereIn('worker_id', $workers->pluck('id'))
            ->latest('started_at')
            ->get()
            ->unique('worker_id')
            ->keyBy('worker_id');

        $latestSubmissions = ActivitySubmission::query()
            ->with([
                'assignment.project:id,name,company_id,city,state',
                'assignment.project.company:id,name',
                'assignment.projectActivity:id,name,activity_type_id',
                'assignment.projectActivity.activityType:id,name,activity_mode',
            ])
            ->whereIn('worker_id', $workers->pluck('id'))
            ->latest('server_timestamp')
            ->latest('id')
            ->get()
            ->unique('worker_id')
            ->keyBy('worker_id');

        $locationRoutes = WorkerLocation::query()
            ->whereIn('worker_id', $workers->pluck('id'))
            ->where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at')
            ->get()
            ->groupBy('worker_id');

        $workerRows = $workers
            ->map(fn (User $worker): array => $this->liveWorkerRow(
                $worker,
                $latestLocations->get($worker->id),
                $latestSessions->get($worker->id),
                $latestSubmissions->get($worker->id),
                $locationRoutes->get($worker->id, collect())
            ))
            ->values();

        $activeCount = $workerRows->where('status', 'active')->count();
        $breakCount = $workerRows->where('status', 'break')->count();
        $offlineCount = $workerRows->where('status', 'offline')->count();

        return [
            'workers' => $workerRows,
            'stats' => [
                'total' => $workerRows->count(),
                'active' => $activeCount,
                'break' => $breakCount,
                'offline' => $offlineCount,
                'active_percent' => $workerRows->count() > 0 ? round($activeCount / $workerRows->count() * 100) : 0,
            ],
            'projects' => $workerRows
                ->pluck('project')
                ->filter(fn ($project) => $project !== 'No active project')
                ->unique()
                ->values(),
        ];
    }

    public function index(): View
    {
        $monthStart = now()->startOfMonth();

        $sessions = ActivitySession::query()
            ->with([
                'assignment.project.company',
                'assignment.projectActivity.activityType',
                'worker',
                'reviewer',
            ])
            ->latest('started_at')
            ->latest('id')
            ->get();

        $stats = [
            'total' => $sessions->count(),
            'total_month' => $sessions->where('created_at', '>=', $monthStart)->count(),
            'active' => $sessions->where('status', 'in_progress')->count(),
            'active_month' => $sessions->where('created_at', '>=', $monthStart)->where('status', 'in_progress')->count(),
            'pending' => $sessions->where('status', 'pending_approval')->count(),
            'pending_month' => $sessions->where('created_at', '>=', $monthStart)->where('status', 'pending_approval')->count(),
            'completed' => $sessions->where('status', 'approved')->count(),
            'completed_month' => $sessions->where('created_at', '>=', $monthStart)->where('status', 'approved')->count(),
            'rejected' => $sessions->where('status', 'rejected')->count(),
            'rejected_month' => $sessions->where('created_at', '>=', $monthStart)->where('status', 'rejected')->count(),
        ];

        return view('activity_sessions.index', [
            'stats' => $stats,
            'sessions' => $sessions->map(fn (ActivitySession $session): array => $this->row($session))->values(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'company_id']),
        ]);
    }

    public function review(Request $request, ActivitySession $session): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $session->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['status'] === 'rejected'
                ? ($validated['rejection_reason'] ?? 'Rejected by admin.')
                : null,
        ]);

        return redirect()
            ->route('web.activity-sessions.index')
            ->with('status', 'Session ' . ($validated['status'] === 'approved' ? 'approved' : 'rejected') . ' successfully.');
    }

    private function row(ActivitySession $session): array
    {
        $assignment = $session->assignment;
        $project = $assignment?->project;
        $activity = $assignment?->projectActivity;
        $activityType = $activity?->activityType;
        $worker = $session->worker;
        $duration = $this->duration($session);
        $location = $this->location($session);

        return [
            'id' => $session->id,
            'code' => 'SES' . str_pad((string) $session->id, 4, '0', STR_PAD_LEFT),
            'worker' => $worker?->name ?? 'Worker',
            'worker_code' => $worker ? 'USR' . str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : 'USR---',
            'worker_mobile' => $worker?->mobile ?? 'N/A',
            'worker_initials' => $this->initials($worker?->name ?? 'Worker'),
            'assignment' => $activity?->name ?? 'Assignment #' . $assignment?->id,
            'project' => $project?->name ?? 'Internal Project',
            'project_id' => $project?->id,
            'company' => $project?->company?->name ?? 'Internal',
            'company_id' => $project?->company_id,
            'activity_mode' => $this->modeLabel((string) ($activityType?->activity_mode ?? 'start_end')),
            'start_time' => $session->started_at?->format('d M Y h:i A') ?? 'N/A',
            'start_date' => $session->started_at?->toDateString(),
            'end_time' => $session->completed_at?->format('d M Y h:i A') ?? '-',
            'duration' => $duration,
            'status' => $session->status,
            'status_label' => $this->statusLabel($session->status),
            'location' => $location,
            'start_gps' => $session->start_latitude . ', ' . $session->start_longitude,
            'end_gps' => $session->end_latitude && $session->end_longitude ? $session->end_latitude . ', ' . $session->end_longitude : 'Not completed',
            'start_image_url' => $session->start_image_url ?: asset('images/admin-construction.jpg'),
            'end_image_url' => $session->end_image_url ?: asset('images/admin-construction.jpg'),
            'remark' => $session->remark ?: 'No remark added.',
            'reviewer' => $session->reviewer?->name,
            'reviewed_at' => $session->reviewed_at?->format('d M Y h:i A'),
            'rejection_reason' => $session->rejection_reason,
            'search' => Str::lower(implode(' ', [
                'SES' . str_pad((string) $session->id, 4, '0', STR_PAD_LEFT),
                $worker?->name,
                $activity?->name,
                $project?->name,
                $this->statusLabel($session->status),
            ])),
        ];
    }

    private function duration(ActivitySession $session): string
    {
        $minutes = $session->actual_duration_minutes;

        if (!$minutes && $session->started_at) {
            $end = $session->completed_at ?? now();
            $minutes = max(0, $session->started_at->diffInMinutes($end));
        }

        if (!$minutes) {
            return '0m';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return trim(($hours ? $hours . 'h ' : '') . $remaining . 'm');
    }

    private function location(ActivitySession $session): string
    {
        $project = $session->assignment?->project;
        $parts = array_filter([$project?->area, $project?->city, $project?->state]);

        return $parts ? implode(', ', $parts) : $session->start_latitude . ', ' . $session->start_longitude;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending_approval' => 'Pending Review',
            'approved' => 'Completed',
            'rejected' => 'Rejected',
            default => 'Active',
        };
    }

    private function modeLabel(string $mode): string
    {
        return match ($mode) {
            'continuous_tracking' => 'Continuous Tracking',
            'single_submission' => 'Single Submission',
            default => 'Start - End',
        };
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('') ?: 'WK';
    }

    private function liveWorkerRow(User $worker, ?WorkerLocation $location, ?ActivitySession $session, ?ActivitySubmission $submission, mixed $routeLocations): array
    {
        $assignment = $location?->assignment ?? $submission?->assignment ?? $session?->assignment ?? $worker->assignments->first();
        $project = $assignment?->project;
        $activity = $assignment?->projectActivity;
        $activityType = $activity?->activityType;
        $recordedAt = $location?->recorded_at ?? $session?->started_at;
        $lastSeenMinutes = $recordedAt ? (int) $recordedAt->diffInMinutes(now()) : null;
        $status = $this->trackingStatus($lastSeenMinutes, $assignment?->status);
        $latitude = $location?->latitude ?? $submission?->latitude ?? $session?->end_latitude ?? $session?->start_latitude;
        $longitude = $location?->longitude ?? $submission?->longitude ?? $session?->end_longitude ?? $session?->start_longitude;
        $submissionAt = $submission?->device_timestamp ?? $submission?->server_timestamp ?? $submission?->created_at;

        return [
            'id' => $worker->id,
            'name' => $worker->name,
            'code' => 'USR' . str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT),
            'mobile' => $worker->mobile ?? 'N/A',
            'company' => $project?->company?->name ?? $worker->company?->name ?? 'Internal',
            'initials' => $this->initials($worker->name),
            'status' => $status,
            'status_label' => $this->trackingStatusLabel($status),
            'last_seen' => $recordedAt ? $recordedAt->diffForHumans() : 'No location yet',
            'last_seen_minutes' => $lastSeenMinutes,
            'latitude' => $latitude ? (float) $latitude : null,
            'longitude' => $longitude ? (float) $longitude : null,
            'accuracy' => $location?->accuracy,
            'speed' => $location?->speed,
            'heading' => $location?->heading,
            'route' => $routeLocations
                ->map(fn (WorkerLocation $point): array => [
                    'lat' => (float) $point->latitude,
                    'lng' => (float) $point->longitude,
                    'time' => $point->recorded_at?->format('d M Y, h:i A'),
                ])
                ->values(),
            'assignment' => $activity?->name ?? 'No active assignment',
            'project' => $project?->name ?? 'No active project',
            'activity_type' => $activityType?->name ?? 'N/A',
            'activity_mode' => $this->modeLabel((string) ($activityType?->activity_mode ?? 'start_end')),
            'started_at' => $assignment?->started_at?->format('d M Y, h:i A') ?? $session?->started_at?->format('d M Y, h:i A') ?? 'Not started',
            'location_text' => $this->locationText($project, $latitude, $longitude),
            'evidence_url' => $submission?->image_url ?? $session?->end_image_url ?? $session?->start_image_url ?? asset('images/admin-construction.jpg'),
            'photo' => [
                'url' => $submission?->image_url,
                'lat' => $submission?->latitude ? (float) $submission->latitude : null,
                'lng' => $submission?->longitude ? (float) $submission->longitude : null,
                'accuracy' => $submission?->location_accuracy,
                'time' => $submissionAt?->format('d M Y, h:i A'),
                'remark' => $submission?->remark ?: 'No remark added.',
                'status' => $submission?->approval_status,
                'assignment' => $submission?->assignment?->projectActivity?->name,
            ],
        ];
    }

    private function trackingStatus(?int $lastSeenMinutes, ?string $assignmentStatus): string
    {
        if ($lastSeenMinutes === null) {
            return 'offline';
        }

        if ($assignmentStatus === 'pending_approval') {
            return 'break';
        }

        return $lastSeenMinutes <= 15 ? 'active' : ($lastSeenMinutes <= 60 ? 'break' : 'offline');
    }

    private function trackingStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Active now',
            'break' => 'Idle / pending',
            default => 'Offline',
        };
    }

    private function locationText(?Project $project, mixed $latitude, mixed $longitude): string
    {
        $parts = array_filter([$project?->city, $project?->state]);

        if ($parts) {
            return implode(', ', $parts);
        }

        return $latitude && $longitude ? $latitude . ', ' . $longitude : 'Location unavailable';
    }
}
