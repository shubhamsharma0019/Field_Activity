<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivitySession;
use App\Models\ProjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivitySessionController extends Controller
{
    /**
     * Get activity sessions.
     *
     * Admin / Super Admin:
     *   Can view all sessions.
     *
     * Company:
     *   Can view sessions belonging to own company's projects.
     *
     * Worker:
     *   Can view only own sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['super_admin', 'admin', 'company', 'worker'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view activity sessions.',
            ], 403);
        }

        $query = ActivitySession::with([
            'assignment.project',
            'assignment.projectActivity.activityType',
            'worker',
            'reviewer',
        ]);

        /*
         * COMPANY ISOLATION
         */
        if ($user->role === 'company') {
            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
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
        }

        /*
         * WORKER ISOLATION
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
         * Worker cannot query another worker.
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

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        $sessions = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Activity sessions fetched successfully.',
            'data' => $sessions,
        ]);
    }

    /**
     * Worker starts an activity session.
     */
    public function start(Request $request): JsonResponse
    {
        $worker = $request->user();

        /*
         * Only workers can start sessions.
         */
        if ($worker->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can start activity sessions.',
            ], 403);
        }

        $validated = $request->validate([
            'assignment_id' => [
                'required',
                'integer',
                'exists:project_assignments,id',
            ],

            'start_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'start_latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'start_longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'start_location_accuracy' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'start_device_timestamp' => [
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
         * Worker can only start own assignment.
         */
        if ($assignment->worker_id !== $worker->id) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment does not belong to you.',
            ], 403);
        }

        /*
         * Company consistency.
         */
        if (
            $assignment->project
            && $assignment->project->company_id !== null
            && $worker->company_id != $assignment->project->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Your company does not match the assignment company.',
            ], 403);
        }

        /*
         * Completed / cancelled / pending assignments
         * cannot start another session.
         */
        if (
            in_array(
                $assignment->status,
                [
                    'completed',
                    'cancelled',
                    'pending_approval',
                ],
                true
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment cannot be started.',
            ], 422);
        }

        $activityType =
            $assignment->projectActivity?->activityType;

        /*
         * This controller handles only
         * start/end activities.
         */
        if (
            !$activityType
            || $activityType->activity_mode !== 'start_end'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment does not use start/end activity mode.',
            ], 422);
        }

        /*
         * Worker cannot have another running
         * session for same assignment.
         */
        $runningSession = ActivitySession::where(
            'assignment_id',
            $assignment->id
        )
            ->where(
                'worker_id',
                $worker->id
            )
            ->where(
                'status',
                'in_progress'
            )
            ->exists();

        if ($runningSession) {
            return response()->json([
                'success' => false,
                'message' => 'An activity session is already in progress.',
            ], 409);
        }

        $imagePath = $request
            ->file('start_image')
            ->store(
                'activity-sessions/start',
                'public'
            );

        $startedAt = now();

        $session = ActivitySession::create([
            'assignment_id' => $assignment->id,
            'worker_id' => $worker->id,

            'start_image_path' => $imagePath,

            'start_latitude' =>
                $validated['start_latitude'],

            'start_longitude' =>
                $validated['start_longitude'],

            'start_location_accuracy' =>
                $validated['start_location_accuracy'] ?? null,

            'start_device_timestamp' =>
                $validated['start_device_timestamp'] ?? null,

            /*
             * Authoritative server start time.
             */
            'started_at' => $startedAt,

            'expected_duration_minutes' =>
                $assignment
                    ->projectActivity
                    ->expected_duration_minutes,

            'remark' =>
                $validated['remark'] ?? null,

            'status' => 'in_progress',
        ]);

        if ($assignment->status === 'assigned') {
            $assignment->update([
                'status' => 'in_progress',
                'started_at' => $startedAt,
            ]);
        }

        $session->load([
            'assignment.project',
            'assignment.projectActivity.activityType',
            'worker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity session started successfully.',
            'data' => [
                'session' => $session,
            ],
        ], 201);
    }

    /**
     * Worker completes activity session.
     */
    public function complete(
        Request $request,
        int $id
    ): JsonResponse {
        $worker = $request->user();

        if ($worker->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can complete activity sessions.',
            ], 403);
        }

        $session = ActivitySession::with([
            'assignment.project',
            'assignment.projectActivity.activityType',
        ])->findOrFail($id);

        /*
         * Worker can complete only own session.
         */
        if ($session->worker_id !== $worker->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to complete this session.',
            ], 403);
        }

        /*
         * Additional assignment ownership protection.
         */
        if (
            !$session->assignment
            || $session->assignment->worker_id !== $worker->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment does not belong to you.',
            ], 403);
        }

        /*
         * Company consistency.
         */
        if (
            $session->assignment->project
            && $session->assignment->project->company_id !== null
            && $worker->company_id
                != $session->assignment->project->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Your company does not match the assignment company.',
            ], 403);
        }

        if ($session->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Only an in-progress session can be completed.',
            ], 422);
        }

        $validated = $request->validate([
            'end_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'end_latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'end_longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'end_location_accuracy' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'end_device_timestamp' => [
                'nullable',
                'date',
            ],

            'remark' => [
                'nullable',
                'string',
            ],
        ]);

        $endImagePath = $request
            ->file('end_image')
            ->store(
                'activity-sessions/end',
                'public'
            );

        $completedAt = now();

        /*
         * Duration calculated using
         * authoritative server times.
         */
        $actualDuration = (int) round(
            $session->started_at
                ->diffInSeconds($completedAt) / 60
        );

        $session->update([
            'end_image_path' => $endImagePath,

            'end_latitude' =>
                $validated['end_latitude'],

            'end_longitude' =>
                $validated['end_longitude'],

            'end_location_accuracy' =>
                $validated['end_location_accuracy'] ?? null,

            'end_device_timestamp' =>
                $validated['end_device_timestamp'] ?? null,

            'completed_at' => $completedAt,

            'actual_duration_minutes' =>
                $actualDuration,

            'remark' =>
                $validated['remark']
                ?? $session->remark,

            'status' => 'pending_approval',
        ]);

        /*
         * Assignment also waits for admin review.
         */
        $session->assignment->update([
            'status' => 'pending_approval',
        ]);

        $session->load([
            'assignment.project',
            'assignment.projectActivity.activityType',
            'worker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity session completed and sent for approval.',
            'data' => [
                'session' => $session,
            ],
        ]);
    }

    /**
     * Admin approves/rejects completed session.
     */
    public function review(
        Request $request,
        int $id
    ): JsonResponse {
        $reviewer = $request->user();

        /*
         * Only Admin / Super Admin.
         */
        if (!in_array(
            $reviewer->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to review activity sessions.',
            ], 403);
        }

        $session = ActivitySession::with(
            'assignment'
        )->findOrFail($id);

        if ($session->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending sessions can be reviewed.',
            ], 422);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'approved',
                    'rejected',
                ]),
            ],

            'rejection_reason' => [
                Rule::requiredIf(
                    $request->status === 'rejected'
                ),
                'nullable',
                'string',
            ],
        ]);

        $session->update([
            'status' => $validated['status'],
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),

            'rejection_reason' =>
                $validated['status'] === 'rejected'
                    ? $validated['rejection_reason']
                    : null,
        ]);

        /*
         * Update assignment according
         * to review result.
         */
        if ($validated['status'] === 'approved') {
            $completedQuantity =
                $session->assignment->target_quantity ?? 1;

            $session->assignment->update([
                'status' => 'completed',
                'completed_quantity' => $completedQuantity,
                'completed_at' => now(),
            ]);
        } else {
            $session->assignment->update([
                'status' => 'in_progress',
                'completed_at' => null,
            ]);
        }

        $session->load([
            'assignment',
            'worker',
            'reviewer',
            'updates',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity session reviewed successfully.',
            'data' => [
                'session' => $session,
            ],
        ]);
    }

    /**
     * Get single session.
     */
    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['super_admin', 'admin', 'company', 'worker'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view activity sessions.',
            ], 403);
        }

        $query = ActivitySession::with([
            'assignment.project',
            'assignment.projectActivity.activityType',
            'worker',
            'reviewer',
            'updates',
        ]);

        /*
         * COMPANY ISOLATION
         */
        if ($user->role === 'company') {
            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
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
        }

        /*
         * WORKER ISOLATION
         */
        if ($user->role === 'worker') {
            $query->where(
                'worker_id',
                $user->id
            );
        }

        $session = $query->find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Activity session not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Activity session fetched successfully.',
            'data' => [
                'session' => $session,
            ],
        ]);
    }
}