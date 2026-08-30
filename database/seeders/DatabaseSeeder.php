<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $administrator = Role::create(['name' => 'administrator', 'description' => 'Administra el sistema.']);
        $customer = Role::create(['name' => 'customer', 'description' => 'Cliente bancario.']);

        $manageUsers = Permission::create(['name' => 'manage-users', 'description' => 'Gestionar usuarios y roles.']);
        $viewAccounts = Permission::create(['name' => 'view-accounts', 'description' => 'Consultar cuentas y movimientos.']);

        $administrator->permissions()->sync([$manageUsers->id, $viewAccounts->id]);
        $customer->permissions()->sync([$viewAccounts->id]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::query()->where('email', 'test@example.com')->firstOrFail()->roles()->attach($administrator);
    }
}
