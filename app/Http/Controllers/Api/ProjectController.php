<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Get projects.
     *
     * Admin / Super Admin:
     * Can view all projects.
     *
     * Company:
     * Can view only projects belonging
     * to its own company.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        /*
         * Only admin, super_admin and company
         * can access project listing.
         */
        if (!in_array(
            $user->role,
            ['admin', 'super_admin', 'company'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view projects.',
            ], 403);
        }

        $query = Project::with([
            'company',
            'creator',
        ]);

        /*
         * COMPANY ISOLATION
         *
         * Company user can only see projects
         * where project.company_id matches
         * logged-in user's company_id.
         *
         * Ignore company_id supplied in URL
         * for company users.
         */
        if ($user->role === 'company') {

            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
                ], 403);
            }

            $query->where(
                'company_id',
                $user->company_id
            );
        }

        /*
         * Admin / Super Admin can filter
         * projects by company_id.
         */
        if (
            in_array(
                $user->role,
                ['admin', 'super_admin'],
                true
            )
            && $request->filled('company_id')
        ) {
            $query->where(
                'company_id',
                $request->company_id
            );
        }

        if ($request->filled('project_type')) {
            $query->where(
                'project_type',
                $request->project_type
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
                        'project_code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'state',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'city',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'area',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $projects = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Projects fetched successfully.',
            'data' => $projects,
        ]);
    }

    /**
     * Create project.
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
                    'Only admin or super admin can create projects.',
            ], 403);
        }

        $validated = $request->validate([
            'project_code' => [
                'required',
                'string',
                'max:30',
                'unique:projects,project_code',
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'project_type' => [
                'required',
                Rule::in([
                    'company',
                    'internal',
                ]),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'area' => [
                'nullable',
                'string',
                'max:150',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'active',
                    'on_hold',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        /*
         * Company project must have company_id.
         */
        if (
            $validated['project_type'] === 'company'
            && empty($validated['company_id'])
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'company_id is required for a company project.',
            ], 422);
        }

        /*
         * Internal project must not
         * belong to a company.
         */
        if ($validated['project_type'] === 'internal') {
            $validated['company_id'] = null;
        }

        /*
         * Authenticated admin becomes creator.
         */
        $validated['created_by'] = $user->id;

        $project = Project::create($validated);

        $project->load([
            'company',
            'creator',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'data' => [
                'project' => $project,
            ],
        ], 201);
    }

    /**
     * Get single project.
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
                    'You are not authorized to view this project.',
            ], 403);
        }

        $query = Project::with([
            'company',
            'creator',
            'activities.activityType',
        ])
            ->withCount([
                'activities',
                'assignments',
            ]);

        /*
         * COMPANY ISOLATION
         *
         * This is important because otherwise
         * company could manually change project ID
         * in URL and access another company's data.
         */
        if ($user->role === 'company') {

            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No company is linked with this account.',
                ], 403);
            }

            $query->where(
                'company_id',
                $user->company_id
            );
        }

        $project = $query->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Project not found or you do not have access to this project.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project fetched successfully.',
            'data' => [
                'project' => $project,
            ],
        ]);
    }

    /**
     * Update project.
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
                    'Only admin or super admin can update projects.',
            ], 403);
        }

        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'project_code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique(
                    'projects',
                    'project_code'
                )->ignore($project->id),
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'project_type' => [
                'sometimes',
                'required',
                Rule::in([
                    'company',
                    'internal',
                ]),
            ],

            'start_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'area' => [
                'nullable',
                'string',
                'max:150',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'active',
                    'on_hold',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        $projectType =
            $validated['project_type']
            ?? $project->project_type;

        $companyId =
            array_key_exists(
                'company_id',
                $validated
            )
                ? $validated['company_id']
                : $project->company_id;

        if (
            $projectType === 'company'
            && empty($companyId)
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'company_id is required for a company project.',
            ], 422);
        }

        if ($projectType === 'internal') {
            $validated['company_id'] = null;
        }

        /*
         * Validate final date combination.
         */
        $startDate =
            $validated['start_date']
            ?? $project->start_date;

        $endDate =
            array_key_exists(
                'end_date',
                $validated
            )
                ? $validated['end_date']
                : $project->end_date;

        if (
            $endDate !== null
            && strtotime((string) $endDate)
                < strtotime((string) $startDate)
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'end_date must be after or equal to start_date.',
            ], 422);
        }

        $project->update($validated);

        $project->load([
            'company',
            'creator',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => [
                'project' => $project,
            ],
        ]);
    }

    /**
     * Delete project.
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
                    'Only admin or super admin can delete projects.',
            ], 403);
        }

        $project = Project::findOrFail($id);

        if ($project->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Project cannot be deleted because assignments exist.',
            ], 409);
        }

        if ($project->activities()->exists()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Project cannot be deleted because activities exist.',
            ], 409);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Project deleted successfully.',
        ]);
    }
}