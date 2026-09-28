<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivitySession;
use App\Models\ActivityUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityUpdateController extends Controller
{
    /**
     * Get activity updates.
     *
     * Admin / Super Admin:
     * Can view all updates.
     *
     * Company:
     * Can view updates only for its own projects.
     *
     * Worker:
     * Can view only own updates.
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
                    'You are not authorized to view activity updates.',
            ], 403);
        }

        $query = ActivityUpdate::with([
            'session.assignment.project',
            'session.assignment.projectActivity.activityType',
            'worker',
        ]);

        /*
         * COMPANY ISOLATION
         *
         * Company can see updates only when:
         *
         * update
         * -> session
         * -> assignment
         * -> project
         * -> company_id
         *
         * matches logged-in company.
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
                'session.assignment.project',
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
        if ($request->filled('session_id')) {
            $query->where(
                'session_id',
                $request->session_id
            );
        }

        /*
         * Worker cannot manipulate worker_id
         * filter to see another worker.
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

        $updates = $query
            ->orderByDesc('server_timestamp')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' =>
                'Activity updates fetched successfully.',
            'data' => $updates,
        ]);
    }

    /**
     * Worker submits progress update
     * during active session.
     */
    public function store(Request $request): JsonResponse
    {
        $worker = $request->user();

        /*
         * Only workers can create updates.
         */
        if ($worker->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only workers can submit activity updates.',
            ], 403);
        }

        $validated = $request->validate([
            'session_id' => [
                'required',
                'integer',
                'exists:activity_sessions,id',
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

            'remark' => [
                'nullable',
                'string',
            ],

            'device_timestamp' => [
                'nullable',
                'date',
            ],
        ]);

        /*
         * Load complete relationship chain
         * for ownership/company validation.
         */
        $session = ActivitySession::with([
            'assignment.project',
            'assignment.projectActivity.activityType',
        ])->findOrFail(
            $validated['session_id']
        );

        /*
         * Session must belong to
         * logged-in worker.
         */
        if ($session->worker_id != $worker->id) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This activity session does not belong to you.',
            ], 403);
        }

        /*
         * Additional company consistency.
         *
         * For company projects, worker must
         * belong to same company.
         */
        $project = $session
            ->assignment
            ?->project;

        if (
            $project
            && $project->project_type === 'company'
            && $project->company_id !== null
            && $worker->company_id
                != $project->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to submit updates for this company project.',
            ], 403);
        }

        /*
         * Updates are accepted only
         * during active session.
         */
        if ($session->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Updates can only be submitted for an in-progress session.',
            ], 422);
        }

        /*
         * Store progress image.
         */
        $imagePath = $request
            ->file('image')
            ->store(
                'activity-updates',
                'public'
            );

        $update = ActivityUpdate::create([
            'session_id' =>
                $session->id,

            /*
             * Never trust worker_id
             * supplied by client.
             */
            'worker_id' =>
                $worker->id,

            'image_path' =>
                $imagePath,

            'latitude' =>
                $validated['latitude'],

            'longitude' =>
                $validated['longitude'],

            'location_accuracy' =>
                $validated['location_accuracy']
                ?? null,

            'remark' =>
                $validated['remark']
                ?? null,

            'device_timestamp' =>
                $validated['device_timestamp']
                ?? null,

            /*
             * Backend authoritative timestamp.
             */
            'server_timestamp' =>
                now(),
        ]);

        $update->load([
            'session.assignment.project',
            'session.assignment.projectActivity.activityType',
            'worker',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Activity progress update submitted successfully.',
            'data' => [
                'update' => $update,
            ],
        ], 201);
    }

    /**
     * Get single activity update.
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
                    'You are not authorized to view this activity update.',
            ], 403);
        }

        $query = ActivityUpdate::with([
            'session.assignment.project',
            'session.assignment.projectActivity.activityType',
            'worker',
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
                'session.assignment.project',
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

        $update = $query->find($id);

        if (!$update) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Activity update not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Activity update fetched successfully.',
            'data' => [
                'update' => $update,
            ],
        ]);
    }
}