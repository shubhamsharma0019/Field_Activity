<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityReport;
use App\Models\ProjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityReportController extends Controller
{
    /**
     * Get activity reports.
     *
     * Admin / Super Admin:
     *   Can view all reports.
     *
     * Company:
     *   Can view reports belonging to own company's projects.
     *
     * Worker:
     *   Can view only own reports.
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
                'message' => 'You are not authorized to view activity reports.',
            ], 403);
        }

        $query = ActivityReport::with([
            'assignment.project',
            'assignment.projectActivity',
            'worker',
            'resolver',
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
         * Worker cannot use worker_id
         * to view another worker's reports.
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

        if ($request->filled('report_type')) {
            $query->where(
                'report_type',
                $request->report_type
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        $reports = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Activity reports fetched successfully.',
            'data' => $reports,
        ]);
    }

    /**
     * Worker creates issue report.
     */
    public function store(Request $request): JsonResponse
    {
        $worker = $request->user();

        /*
         * Only workers can create reports.
         */
        if ($worker->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can create activity reports.',
            ], 403);
        }

        $validated = $request->validate([
            'assignment_id' => [
                'required',
                'integer',
                'exists:project_assignments,id',
            ],

            'report_type' => [
                'required',
                Rule::in([
                    'technical_issue',
                    'location_issue',
                    'material_issue',
                    'permission_issue',
                    'safety_issue',
                    'other',
                ]),
            ],

            'title' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'required',
                'string',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'location_accuracy' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $assignment = ProjectAssignment::with('project')
            ->findOrFail(
                $validated['assignment_id']
            );

        /*
         * Worker can report only own assignment.
         */
        if ($assignment->worker_id !== $worker->id) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment does not belong to you.',
            ], 403);
        }

        /*
         * Company consistency protection.
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
         * Do not allow new reports after
         * assignment is cancelled.
         *
         * Completed assignments are intentionally
         * allowed so a worker can still report
         * a post-completion issue if required.
         */
        if ($assignment->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a report for a cancelled assignment.',
            ], 422);
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request
                ->file('image')
                ->store(
                    'activity-reports',
                    'public'
                );
        }

        $report = ActivityReport::create([
            'assignment_id' => $assignment->id,
            'worker_id' => $worker->id,

            'report_type' =>
                $validated['report_type'],

            'title' =>
                $validated['title'],

            'description' =>
                $validated['description'],

            'image_path' => $imagePath,

            'latitude' =>
                $validated['latitude'] ?? null,

            'longitude' =>
                $validated['longitude'] ?? null,

            'location_accuracy' =>
                $validated['location_accuracy'] ?? null,

            'status' => 'open',
        ]);

        $report->load([
            'assignment.project',
            'assignment.projectActivity',
            'worker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity report submitted successfully.',
            'data' => [
                'report' => $report,
            ],
        ], 201);
    }

    /**
     * Get single report.
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
                'message' => 'You are not authorized to view activity reports.',
            ], 403);
        }

        $query = ActivityReport::with([
            'assignment.project',
            'assignment.projectActivity',
            'worker',
            'resolver',
        ]);

        /*
         * Company can only see reports
         * from own company's projects.
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
         * Worker can only see own report.
         */
        if ($user->role === 'worker') {
            $query->where(
                'worker_id',
                $user->id
            );
        }

        $report = $query->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Activity report not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Activity report fetched successfully.',
            'data' => [
                'report' => $report,
            ],
        ]);
    }

    /**
     * Admin changes report status.
     *
     * Only Admin / Super Admin.
     */
    public function updateStatus(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        /*
         * Authorization BEFORE processing
         * report management.
         */
        if (!in_array(
            $user->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to manage activity reports.',
            ], 403);
        }

        $report = ActivityReport::findOrFail($id);

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'open',
                    'in_review',
                    'resolved',
                    'rejected',
                ]),
            ],

            'resolution_note' => [
                'nullable',
                'string',
            ],
        ]);

        $updateData = [
            'status' => $validated['status'],
            'resolution_note' =>
                $validated['resolution_note'] ?? null,
        ];

        /*
         * Set resolution information only when
         * report reaches final status.
         */
        if (
            in_array(
                $validated['status'],
                ['resolved', 'rejected'],
                true
            )
        ) {
            $updateData['resolved_by'] = $user->id;
            $updateData['resolved_at'] = now();
        } else {
            $updateData['resolved_by'] = null;
            $updateData['resolved_at'] = null;
        }

        $report->update($updateData);

        $report->load([
            'assignment.project',
            'assignment.projectActivity',
            'worker',
            'resolver',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity report status updated successfully.',
            'data' => [
                'report' => $report,
            ],
        ]);
    }
}