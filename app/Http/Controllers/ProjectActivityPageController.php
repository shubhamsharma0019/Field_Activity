<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\Project;
use App\Models\ProjectActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectActivityPageController extends Controller
{
    public function create(Request $request): View
    {
        return view('project_activities.create', [
            'projects' => Project::query()->whereIn('status', ['active', 'draft'])->orderBy('name')->get(['id', 'name', 'start_date', 'end_date']),
            'activityTypes' => ActivityType::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'activity_mode']),
            'selectedProjectId' => $request->query('project_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'name' => ['required', 'string', 'max:150'],
            'target_quantity' => ['nullable', 'integer', 'min:1'],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:pending,active,on_hold,completed,cancelled'],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        if (!empty($validated['start_date']) && $project->start_date && $validated['start_date'] < $project->start_date->toDateString()) {
            return back()->withErrors(['start_date' => 'Activity start date cannot be before project start date.'])->withInput();
        }

        if (!empty($validated['end_date']) && $project->end_date && $validated['end_date'] > $project->end_date->toDateString()) {
            return back()->withErrors(['end_date' => 'Activity end date cannot be after project end date.'])->withInput();
        }

        ProjectActivity::create($validated);

        return redirect()
            ->route('web.assignments.index');
    }
}
