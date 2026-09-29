<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectAssignmentController extends Controller
{
    /**
     * Get assignments.
     *
     * Admin / Super Admin:
     *   Can view all assignments.
     *
     * Company:
     *   Can view only assignments belonging
     *   to its own company's projects.
     *
     * Worker:
     *   Can view only own assignments.
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
                'message' => 'You are not authorized to view assignments.',
            ], 403);
        }

        $query = ProjectAssignment::with([
            'project',
            'projectActivity.activityType',
            'worker',
            'assignedBy',
        ]);

        /*
         * COMPANY:
         * Only assignments from its own company projects.
         */
        if ($user->role === 'company') {
            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
                ], 403);
            }

            $query->whereHas('project', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        /*
         * WORKER:
         * Only own assignments.
         */
        if ($user->role === 'worker') {
            $query->where('worker_id', $user->id);
        }

        /*
         * Optional filters.
         */
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('project_activity_id')) {
            $query->where(
                'project_activity_id',
                $request->project_activity_id
            );
        }

        /*
         * Worker cannot use worker_id
         * to access another worker's data.
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

        if ($request->filled('assigned_date')) {
            $query->whereDate(
                'assigned_date',
                $request->assigned_date
            );
        }

        $assignments = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Assignments fetched successfully.',
            'data' => $assignments,
        ]);
    }

    /**
     * Mobile worker assignment list.
     *
     * Kept separate from the admin index response so Flutter can consume a
     * simple list without having to unwrap Laravel pagination.
     */
    public function workerIndex(Request $request): JsonResponse
    {
        $worker = $request->user();

        if ($worker->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can view this assignment list.',
            ], 403);
        }

        $assignments = ProjectAssignment::with([
            'project.company',
            'projectActivity.activityType',
        ])
            ->where('worker_id', $worker->id)
            ->whereIn('status', [
                'assigned',
                'in_progress',
                'pending_approval',
                'completed',
            ])
            ->latest('assigned_date')
            ->latest('id')
            ->get()
            ->map(function (ProjectAssignment $assignment): array {
                $activityType = $assignment->projectActivity?->activityType;

                return [
                    'id' => $assignment->id,
                    'assignment_id' => $assignment->id,
                    'project_id' => $assignment->project_id,
                    'project_activity_id' => $assignment->project_activity_id,
                    'title' => $assignment->projectActivity?->name,
                    'project_name' => $assignment->project?->name,
                    'company_name' => $assignment->project?->company?->name,
                    'activity_type' => $activityType?->name,
                    'activity_mode' => $activityType?->activity_mode,
                    'tracking_required' => (bool) $assignment->tracking_required,
                    'target_quantity' => $assignment->target_quantity,
                    'completed_quantity' => $assignment->completed_quantity,
                    'assigned_date' => $assignment->assigned_date?->toDateString(),
                    'start_date' => $assignment->projectActivity?->start_date?->toDateString(),
                    'end_date' => $assignment->projectActivity?->end_date?->toDateString(),
                    'status' => $assignment->status,
                    'instructions' => $assignment->projectActivity?->instructions,
                    'location' => trim(collect([
                        $assignment->project?->area,
                        $assignment->project?->city,
                        $assignment->project?->state,
                    ])->filter()->implode(', ')),
                    'project' => $assignment->project,
                    'project_activity' => $assignment->projectActivity,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Worker assignments fetched successfully.',
            'data' => [
                'assignments' => $assignments,
            ],
        ]);
    }

    /**
     * Create worker assignment.
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
                'message' => 'Only admin or super admin can create assignments.',
            ], 403);
        }

        $validated = $request->validate([
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
            ],

            'project_activity_id' => [
                'required',
                'integer',
                'exists:project_activities,id',
            ],

            'worker_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'target_quantity' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'tracking_required' => [
                'nullable',
                'boolean',
            ],

            'assigned_date' => [
                'required',
                'date',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'assigned',
                    'in_progress',
                    'pending_approval',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        /*
         * Get project.
         */
        $project = Project::findOrFail(
            $validated['project_id']
        );

        /*
         * Get activity.
         */
        $projectActivity = ProjectActivity::with(
            'activityType'
        )->findOrFail(
            $validated['project_activity_id']
        );

        /*
         * Activity must belong to selected project.
         */
        if (
            $projectActivity->project_id
            != $validated['project_id']
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Selected project activity does not belong to this project.',
            ], 422);
        }

        /*
         * Get worker.
         */
        $worker = User::findOrFail(
            $validated['worker_id']
        );

        /*
         * Worker role validation.
         */
        if ($worker->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a worker.',
            ], 422);
        }

        /*
         * Worker must be active.
         */
        if ($worker->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Inactive worker cannot be assigned.',
            ], 422);
        }

        /*
         * IMPORTANT COMPANY SECURITY:
         *
         * If this is a company project,
         * worker must belong to same company.
         */
        if (
            $project->project_type === 'company'
            && $project->company_id !== null
            && $worker->company_id != $project->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Worker does not belong to the company assigned to this project.',
            ], 422);
        }

        /*
         * Prevent duplicate active assignment
         * for same worker and activity.
         */
        $duplicateAssignment = ProjectAssignment::where(
            'project_activity_id',
            $validated['project_activity_id']
        )
            ->where(
                'worker_id',
                $validated['worker_id']
            )
            ->whereIn('status', [
                'assigned',
                'in_progress',
                'pending_approval',
            ])
            ->exists();

        if ($duplicateAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'This worker already has an active assignment for this activity.',
            ], 409);
        }

        /*
         * Assignment creator comes from
         * authenticated user.
         */
        $validated['assigned_by'] = $user->id;

        /*
         * New assignment starts with
         * zero completed quantity.
         */
        $validated['completed_quantity'] = 0;

        /*
         * Activity type can force tracking.
         */
        if (
            $projectActivity->activityType
            && $projectActivity
                ->activityType
                ->tracking_required
        ) {
            $validated['tracking_required'] = true;
        }

        $assignment = ProjectAssignment::create(
            $validated
        );

        $assignment->load([
            'project',
            'projectActivity.activityType',
            'worker',
            'assignedBy',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker assigned successfully.',
            'data' => [
                'assignment' => $assignment,
            ],
        ], 201);
    }

    /**
     * Get single assignment.
     *
     * Admin / Super Admin:
     *   Can view any assignment.
     *
     * Company:
     *   Can view only own company's assignment.
     *   Can see only APPROVED submissions/evidence.
     *
     * Worker:
     *   Can view only own assignment.
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
                'message' => 'You are not authorized to view this assignment.',
            ], 403);
        }

        /*
         * IMPORTANT:
         *
         * Admin / Super Admin / Worker:
         *     Load all submissions allowed by assignment access.
         *
         * Company:
         *     Load ONLY approved submissions.
         *
         * This prevents pending/rejected evidence
         * from leaking through assignment detail API.
         */
        $query = ProjectAssignment::with([
            'project',
            'projectActivity.activityType',
            'worker',
            'assignedBy',

            'submissions' => function ($q) use ($user) {
                if ($user->role === 'company') {
                    $q->where(
                        'approval_status',
                        'approved'
                    );
                }
            },

            'sessions.updates',
            'locations',
            'reports',
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
            $query->where(
                'worker_id',
                $user->id
            );
        }

        $assignment = $query->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment fetched successfully.',
            'data' => [
                'assignment' => $assignment,
            ],
        ]);
    }

    /**
     * Update assignment.
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
                'message' => 'Only admin or super admin can update assignments.',
            ], 403);
        }

        $assignment = ProjectAssignment::findOrFail(
            $id
        );

        $validated = $request->validate([
            'target_quantity' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'tracking_required' => [
                'sometimes',
                'boolean',
            ],

            'assigned_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'assigned',
                    'in_progress',
                    'pending_approval',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        $assignment->update(
            $validated
        );

        $assignment->load([
            'project',
            'projectActivity.activityType',
            'worker',
            'assignedBy',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully.',
            'data' => [
                'assignment' => $assignment,
            ],
        ]);
    }

    /**
     * Worker completes continuous tracking assignment.
     */
    public function complete(
        Request $request,
        int $id
    ): JsonResponse {
        $assignment = ProjectAssignment::with([
            'projectActivity.activityType',
        ])->findOrFail($id);

        $user = $request->user();

        /*
         * Only worker can complete activity.
         */
        if ($user->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can complete this activity.',
            ], 403);
        }

        /*
         * Worker can complete only own assignment.
         */
        if ($assignment->worker_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment does not belong to you.',
            ], 403);
        }

        /*
         * Only continuous tracking activity.
         */
        if (
            !$assignment->projectActivity
            || !$assignment
                ->projectActivity
                ->activityType
            || $assignment
                ->projectActivity
                ->activityType
                ->activity_mode
                !== 'continuous_tracking'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Only continuous tracking activities can be completed using this endpoint.',
            ], 422);
        }

        /*
         * Assignment must be in progress.
         */
        if ($assignment->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Only an in-progress assignment can be completed.',
            ], 422);
        }

        /*
         * Tracking data must exist.
         */
        if (!$assignment->locations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot complete activity because no tracking location was recorded.',
            ], 422);
        }

        /*
         * Send to admin for approval.
         */
        $assignment->update([
            'status' => 'pending_approval',
        ]);

        $assignment->load([
            'project',
            'projectActivity.activityType',
            'worker',
            'assignedBy',
            'locations',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Continuous tracking activity completed and sent for approval.',
            'data' => [
                'assignment' => $assignment,
            ],
        ]);
    }

    /**
     * Admin / Super Admin reviews
     * continuous tracking assignment.
     */
    public function review(
        Request $request,
        int $id
    ): JsonResponse {
        $assignment = ProjectAssignment::with([
            'projectActivity.activityType',
        ])->findOrFail($id);

        $user = $request->user();

        /*
         * Only admin / super admin can review.
         */
        if (!in_array(
            $user->role,
            ['admin', 'super_admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin or super admin can review this assignment.',
            ], 403);
        }

        /*
         * Only continuous tracking assignment.
         */
        if (
            !$assignment->projectActivity
            || !$assignment
                ->projectActivity
                ->activityType
            || $assignment
                ->projectActivity
                ->activityType
                ->activity_mode
                !== 'continuous_tracking'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Only continuous tracking assignments can be reviewed using this endpoint.',
            ], 422);
        }

        /*
         * Must be pending approval.
         */
        if ($assignment->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only a pending approval assignment can be reviewed.',
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
        ]);

        /*
         * APPROVED
         */
        if ($validated['status'] === 'approved') {
            $completedQuantity =
                $assignment->target_quantity ?? 1;

            $assignment->update([
                'status' => 'completed',
                'completed_quantity' => $completedQuantity,
                'completed_at' => now(),
            ]);

            $message =
                'Continuous tracking assignment approved successfully.';
        }

        /*
         * REJECTED
         */
        else {
            $assignment->update([
                'status' => 'in_progress',
                'completed_at' => null,
            ]);

            $message =
                'Continuous tracking assignment rejected and returned to worker.';
        }

        $assignment->load([
            'project',
            'projectActivity.activityType',
            'worker',
            'assignedBy',
            'locations',
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'assignment' => $assignment,
            ],
        ]);
    }

    /**
     * Delete assignment.
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
                'message' => 'Only admin or super admin can delete assignments.',
            ], 403);
        }

        $assignment = ProjectAssignment::findOrFail(
            $id
        );

        /*
         * Don't delete assignment once
         * activity data exists.
         */
        if (
            $assignment->submissions()->exists()
            || $assignment->sessions()->exists()
            || $assignment->locations()->exists()
            || $assignment->reports()->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment cannot be deleted because activity data exists.',
            ], 409);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully.',
        ]);
    }
}
