<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_see_customers_from_their_own_company(): void
    {
        $gujarat = Company::create([
            'code' => 'TEST-GJ',
            'name' => 'Test Gujarat Company',
            'gstin' => '24TESTGJ1234N1Z8',
            'state' => 'Gujarat',
            'invoice_prefix' => 'TGJ',
            'is_active' => true,
        ]);

        $delhi = Company::create([
            'code' => 'TEST-DL',
            'name' => 'Test Delhi Company',
            'gstin' => '07TESTDL1234N1Z2',
            'state' => 'Delhi',
            'invoice_prefix' => 'TDL',
            'is_active' => true,
        ]);

        $adminRole = Role::create(['name' => 'Admin']);

        $user = User::create([
            'name' => 'Gujarat Test Admin',
            'email' => 'test.admin.gj@example.com',
            'password' => 'password',
            'company_id' => $gujarat->id,
        ]);

        $user->roles()->attach($adminRole);

        Customer::create([
            'company_id' => $gujarat->id,
            'code' => 'GJ-CUST-01',
            'name' => 'Gujarat Test Customer',
            'state' => 'Gujarat',
            'credit_limit' => 50000,
            'payment_terms_days' => 30,
            'is_active' => true,
        ]);

        Customer::create([
            'company_id' => $delhi->id,
            'code' => 'DL-CUST-01',
            'name' => 'Delhi Secret Customer',
            'state' => 'Delhi',
            'credit_limit' => 50000,
            'payment_terms_days' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Gujarat Test Customer');
        $response->assertDontSee('Delhi Secret Customer');
    }
}
