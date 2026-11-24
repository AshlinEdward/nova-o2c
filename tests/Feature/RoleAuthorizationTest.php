<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_required_role_cannot_access_customers(): void
    {
        $company = Company::create([
            'code' => 'ROLE-TEST',
            'name' => 'Role Test Company',
            'gstin' => '24ROLE1234N1Z8',
            'state' => 'Gujarat',
            'invoice_prefix' => 'RLT',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Unauthorized User',
            'email' => 'unauthorized@example.com',
            'password' => 'password',
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertForbidden();
    }
}
