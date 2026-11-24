<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_creation_rejects_missing_required_fields(): void
    {
        $company = Company::create([
            'code' => 'VALID-TEST',
            'name' => 'Validation Test Company',
            'gstin' => '24VALID1234N1Z8',
            'state' => 'Gujarat',
            'invoice_prefix' => 'VLD',
            'is_active' => true,
        ]);

        $role = Role::create(['name' => 'Admin']);

        $user = User::create([
            'name' => 'Validation Admin',
            'email' => 'validation.admin@example.com',
            'password' => 'password',
            'company_id' => $company->id,
        ]);

        $user->roles()->attach($role);

        $response = $this->actingAs($user)
            ->post(route('customers.store'), []);

        $response->assertSessionHasErrors([
            'code',
            'name',
            'state',
            'credit_limit',
            'payment_terms_days',
            'is_active',
        ]);

        $this->assertDatabaseCount('customers', 0);
    }
}
