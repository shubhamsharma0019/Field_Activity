<?php

namespace App\Http\Controllers;

use App\Models\ActivitySubmission;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubmissionPageController extends Controller
{
    public function index(): View
    {
        $weekStart = now()->startOfWeek();

        $submissions = ActivitySubmission::query()
            ->with([
                'assignment.project.company',
                'assignment.projectActivity.activityType',
                'worker',
                'reviewer',
            ])
            ->latest('server_timestamp')
            ->latest('id')
            ->get();

        $total = max($submissions->count(), 1);

        $stats = [
            'total' => $submissions->count(),
            'week' => $submissions->where('created_at', '>=', $weekStart)->count(),
            'approved' => $submissions->where('approval_status', 'approved')->count(),
            'approved_percent' => round($submissions->where('approval_status', 'approved')->count() / $total * 100),
            'pending' => $submissions->where('approval_status', 'pending')->count(),
            'pending_percent' => round($submissions->where('approval_status', 'pending')->count() / $total * 100),
            'rejected' => $submissions->where('approval_status', 'rejected')->count(),
            'rejected_percent' => round($submissions->where('approval_status', 'rejected')->count() / $total * 100),
        ];

        return view('submissions.index', [
            'stats' => $stats,
            'submissions' => $submissions->map(fn (ActivitySubmission $submission): array => $this->row($submission))->values(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'company_id']),
            'activityTypes' => ActivityType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, ActivitySubmission $submission): View
    {
        abort_unless($request->user()->status === 'active' && in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
        $submission->load(['assignment.project.company', 'assignment.projectActivity.activityType', 'worker', 'reviewer']);

        return view('submissions.show', ['submission' => $this->row($submission)]);
    }

    public function review(Request $request, ActivitySubmission $submission): RedirectResponse
    {
        abort_unless($request->user()->status === 'active' && in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
        $validated = $request->validate([
            'approval_status' => ['required', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $submission->update([
            'approval_status' => $validated['approval_status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['approval_status'] === 'rejected'
                ? ($validated['rejection_reason'] ?? 'Rejected by admin.')
                : null,
        ]);

        $assignment = $submission->assignment;

        if ($assignment && $validated['approval_status'] === 'approved') {
            $assignment->completed_quantity = ActivitySubmission::query()
                ->where('assignment_id', $assignment->id)
                ->where('approval_status', 'approved')
                ->count();

            if ($assignment->target_quantity && $assignment->completed_quantity >= $assignment->target_quantity) {
                $assignment->status = 'completed';
                $assignment->completed_at = now();
            } elseif ($assignment->status === 'assigned') {
                $assignment->status = 'in_progress';
            }

            $assignment->save();
        }

        return redirect()
            ->route('web.submissions.show', $submission)
            ->with('status', 'Submission '.str_replace('_', ' ', $validated['approval_status']).' successfully.');
    }

    private function row(ActivitySubmission $submission): array
    {
        $assignment = $submission->assignment;
        $project = $assignment?->project;
        $projectActivity = $assignment?->projectActivity;
        $activityType = $projectActivity?->activityType;
        $worker = $submission->worker;
        $location = $this->location($submission);
        $submittedAt = $submission->device_timestamp ?? $submission->server_timestamp ?? $submission->created_at;

        return [
            'id' => $submission->id,
            'title' => $projectActivity?->name ?? 'Submission #'.$submission->submission_no,
            'project' => $project?->name ?? 'Internal Project',
            'project_code' => $project?->project_code ?? 'PRJ---',
            'project_id' => $project?->id,
            'company' => $project?->company?->name ?? 'Internal',
            'company_id' => $project?->company_id,
            'activity_type' => $activityType?->name ?? 'Activity',
            'activity_type_id' => $activityType?->id,
            'mode' => $this->modeLabel((string) ($activityType?->activity_mode ?? 'single_submission')),
            'worker' => $worker?->name ?? 'Worker',
            'worker_code' => $worker ? 'USR'.str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT) : 'USR---',
            'worker_mobile' => $worker?->mobile ?? 'N/A',
            'location' => $location,
            'latitude' => $submission->latitude,
            'longitude' => $submission->longitude,
            'accuracy' => $submission->location_accuracy,
            'submitted_at' => $submittedAt?->format('d M Y, h:i A') ?? 'N/A',
            'photo_count' => '1 photo',
            'image_url' => $submission->image_url ?: asset('images/admin-construction.jpg'),
            'status' => $submission->approval_status,
            'status_label' => $this->statusLabel($submission->approval_status),
            'remark' => $submission->remark ?: 'No remark added.',
            'reviewer' => $submission->reviewer?->name,
            'reviewed_at' => $submission->reviewed_at?->format('d M Y, h:i A'),
            'rejection_reason' => $submission->rejection_reason,
            'search' => Str::lower(implode(' ', [
                $projectActivity?->name,
                $project?->name,
                $project?->company?->name,
                $activityType?->name,
                $worker?->name,
                $location,
                $this->statusLabel($submission->approval_status),
            ])),
        ];
    }

    private function location(ActivitySubmission $submission): string
    {
        $project = $submission->assignment?->project;
        $parts = array_filter([$project?->area, $project?->city, $project?->state]);

        return $parts ? implode(', ', $parts) : $submission->latitude.', '.$submission->longitude;
    }

    private function modeLabel(string $mode): string
    {
        return match ($mode) {
            'start_end' => 'Start-End',
            'continuous_tracking' => 'Continuous Tracking',
            default => 'Single Submission',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Pending Review',
        };
    }
}
