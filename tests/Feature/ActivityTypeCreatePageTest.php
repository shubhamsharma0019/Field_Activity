<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTypeCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_create_page_and_correct_invalid_input(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('web.activity-types.create'))
            ->assertOk()->assertSee('Create Activity Type')->assertSee('name="activity_mode"', false);

        $this->from(route('web.activity-types.create'))->post(route('web.activity-types.store'), [
            'name' => 'Test Activity', 'activity_mode' => 'invalid',
        ])->assertRedirect(route('web.activity-types.create'))->assertSessionHasErrors('activity_mode');

        $this->get(route('web.activity-types.create'))->assertOk()->assertSee('Test Activity');
    }
}
