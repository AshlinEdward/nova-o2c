<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create the five roles
        $roleNames = [
            'Admin',
            'Sales Executive',
            'Sales Manager',
            'Warehouse',
            'Accounts',
        ];

        foreach ($roleNames as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Retrieve companies
        $gujarat = Company::where('code', 'NOVA-GJ')->firstOrFail();
        $delhi = Company::where('code', 'NOVA-DL')->firstOrFail();

        // Demo users
        $users = [
            [
                'name' => 'Gujarat Admin',
                'email' => 'admin.gj@nova.test',
                'company_id' => $gujarat->id,
                'roles' => ['Admin'],
            ],
            [
                'name' => 'Gujarat Sales Executive',
                'email' => 'sales.gj@nova.test',
                'company_id' => $gujarat->id,
                'roles' => ['Sales Executive'],
            ],
            [
                'name' => 'Gujarat Sales Executive 2',
                'email' => 'sales2.gj@nova.test',
                'company_id' => $gujarat->id,
                'roles' => ['Sales Executive'],
            ],
            [
                'name' => 'Gujarat Sales Manager',
                'email' => 'salesmgr.gj@nova.test',
                'company_id' => $gujarat->id,
                'roles' => ['Sales Manager'],
            ],
            [
                'name' => 'Gujarat Warehouse User',
                'email' => 'wh.gj@nova.test',
                'company_id' => $gujarat->id,
                'roles' => ['Warehouse'],
            ],
            [
                'name' => 'Gujarat Accounts User',
                'email' => 'accounts.gj@nova.test',
                'company_id' => $gujarat->id,
                'roles' => ['Accounts'],
            ],
            [
                'name' => 'Delhi Admin',
                'email' => 'admin.dl@nova.test',
                'company_id' => $delhi->id,
                'roles' => ['Admin'],
            ],
            [
                'name' => 'Delhi Sales Executive',
                'email' => 'sales.dl@nova.test',
                'company_id' => $delhi->id,
                'roles' => ['Sales Executive'],
            ],
            [
                'name' => 'Delhi Warehouse User',
                'email' => 'wh.dl@nova.test',
                'company_id' => $delhi->id,
                'roles' => ['Warehouse'],
            ],
        ];

        foreach ($users as $userData) {
            $roleNames = $userData['roles'];

            unset($userData['roles']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'company_id' => $userData['company_id'],
                    'password' => Hash::make('password'),
                ]
            );

            $roleIds = Role::whereIn('name', $roleNames)
                ->pluck('id');

            $user->roles()->sync($roleIds);
        }
    }
}
