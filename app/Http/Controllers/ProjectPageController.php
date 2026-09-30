<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectPageController extends Controller
{
    private function authorizeProjectManagement(Request $request): void
    {
        abort_unless($request->user()->status === 'active' && in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
    }

    public function show(Request $request, Project $project): View
    {
        $this->authorizeProjectManagement($request);
        $project->load('company', 'creator')->loadCount(['activities', 'assignments']);
        $target = (int) $project->assignments()->sum('target_quantity');
        $completed = (int) $project->assignments()->sum('completed_quantity');
        $workers = $project->assignments()->distinct()->count('worker_id');

        return view('projects.show', compact('project', 'target', 'completed', 'workers'));
    }

    public function edit(Request $request, Project $project): View
    {
        $this->authorizeProjectManagement($request);
        $project->load('company');

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectManagement($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'area' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:active,on_hold,completed,draft'],
        ]);
        $project->update($validated);

        return redirect()->route('web.projects.show', $project)->with('success', 'Project updated successfully.');
    }

    public function index(): View
    {
        $monthStart = now()->startOfMonth();

        $projects = Project::query()
            ->with('company')
            ->withCount([
                'activities',
                'assignments',
                'assignments as workers_count' => fn ($query) => $query->select(DB::raw('COUNT(DISTINCT worker_id)')),
            ])
            ->withSum('assignments as target_quantity', 'target_quantity')
            ->withSum('assignments as completed_quantity', 'completed_quantity')
            ->latest()
            ->get()
            ->map(function (Project $project): array {
                $target = (int) ($project->target_quantity ?? 0);
                $completed = (int) ($project->completed_quantity ?? 0);
                $progress = $target > 0 ? round(min(100, ($completed / $target) * 100)) : 0;

                return [
                    'id' => $project->id,
                    'show_url' => route('web.projects.show', $project),
                    'edit_url' => route('web.projects.edit', $project),
                    'project_code' => $project->project_code,
                    'name' => $project->name,
                    'description' => $project->description,
                    'project_type' => $project->project_type,
                    'project_type_label' => str($project->project_type)->title()->toString(),
                    'company_id' => $project->company_id,
                    'company' => $project->company?->name ?? 'Internal Project',
                    'location' => collect([$project->area, $project->city, $project->state])->filter()->implode(', ') ?: '-',
                    'start_date' => $project->start_date?->toDateString(),
                    'start_date_label' => $project->start_date?->format('d M Y') ?? '-',
                    'end_date' => $project->end_date?->toDateString(),
                    'end_date_label' => $project->end_date?->format('d M Y') ?? '-',
                    'status' => $project->status,
                    'status_label' => str($project->status)->replace('_', ' ')->title()->toString(),
                    'activities_count' => (int) $project->activities_count,
                    'assignments_count' => (int) $project->assignments_count,
                    'workers_count' => (int) $project->workers_count,
                    'target' => $target,
                    'approved' => $completed,
                    'remaining' => max($target - $completed, 0),
                    'progress' => $progress,
                    'created_at' => $project->created_at?->toDateString(),
                ];
            })
            ->values();

        $companies = Company::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company): array => [
                'id' => $company->id,
                'name' => $company->name,
            ])
            ->values();

        return view('projects.index', [
            'projects' => $projects,
            'companies' => $companies,
            'summary' => [
                'total' => $projects->count(),
                'total_month' => Project::where('created_at', '>=', $monthStart)->count(),
                'active' => $projects->where('status', 'active')->count(),
                'active_month' => Project::where('status', 'active')->where('created_at', '>=', $monthStart)->count(),
                'on_hold' => $projects->where('status', 'on_hold')->count(),
                'on_hold_month' => Project::where('status', 'on_hold')->where('created_at', '>=', $monthStart)->count(),
                'completed' => $projects->where('status', 'completed')->count(),
                'completed_month' => Project::where('status', 'completed')->where('created_at', '>=', $monthStart)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('projects.create', [
            'companies' => Company::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_name' => ['required', 'string', 'max:150'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'project_code' => ['nullable', 'string', 'max:30', 'unique:projects,project_code'],
            'status' => ['required', 'string', 'in:Active,On Hold,Completed,Draft,active,on_hold,completed,draft'],
            'description' => ['nullable', 'string', 'max:500'],
            'address' => ['required', 'string', 'max:150'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        Project::create([
            'project_code' => $validated['project_code'] ?: $this->nextProjectCode(),
            'company_id' => $validated['company_id'] ?: null,
            'name' => $validated['project_name'],
            'description' => $validated['description'] ?? null,
            'project_type' => empty($validated['company_id']) ? 'internal' : 'company',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'state' => $validated['state'],
            'city' => $validated['city'],
            'area' => $validated['address'],
            'status' => $this->projectStatus($validated['status']),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('web.projects.index')
            ->with('status', 'Project created successfully.');
    }

    private function nextProjectCode(): string
    {
        $number = Project::count() + 1;

        do {
            $code = 'PRJ'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (Project::where('project_code', $code)->exists());

        return $code;
    }

    private function projectStatus(string $status): string
    {
        return match (Str::lower($status)) {
            'on hold' => 'on_hold',
            'completed' => 'completed',
            'draft' => 'draft',
            default => 'active',
        };
    }
}
