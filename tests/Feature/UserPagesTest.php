<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_edit_and_save_user(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'worker', 'status' => 'active', 'mobile' => '9876543210']);
        $password = $user->password;
        $this->actingAs($admin)->get(route('web.users.show', $user))->assertOk()->assertSee($user->name);
        $this->get(route('web.users.edit', $user))->assertOk()->assertSee($user->email);
        $this->put(route('web.users.update', $user), [
            'name' => 'Updated Worker', 'email' => $user->email, 'mobile' => $user->mobile,
            'status' => 'active', 'password' => '', 'role' => 'super_admin',
        ])->assertRedirect(route('web.users.show', $user));
        $this->assertSame('Updated Worker', $user->fresh()->name);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame('worker', $user->fresh()->role);
    }

    public function test_workers_cannot_access_user_management(): void
    {
        $worker = User::factory()->create(['role' => 'worker', 'status' => 'active']);
        $this->actingAs($worker)->get(route('web.users.show', $worker))->assertForbidden();
        $this->get(route('web.users.edit', $worker))->assertForbidden();
        $this->put(route('web.users.update', $worker), [])->assertForbidden();
    }

    public function test_invalid_updates_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'worker', 'status' => 'active']);
        $this->actingAs($admin)->put(route('web.users.update', $user), [
            'name' => 'Worker', 'email' => $admin->email, 'mobile' => 'bad',
            'status' => 'invalid', 'password' => 'newpassword', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'mobile', 'status', 'password']);
        $this->assertSame($user->name, $user->fresh()->name);
    }
}
