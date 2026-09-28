<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectActivityController extends Controller
{
    /**
     * Get project activities.
     *
     * Admin / Super Admin:
     * Can view all activities.
     *
     * Company:
     * Can view only activities belonging
     * to projects of its own company.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['admin', 'super_admin', 'company'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to view project activities.',
            ], 403);
        }

        $query = ProjectActivity::with([
            'project',
            'activityType',
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
                'project',
                function ($q) use ($user) {
                    $q->where(
                        'company_id',
                        $user->company_id
                    );
                }
            );
        }

        if ($request->filled('project_id')) {
            $query->where(
                'project_id',
                $request->project_id
            );
        }

        if ($request->filled('activity_type_id')) {
            $query->where(
                'activity_type_id',
                $request->activity_type_id
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'instructions',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $activities = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' =>
                'Project activities fetched successfully.',
            'data' => $activities,
        ]);
    }

    /**
     * Create project activity.
     *
     * Only Admin / Super Admin.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['admin', 'super_admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only admin or super admin can create project activities.',
            ], 403);
        }

        $validated = $request->validate([
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
            ],

            'activity_type_id' => [
                'required',
                'integer',
                'exists:activity_types,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'target_quantity' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'expected_duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'instructions' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'active',
                    'on_hold',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        $project = Project::findOrFail(
            $validated['project_id']
        );

        /*
         * Activity dates must remain inside
         * parent project's date range.
         */
        if (
            !empty($validated['start_date'])
            && $validated['start_date']
                < $project->start_date->toDateString()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Activity start date cannot be before project start date.',
            ], 422);
        }

        if (
            !empty($validated['end_date'])
            && $project->end_date !== null
            && $validated['end_date']
                > $project->end_date->toDateString()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Activity end date cannot be after project end date.',
            ], 422);
        }

        $activity = ProjectActivity::create(
            $validated
        );

        $activity->load([
            'project',
            'activityType',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Project activity created successfully.',
            'data' => [
                'activity' => $activity,
            ],
        ], 201);
    }

    /**
     * Get single project activity.
     */
    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['admin', 'super_admin', 'company'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to view this project activity.',
            ], 403);
        }

        $query = ProjectActivity::with([
            'project',
            'activityType',
        ])->withCount('assignments');

        /*
         * COMPANY ISOLATION
         *
         * Company cannot manually change
         * activity ID and access another
         * company's project activity.
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
                'project',
                function ($q) use ($user) {
                    $q->where(
                        'company_id',
                        $user->company_id
                    );
                }
            );
        }

        $activity = $query->find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Project activity not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Project activity fetched successfully.',
            'data' => [
                'activity' => $activity,
            ],
        ]);
    }

    /**
     * Update project activity.
     *
     * Only Admin / Super Admin.
     */
    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['admin', 'super_admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only admin or super admin can update project activities.',
            ], 403);
        }

        $activity = ProjectActivity::findOrFail($id);

        $validated = $request->validate([
            'project_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:projects,id',
            ],

            'activity_type_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:activity_types,id',
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'target_quantity' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'expected_duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'instructions' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'pending',
                    'active',
                    'on_hold',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        $projectId =
            $validated['project_id']
            ?? $activity->project_id;

        $project = Project::findOrFail(
            $projectId
        );

        $startDate = array_key_exists(
            'start_date',
            $validated
        )
            ? $validated['start_date']
            : optional(
                $activity->start_date
            )->toDateString();

        $endDate = array_key_exists(
            'end_date',
            $validated
        )
            ? $validated['end_date']
            : optional(
                $activity->end_date
            )->toDateString();

        if (
            $startDate !== null
            && $endDate !== null
            && $endDate < $startDate
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Activity end date must be after or equal to start date.',
            ], 422);
        }

        if (
            $startDate !== null
            && $startDate
                < $project->start_date->toDateString()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Activity start date cannot be before project start date.',
            ], 422);
        }

        if (
            $endDate !== null
            && $project->end_date !== null
            && $endDate
                > $project->end_date->toDateString()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Activity end date cannot be after project end date.',
            ], 422);
        }

        $activity->update(
            $validated
        );

        $activity->load([
            'project',
            'activityType',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Project activity updated successfully.',
            'data' => [
                'activity' => $activity,
            ],
        ]);
    }

    /**
     * Delete project activity.
     *
     * Only Admin / Super Admin.
     */
    public function destroy(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['admin', 'super_admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only admin or super admin can delete project activities.',
            ], 403);
        }

        $activity = ProjectActivity::findOrFail(
            $id
        );

        if ($activity->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Project activity cannot be deleted because worker assignments exist.',
            ], 409);
        }

        $activity->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Project activity deleted successfully.',
        ]);
    }
}