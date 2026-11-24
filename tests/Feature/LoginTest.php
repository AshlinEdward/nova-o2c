<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser(): User
    {
        $company = Company::create([
            'code' => 'LOGIN-TEST',
            'name' => 'Login Test Company',
            'gstin' => '24LOGIN1234N1Z8',
            'state' => 'Gujarat',
            'invoice_prefix' => 'LGT',
            'is_active' => true,
        ]);

        return User::create([
            'name' => 'Login Test User',
            'email' => 'login.test@example.com',
            'password' => 'password',
            'company_id' => $company->id,
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->createTestUser();

        $response = $this->post('/login', [
            'email' => 'login.test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $this->createTestUser();

        $response = $this->from('/login')->post('/login', [
            'email' => 'login.test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
