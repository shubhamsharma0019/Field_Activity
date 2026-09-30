<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_edit_and_save_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['name' => 'Test Project', 'project_code' => 'TEST001', 'project_type' => 'internal', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'created_by' => $admin->id, 'status' => 'active']);
        $this->actingAs($admin)->get(route('web.projects.show', $project))->assertOk()->assertSee('Test Project');
        $this->get(route('web.projects.edit', $project))->assertOk()->assertSee('Test Project');
        $data = ['name' => 'Updated', 'area' => 'Office', 'city' => 'Noida', 'state' => 'UP', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'status' => 'on_hold', 'project_code' => 'CHANGED'];
        $this->put(route('web.projects.update', $project), $data)->assertRedirect(route('web.projects.show', $project));
        $this->assertSame('Updated', $project->fresh()->name);
        $this->assertSame('TEST001', $project->fresh()->project_code);
        $this->assertSame('on_hold', $project->fresh()->status);
        $data['end_date'] = '2026-08-01';
        $this->put(route('web.projects.update', $project), $data)->assertSessionHasErrors('end_date');
        $this->assertSame('2026-09-30', $project->fresh()->end_date->toDateString());
    }

    public function test_worker_cannot_manage_project(): void
    {
        $worker = User::factory()->create(['role' => 'worker', 'status' => 'active']);
        $project = Project::create(['name' => 'Test', 'project_code' => 'TEST002', 'project_type' => 'internal', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'created_by' => $worker->id]);
        $this->actingAs($worker)->get(route('web.projects.show', $project))->assertForbidden();
        $this->get(route('web.projects.edit', $project))->assertForbidden();
        $this->put(route('web.projects.update', $project), [])->assertForbidden();
    }
}
