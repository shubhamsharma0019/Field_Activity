<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectAssignment;
use App\Models\WorkerLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerLocationController extends Controller
{
    /**
     * Get worker location history.
     *
     * Admin / Super Admin:
     * Can view all locations.
     *
     * Company:
     * Can view locations only for
     * its own company's projects.
     *
     * Worker:
     * Can view only own locations.
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
                    'You are not authorized to view worker locations.',
            ], 403);
        }

        $query = WorkerLocation::with([
            'worker',
            'assignment.project',
            'assignment.projectActivity.activityType',
        ]);

        /*
         * COMPANY ISOLATION
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
         * Optional worker filter.
         *
         * Worker cannot manipulate this
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

        if ($request->filled('assignment_id')) {
            $query->where(
                'assignment_id',
                $request->assignment_id
            );
        }

        if ($request->filled('date')) {
            $query->whereDate(
                'recorded_at',
                $request->date
            );
        }

        $locations = $query
            ->orderByDesc('recorded_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'message' =>
                'Worker locations fetched successfully.',
            'data' => $locations,
        ]);
    }

    /**
     * Worker sends current GPS location.
     */
    public function store(Request $request): JsonResponse
    {
        $worker = $request->user();

        /*
         * Only worker can submit GPS.
         */
        if ($worker->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only workers can submit location data.',
            ], 403);
        }

        $validated = $request->validate([
            'assignment_id' => [
                'required',
                'integer',
                'exists:project_assignments,id',
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

            'accuracy' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'speed' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'heading' => [
                'nullable',
                'numeric',
                'between:0,360',
            ],
        ]);

        $assignment = ProjectAssignment::with([
            'project',
            'projectActivity.activityType',
        ])->findOrFail(
            $validated['assignment_id']
        );

        /*
         * Worker can send location only
         * for own assignment.
         */
        if ($assignment->worker_id != $worker->id) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This assignment does not belong to you.',
            ], 403);
        }

        /*
         * Company consistency.
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
                    'You are not authorized to submit location for this company project.',
            ], 403);
        }

        /*
         * Tracking must be enabled.
         */
        if (!$assignment->tracking_required) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Location tracking is not enabled for this assignment.',
            ], 422);
        }

        /*
         * Do not accept locations for
         * completed/cancelled assignments.
         */
        if (in_array(
            $assignment->status,
            ['completed', 'cancelled'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Location cannot be submitted for this assignment.',
            ], 422);
        }

        $location = WorkerLocation::create([
            'worker_id' =>
                $worker->id,

            'assignment_id' =>
                $assignment->id,

            'latitude' =>
                $validated['latitude'],

            'longitude' =>
                $validated['longitude'],

            'accuracy' =>
                $validated['accuracy'] ?? null,

            'speed' =>
                $validated['speed'] ?? null,

            'heading' =>
                $validated['heading'] ?? null,

            /*
             * Authoritative server timestamp.
             */
            'recorded_at' =>
                now(),
        ]);

        /*
         * First GPS point starts assignment.
         */
        if ($assignment->status === 'assigned') {
            $assignment->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $location->load([
            'worker',
            'assignment.project',
            'assignment.projectActivity.activityType',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Worker location recorded successfully.',
            'data' => [
                'location' => $location,
            ],
        ], 201);
    }

    /**
     * Get latest location for assignment.
     */
    public function latest(
        Request $request,
        int $assignmentId
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
                    'You are not authorized to view this location.',
            ], 403);
        }

        /*
         * First authorize assignment.
         */
        $assignmentQuery =
            ProjectAssignment::query();

        /*
         * Company can access only
         * own company's assignment.
         */
        if ($user->role === 'company') {

            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No company is linked with this account.',
                ], 403);
            }

            $assignmentQuery->whereHas(
                'project',
                function ($q) use ($user) {
                    $q->where(
                        'company_id',
                        $user->company_id
                    );
                }
            );
        }

        /*
         * Worker can access only
         * own assignment.
         */
        if ($user->role === 'worker') {
            $assignmentQuery->where(
                'worker_id',
                $user->id
            );
        }

        $assignment =
            $assignmentQuery->find(
                $assignmentId
            );

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Assignment not found or you do not have access to it.',
            ], 404);
        }

        $location = WorkerLocation::with([
            'worker',
            'assignment.project',
            'assignment.projectActivity.activityType',
        ])
            ->where(
                'assignment_id',
                $assignment->id
            )
            ->latest('recorded_at')
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No location found for this assignment.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Latest worker location fetched successfully.',
            'data' => [
                'location' => $location,
            ],
        ]);
    }

    /**
     * Get complete route/history
     * for assignment.
     */
    public function history(
        Request $request,
        int $assignmentId
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
                    'You are not authorized to view location history.',
            ], 403);
        }

        /*
         * Authorize assignment first.
         */
        $assignmentQuery =
            ProjectAssignment::query();

        /*
         * COMPANY ISOLATION
         */
        if ($user->role === 'company') {

            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No company is linked with this account.',
                ], 403);
            }

            $assignmentQuery->whereHas(
                'project',
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
            $assignmentQuery->where(
                'worker_id',
                $user->id
            );
        }

        $assignment =
            $assignmentQuery->find(
                $assignmentId
            );

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Assignment not found or you do not have access to it.',
            ], 404);
        }

        $locations = WorkerLocation::with([
            'worker',
        ])
            ->where(
                'assignment_id',
                $assignment->id
            )
            ->orderBy('recorded_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' =>
                'Worker location history fetched successfully.',
            'data' => [
                'locations' => $locations,
            ],
        ]);
    }
}