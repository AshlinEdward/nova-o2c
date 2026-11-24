<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_customer_under_own_company(): void
    {
        $company = Company::create([
            'code' => 'CREATE-TEST',
            'name' => 'Customer Creation Company',
            'gstin' => '24CREAT1234N1Z8',
            'state' => 'Gujarat',
            'invoice_prefix' => 'CRT',
            'is_active' => true,
        ]);

        $role = Role::create(['name' => 'Admin']);

        $user = User::create([
            'name' => 'Customer Admin',
            'email' => 'customer.admin@example.com',
            'password' => 'password',
            'company_id' => $company->id,
        ]);

        $user->roles()->attach($role);

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'code' => 'NEW-CUST-01',
            'name' => 'New Test Customer',
            'gstin' => '24NEWCU1234N1Z8',
            'state' => 'Gujarat',
            'credit_limit' => 75000,
            'payment_terms_days' => 30,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'company_id' => $company->id,
            'code' => 'NEW-CUST-01',
            'name' => 'New Test Customer',
        ]);
    }
}
