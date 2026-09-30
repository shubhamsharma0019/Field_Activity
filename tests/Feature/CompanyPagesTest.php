<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_edit_and_update_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $company = Company::create(['name' => 'Original Company', 'company_code' => 'TEST001', 'status' => 'active']);
        $this->actingAs($admin)->get(route('web.companies.show', $company))->assertOk()->assertSee('Original Company');
        $this->get(route('web.companies.edit', $company))->assertOk()->assertSee('Original Company');
        $this->put(route('web.companies.update', $company), [
            'name' => 'Updated Company', 'email' => 'company@example.com', 'mobile' => '9876543210',
            'contact_person' => 'Manager', 'address' => 'Office 1', 'city' => 'Noida', 'state' => 'Uttar Pradesh',
            'status' => 'inactive', 'company_code' => 'CHANGED',
        ])->assertRedirect(route('web.companies.show', $company));
        $this->assertSame('Updated Company', $company->fresh()->name);
        $this->assertSame('inactive', $company->fresh()->status);
        $this->assertSame('TEST001', $company->fresh()->company_code);
    }

    public function test_non_admin_cannot_manage_company(): void
    {
        $worker = User::factory()->create(['role' => 'worker', 'status' => 'active']);
        $company = Company::create(['name' => 'Company', 'company_code' => 'TEST002']);
        $this->actingAs($worker)->get(route('web.companies.show', $company))->assertForbidden();
        $this->get(route('web.companies.edit', $company))->assertForbidden();
        $this->put(route('web.companies.update', $company), [])->assertForbidden();
    }

    public function test_invalid_update_does_not_change_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $company = Company::create(['name' => 'Original', 'company_code' => 'TEST003']);
        $this->actingAs($admin)->put(route('web.companies.update', $company), [
            'name' => '', 'email' => 'bad', 'status' => 'invalid',
        ])->assertSessionHasErrors(['name', 'email', 'status']);
        $this->assertSame('Original', $company->fresh()->name);
    }
}
