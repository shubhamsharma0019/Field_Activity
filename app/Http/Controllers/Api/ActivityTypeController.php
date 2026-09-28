<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityTypeController extends Controller
{
    /**
     * Get all activity types.
     *
     * Admin / Super Admin / Company / Worker
     * can read activity types.
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
                'message' => 'You are not authorized to view activity types.',
            ], 403);
        }

        $query = ActivityType::query();

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('activity_mode')) {
            $query->where(
                'activity_mode',
                $request->activity_mode
            );
        }

        if ($request->filled('search')) {
            $query->where(
                'name',
                'like',
                '%' . $request->search . '%'
            );
        }

        $activityTypes = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Activity types fetched successfully.',
            'data' => $activityTypes,
        ]);
    }

    /**
     * Create activity type.
     *
     * Only Admin / Super Admin.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin or super admin can create activity types.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:activity_types,name',
            ],

            'activity_mode' => [
                'required',
                Rule::in([
                    'single_submission',
                    'start_end',
                    'continuous_tracking',
                ]),
            ],

            'tracking_required' => [
                'nullable',
                'boolean',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        /*
         * Continuous tracking always
         * requires tracking.
         */
        if (
            $validated['activity_mode']
            === 'continuous_tracking'
        ) {
            $validated['tracking_required'] = true;
        }

        $activityType = ActivityType::create(
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity type created successfully.',
            'data' => [
                'activity_type' => $activityType,
            ],
        ], 201);
    }

    /**
     * Get single activity type.
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
                'message' => 'You are not authorized to view activity types.',
            ], 403);
        }

        $activityType = ActivityType::withCount(
            'projectActivities'
        )->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Activity type fetched successfully.',
            'data' => [
                'activity_type' => $activityType,
            ],
        ]);
    }

    /**
     * Update activity type.
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
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin or super admin can update activity types.',
            ], 403);
        }

        $activityType = ActivityType::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'activity_types',
                    'name'
                )->ignore($activityType->id),
            ],

            'activity_mode' => [
                'sometimes',
                'required',
                Rule::in([
                    'single_submission',
                    'start_end',
                    'continuous_tracking',
                ]),
            ],

            'tracking_required' => [
                'sometimes',
                'boolean',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $activityMode =
            $validated['activity_mode']
            ?? $activityType->activity_mode;

        /*
         * Continuous tracking must always
         * have tracking enabled.
         */
        if ($activityMode === 'continuous_tracking') {
            $validated['tracking_required'] = true;
        }

        $activityType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activity type updated successfully.',
            'data' => [
                'activity_type' => $activityType->fresh(),
            ],
        ]);
    }

    /**
     * Delete activity type.
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
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin or super admin can delete activity types.',
            ], 403);
        }

        $activityType = ActivityType::findOrFail($id);

        /*
         * Cannot delete an activity type
         * already used by project activities.
         */
        if (
            $activityType
                ->projectActivities()
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Activity type cannot be deleted because project activities are using it.',
            ], 409);
        }

        $activityType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity type deleted successfully.',
        ]);
    }
}