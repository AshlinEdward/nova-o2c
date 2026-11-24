<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_edit_another_company_customer(): void
    {
        $gujarat = Company::create([
            'code' => 'EDIT-GJ',
            'name' => 'Gujarat Company',
            'gstin' => '24EDITGJ1234N1Z8',
            'state' => 'Gujarat',
            'invoice_prefix' => 'EGJ',
            'is_active' => true,
        ]);

        $delhi = Company::create([
            'code' => 'EDIT-DL',
            'name' => 'Delhi Company',
            'gstin' => '07EDITDL1234N1Z2',
            'state' => 'Delhi',
            'invoice_prefix' => 'EDL',
            'is_active' => true,
        ]);

        $role = Role::create(['name' => 'Admin']);

        $user = User::create([
            'name' => 'Gujarat Admin',
            'email' => 'edit.admin@example.com',
            'password' => 'password',
            'company_id' => $gujarat->id,
        ]);

        $user->roles()->attach($role);

        $customer = Customer::create([
            'company_id' => $delhi->id,
            'code' => 'DL-PRIVATE-01',
            'name' => 'Private Delhi Customer',
            'state' => 'Delhi',
            'credit_limit' => 50000,
            'payment_terms_days' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('customers.edit', $customer->id));

        $response->assertNotFound();
    }
}
