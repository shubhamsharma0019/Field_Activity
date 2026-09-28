<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivitySubmission;
use App\Models\ProjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivitySubmissionController extends Controller
{
    /**
     * Get submissions.
     *
     * Admin / Super Admin:
     * Can view all submissions.
     *
     * Company:
     * Can view only APPROVED submissions
     * belonging to its own company's projects.
     *
     * Worker:
     * Can view only own submissions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['admin', 'super_admin', 'company', 'worker'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to view activity submissions.',
            ], 403);
        }

        $query = ActivitySubmission::with([
            'assignment.project',
            'assignment.projectActivity.activityType',
            'worker',
            'reviewer',
        ]);

        /*
         * COMPANY ISOLATION
         *
         * Company can:
         * 1. See only its own project data.
         * 2. See only approved evidence.
         */
        if ($user->role === 'company') {

            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No company is linked with this account.',
                ], 403);
            }

            $query->whereHas(
                'assignment.project',
                function ($q) use ($user) {
                    $q->where(
                        'company_id',
                        $user->company_id
                    );
                }
            );

            $query->where(
                'approval_status',
                'approved'
            );
        }

        /*
         * WORKER ISOLATION
         *
         * Worker can see only their
         * own submissions.
         */
        if ($user->role === 'worker') {
            $query->where(
                'worker_id',
                $user->id
            );
        }

        /*
         * Filters
         */
        if ($request->filled('assignment_id')) {
            $query->where(
                'assignment_id',
                $request->assignment_id
            );
        }

        /*
         * Worker cannot change worker_id
         * filter to access another worker.
         */
        if (
            $request->filled('worker_id')
            && $user->role !== 'worker'
        ) {
            $query->where(
                'worker_id',
                $request->worker_id
            );
        }

        /*
         * Company is always restricted
         * to approved submissions.
         *
         * Therefore approval_status filter
         * is available only for admin,
         * super_admin and worker.
         */
        if (
            $request->filled('approval_status')
            && $user->role !== 'company'
        ) {
            $query->where(
                'approval_status',
                $request->approval_status
            );
        }

        $submissions = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' =>
                'Activity submissions fetched successfully.',
            'data' => $submissions,
        ]);
    }

    /**
     * Worker creates evidence submission.
     */
    public function store(Request $request): JsonResponse
    {
        $worker = $request->user();

        /*
         * Only worker can submit evidence.
         */
        if ($worker->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only workers can submit activity evidence.',
            ], 403);
        }

        $validated = $request->validate([
            'assignment_id' => [
                'required',
                'integer',
                'exists:project_assignments,id',
            ],

            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'location_accuracy' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'device_timestamp' => [
                'nullable',
                'date',
            ],

            'remark' => [
                'nullable',
                'string',
            ],
        ]);

        $assignment = ProjectAssignment::with([
            'project',
            'projectActivity.activityType',
        ])->findOrFail(
            $validated['assignment_id']
        );

        /*
         * Worker can submit only against
         * their own assignment.
         */
        if ($assignment->worker_id != $worker->id) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This assignment does not belong to you.',
            ], 403);
        }

        /*
         * Additional company consistency check.
         *
         * If assignment belongs to company project,
         * worker must belong to same company.
         */
        if (
            $assignment->project
            && $assignment->project->project_type === 'company'
            && $assignment->project->company_id !== null
            && $worker->company_id
                != $assignment->project->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to submit evidence for this company project.',
            ], 403);
        }

        /*
         * Completed / cancelled assignment
         * cannot accept new evidence.
         */
        if (in_array(
            $assignment->status,
            ['completed', 'cancelled'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Submission cannot be added to this assignment.',
            ], 422);
        }

        /*
         * This endpoint is only for
         * single_submission activity mode.
         */
        $activityType =
            $assignment
                ->projectActivity
                ?->activityType;

        if (
            !$activityType
            || $activityType->activity_mode
                !== 'single_submission'
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This assignment does not use single submission mode.',
            ], 422);
        }

        /*
         * Generate next submission number.
         */
        $lastSubmissionNo =
            ActivitySubmission::where(
                'assignment_id',
                $assignment->id
            )->max('submission_no');

        $submissionNo =
            ($lastSubmissionNo ?? 0) + 1;

        /*
         * Store evidence image.
         */
        $imagePath = $request
            ->file('image')
            ->store(
                'activity-submissions',
                'public'
            );

        $submission = ActivitySubmission::create([
            'assignment_id' =>
                $assignment->id,

            'worker_id' =>
                $worker->id,

            'submission_no' =>
                $submissionNo,

            'image_path' =>
                $imagePath,

            'latitude' =>
                $validated['latitude'],

            'longitude' =>
                $validated['longitude'],

            'location_accuracy' =>
                $validated['location_accuracy']
                ?? null,

            'device_timestamp' =>
                $validated['device_timestamp']
                ?? null,

            /*
             * Server controls authoritative
             * timestamp.
             */
            'server_timestamp' =>
                now(),

            'remark' =>
                $validated['remark']
                ?? null,

            'approval_status' =>
                'pending',
        ]);

        /*
         * First evidence automatically
         * starts assignment.
         */
        if ($assignment->status === 'assigned') {
            $assignment->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $submission->load([
            'assignment.project',
            'assignment.projectActivity.activityType',
            'worker',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Activity evidence submitted successfully.',
            'data' => [
                'submission' =>
                    $submission,
            ],
        ], 201);
    }

    /**
     * Get single submission.
     */
    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['admin', 'super_admin', 'company', 'worker'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to view this activity submission.',
            ], 403);
        }

        $query = ActivitySubmission::with([
            'assignment.project',
            'assignment.projectActivity.activityType',
            'worker',
            'reviewer',
        ]);

        /*
         * COMPANY:
         * Only own company's approved evidence.
         */
        if ($user->role === 'company') {

            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No company is linked with this account.',
                ], 403);
            }

            $query->whereHas(
                'assignment.project',
                function ($q) use ($user) {
                    $q->where(
                        'company_id',
                        $user->company_id
                    );
                }
            );

            $query->where(
                'approval_status',
                'approved'
            );
        }

        /*
         * WORKER:
         * Only own submission.
         */
        if ($user->role === 'worker') {
            $query->where(
                'worker_id',
                $user->id
            );
        }

        $submission = $query->find($id);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Activity submission not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Activity submission fetched successfully.',
            'data' => [
                'submission' =>
                    $submission,
            ],
        ]);
    }

    /**
     * Admin / Super Admin
     * approves or rejects submission.
     */
    public function review(
        Request $request,
        int $id
    ): JsonResponse {
        $reviewer = $request->user();

        /*
         * Only admin / super admin
         * can review evidence.
         */
        if (!in_array(
            $reviewer->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to review submissions.',
            ], 403);
        }

        $submission =
            ActivitySubmission::findOrFail($id);

        $validated = $request->validate([
            'approval_status' => [
                'required',
                Rule::in([
                    'approved',
                    'rejected',
                ]),
            ],

            'rejection_reason' => [
                Rule::requiredIf(
                    $request->approval_status
                        === 'rejected'
                ),
                'nullable',
                'string',
            ],
        ]);

        /*
         * Already reviewed evidence
         * cannot be reviewed again.
         */
        if (
            $submission->approval_status
            !== 'pending'
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This submission has already been reviewed.',
            ], 422);
        }

        $submission->update([
            'approval_status' =>
                $validated['approval_status'],

            'reviewed_by' =>
                $reviewer->id,

            'reviewed_at' =>
                now(),

            'rejection_reason' =>
                $validated['approval_status']
                    === 'rejected'
                    ? $validated[
                        'rejection_reason'
                    ]
                    : null,
        ]);

        /*
         * Update assignment progress
         * after approval/rejection.
         *
         * Count approved submissions every time,
         * so completed_quantity remains accurate.
         */
        $assignment =
            $submission->assignment;

        $approvedCount =
            ActivitySubmission::where(
                'assignment_id',
                $assignment->id
            )
                ->where(
                    'approval_status',
                    'approved'
                )
                ->count();

        $assignment->completed_quantity =
            $approvedCount;

        /*
         * Complete assignment when target
         * quantity has been reached.
         */
        if (
            $assignment->target_quantity !== null
            && $approvedCount
                >= $assignment->target_quantity
        ) {
            $assignment->status =
                'completed';

            $assignment->completed_at =
                now();
        }

        $assignment->save();

        $submission->load([
            'assignment.project',
            'assignment.projectActivity.activityType',
            'worker',
            'reviewer',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Activity submission reviewed successfully.',
            'data' => [
                'submission' =>
                    $submission,
            ],
        ]);
    }
}