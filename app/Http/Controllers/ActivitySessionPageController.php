<?php

namespace App\Http\Controllers;

use App\Models\ActivitySession;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivitySessionPageController extends Controller
{
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

    public function show(Request $request, ActivitySession $session): View
    {
        abort_unless($request->user()->status === 'active' && in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
        $session->load(['assignment.project.company', 'assignment.projectActivity.activityType', 'worker', 'reviewer']);

        return view('activity_sessions.show', ['session' => $this->row($session)]);
    }

    public function review(Request $request, ActivitySession $session): RedirectResponse
    {
        abort_unless($request->user()->status === 'active' && in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
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
            ->route('web.activity-sessions.show', $session)
            ->with('status', 'Session '.($validated['status'] === 'approved' ? 'approved' : 'rejected').' successfully.');
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
            'code' => 'SES'.str_pad((string) $session->id, 4, '0', STR_PAD_LEFT),
            'worker' => $worker?->name ?? 'Worker',
            'worker_code' => $worker ? 'USR'.str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : 'USR---',
            'worker_mobile' => $worker?->mobile ?? 'N/A',
            'worker_initials' => $this->initials($worker?->name ?? 'Worker'),
            'assignment' => $activity?->name ?? 'Assignment #'.$assignment?->id,
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
            'start_gps' => $session->start_latitude.', '.$session->start_longitude,
            'end_gps' => $session->end_latitude && $session->end_longitude ? $session->end_latitude.', '.$session->end_longitude : 'Not completed',
            'start_image_url' => $session->start_image_url ?? '',
            'end_image_url' => $session->end_image_url ?? '',
            'remark' => $session->remark ?: 'No remark added.',
            'reviewer' => $session->reviewer?->name,
            'reviewed_at' => $session->reviewed_at?->format('d M Y h:i A'),
            'rejection_reason' => $session->rejection_reason,
            'search' => Str::lower(implode(' ', [
                'SES'.str_pad((string) $session->id, 4, '0', STR_PAD_LEFT),
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

        if (! $minutes && $session->started_at) {
            $end = $session->completed_at ?? now();
            $minutes = max(0, $session->started_at->diffInMinutes($end));
        }

        if (! $minutes) {
            return '0m';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return trim(($hours ? $hours.'h ' : '').$remaining.'m');
    }

    private function location(ActivitySession $session): string
    {
        $project = $session->assignment?->project;
        $parts = array_filter([$project?->area, $project?->city, $project?->state]);

        return $parts ? implode(', ', $parts) : $session->start_latitude.', '.$session->start_longitude;
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
}
