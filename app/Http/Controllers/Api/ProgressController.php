<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivitySubmission;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use App\Models\WorkerLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['super_admin', 'admin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin or super admin can view dashboard progress.',
            ], 403);
        }

        $assignments = ProjectAssignment::query();

        $targetTotal = (int) (clone $assignments)
            ->whereNotNull('target_quantity')
            ->sum('target_quantity');

        $completedTotal = (int) (clone $assignments)
            ->sum('completed_quantity');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard progress fetched successfully.',
            'data' => [
                'companies' => [
                    'total' => Company::count(),
                    'active' => Company::where('status', 'active')->count(),
                    'inactive' => Company::where('status', 'inactive')->count(),
                ],
                'projects' => [
                    'total' => Project::count(),
                    'by_status' => $this->countsByStatus(Project::query()),
                ],
                'workers' => [
                    'total' => User::where('role', 'worker')->count(),
                    'active' => User::where('role', 'worker')
                        ->where('status', 'active')
                        ->count(),
                    'inactive' => User::where('role', 'worker')
                        ->where('status', 'inactive')
                        ->count(),
                ],
                'assignments' => [
                    'total' => ProjectAssignment::count(),
                    'by_status' => $this->countsByStatus(ProjectAssignment::query()),
                    'target_quantity' => $targetTotal,
                    'completed_quantity' => $completedTotal,
                    'progress_percentage' => $this->percentage(
                        $completedTotal,
                        $targetTotal
                    ),
                ],
                'submissions' => [
                    'total' => ActivitySubmission::count(),
                    'pending' => ActivitySubmission::where('approval_status', 'pending')->count(),
                    'approved' => ActivitySubmission::where('approval_status', 'approved')->count(),
                    'rejected' => ActivitySubmission::where('approval_status', 'rejected')->count(),
                ],
            ],
        ]);
    }

    public function project(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['super_admin', 'admin', 'company'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view project progress.',
            ], 403);
        }

        $projectQuery = Project::with('company');

        if ($user->role === 'company') {
            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
                ], 403);
            }

            $projectQuery->where('company_id', $user->company_id);
        }

        $project = $projectQuery->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found or you do not have access to this project.',
            ], 404);
        }

        $assignments = ProjectAssignment::where('project_id', $project->id);

        $targetTotal = (int) (clone $assignments)
            ->whereNotNull('target_quantity')
            ->sum('target_quantity');

        $completedTotal = (int) (clone $assignments)
            ->sum('completed_quantity');

        return response()->json([
            'success' => true,
            'message' => 'Project progress fetched successfully.',
            'data' => [
                'project' => $project,
                'summary' => [
                    'assignments_total' => (clone $assignments)->count(),
                    'assignments_by_status' => $this->countsByStatus(clone $assignments),
                    'target_quantity' => $targetTotal,
                    'completed_quantity' => $completedTotal,
                    'progress_percentage' => $this->percentage(
                        $completedTotal,
                        $targetTotal
                    ),
                    'pending_submissions' => ActivitySubmission::whereHas(
                        'assignment',
                        fn ($query) => $query->where('project_id', $project->id)
                    )
                        ->where('approval_status', 'pending')
                        ->count(),
                ],
                'activity_progress' => $this->activityProgress($project->id),
                'worker_progress' => $this->workerProgressForProject($project->id),
            ],
        ]);
    }

    public function worker(Request $request, int $id): JsonResponse
    {
        $authUser = $request->user();

        if (!in_array($authUser->role, ['super_admin', 'admin', 'company', 'worker'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view worker progress.',
            ], 403);
        }

        $workerQuery = User::with('company')->where('role', 'worker');

        if ($authUser->role === 'worker') {
            $workerQuery->where('id', $authUser->id);
        }

        if ($authUser->role === 'company') {
            if (empty($authUser->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
                ], 403);
            }

            $workerQuery->where('company_id', $authUser->company_id);
        }

        $worker = $workerQuery->find($id);

        if (!$worker) {
            return response()->json([
                'success' => false,
                'message' => 'Worker not found or you do not have access to this worker.',
            ], 404);
        }

        $assignments = ProjectAssignment::where('worker_id', $worker->id);

        if ($authUser->role === 'company') {
            $assignments->whereHas(
                'project',
                fn ($query) => $query->where('company_id', $authUser->company_id)
            );
        }

        $targetTotal = (int) (clone $assignments)
            ->whereNotNull('target_quantity')
            ->sum('target_quantity');

        $completedTotal = (int) (clone $assignments)
            ->sum('completed_quantity');

        $latestLocation = WorkerLocation::with([
            'assignment.project',
            'assignment.projectActivity.activityType',
        ])
            ->where('worker_id', $worker->id)
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Worker progress fetched successfully.',
            'data' => [
                'worker' => $worker,
                'summary' => [
                    'assignments_total' => (clone $assignments)->count(),
                    'assignments_by_status' => $this->countsByStatus(clone $assignments),
                    'target_quantity' => $targetTotal,
                    'completed_quantity' => $completedTotal,
                    'progress_percentage' => $this->percentage(
                        $completedTotal,
                        $targetTotal
                    ),
                    'submissions' => [
                        'pending' => ActivitySubmission::where('worker_id', $worker->id)
                            ->where('approval_status', 'pending')
                            ->count(),
                        'approved' => ActivitySubmission::where('worker_id', $worker->id)
                            ->where('approval_status', 'approved')
                            ->count(),
                        'rejected' => ActivitySubmission::where('worker_id', $worker->id)
                            ->where('approval_status', 'rejected')
                            ->count(),
                    ],
                ],
                'latest_location' => $latestLocation,
                'assignments' => (clone $assignments)
                    ->with([
                        'project',
                        'projectActivity.activityType',
                    ])
                    ->latest()
                    ->paginate(20),
            ],
        ]);
    }

    private function countsByStatus($query): array
    {
        return $query
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->toArray();
    }

    private function percentage(int $completed, int $target): float
    {
        if ($target <= 0) {
            return 0.0;
        }

        return round(min(100, ($completed / $target) * 100), 2);
    }

    private function activityProgress(int $projectId): array
    {
        return ProjectAssignment::query()
            ->with('projectActivity.activityType')
            ->where('project_id', $projectId)
            ->select(
                'project_activity_id',
                DB::raw('COUNT(*) as assignments_total'),
                DB::raw('COALESCE(SUM(target_quantity), 0) as target_quantity'),
                DB::raw('COALESCE(SUM(completed_quantity), 0) as completed_quantity')
            )
            ->groupBy('project_activity_id')
            ->get()
            ->map(function (ProjectAssignment $assignment) {
                $target = (int) $assignment->target_quantity;
                $completed = (int) $assignment->completed_quantity;

                return [
                    'project_activity' => $assignment->projectActivity,
                    'assignments_total' => (int) $assignment->assignments_total,
                    'target_quantity' => $target,
                    'completed_quantity' => $completed,
                    'progress_percentage' => $this->percentage($completed, $target),
                ];
            })
            ->values()
            ->toArray();
    }

    private function workerProgressForProject(int $projectId): array
    {
        return ProjectAssignment::query()
            ->with('worker')
            ->where('project_id', $projectId)
            ->select(
                'worker_id',
                DB::raw('COUNT(*) as assignments_total'),
                DB::raw('COALESCE(SUM(target_quantity), 0) as target_quantity'),
                DB::raw('COALESCE(SUM(completed_quantity), 0) as completed_quantity')
            )
            ->groupBy('worker_id')
            ->get()
            ->map(function (ProjectAssignment $assignment) {
                $target = (int) $assignment->target_quantity;
                $completed = (int) $assignment->completed_quantity;

                return [
                    'worker' => $assignment->worker,
                    'assignments_total' => (int) $assignment->assignments_total,
                    'target_quantity' => $target,
                    'completed_quantity' => $completed,
                    'progress_percentage' => $this->percentage($completed, $target),
                ];
            })
            ->values()
            ->toArray();
    }
}
