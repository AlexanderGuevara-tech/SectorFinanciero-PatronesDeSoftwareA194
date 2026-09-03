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
        $administrator = Role::firstOrCreate(
            ['name' => 'administrator'],
            ['description' => 'Administra el sistema.']
        );
        $customer = Role::firstOrCreate(
            ['name' => 'customer'],
            ['description' => 'Cliente bancario.']
        );

        $manageUsers = Permission::firstOrCreate(
            ['name' => 'manage-users'],
            ['description' => 'Gestionar usuarios y roles.']
        );
        $viewAccounts = Permission::firstOrCreate(
            ['name' => 'view-accounts'],
            ['description' => 'Consultar cuentas y movimientos.']
        );
        $manageAccounts = Permission::firstOrCreate(
            ['name' => 'manage-accounts'],
            ['description' => 'Crear, bloquear y desbloquear cuentas.']
        );

        $administrator->permissions()->sync([$manageUsers->id, $viewAccounts->id, $manageAccounts->id]);
        $customer->permissions()->sync([$viewAccounts->id]);

        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password']
        );

        $testUser->roles()->syncWithoutDetaching([$administrator->id]);
    }
}
