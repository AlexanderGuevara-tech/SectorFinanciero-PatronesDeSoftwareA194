<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    private function ejecutarSeeder(): void
    {
        (new DatabaseSeeder)->run();
    }

    public function test_ejecutar_seed_dos_veces_no_causa_error(): void
    {
        $this->ejecutarSeeder();
        $this->ejecutarSeeder();

        $this->assertTrue(true, 'db:seed does not crash on re-run');
    }

    public function test_roles_no_se_duplican_al_reseedear(): void
    {
        $this->ejecutarSeeder();
        $rolesCountFirst = Role::count();
        $this->ejecutarSeeder();
        $rolesCountSecond = Role::count();

        $this->assertSame($rolesCountFirst, $rolesCountSecond, 'Role count must not grow on re-seed');
        $this->assertDatabaseHas('roles', ['name' => 'administrator']);
        $this->assertDatabaseHas('roles', ['name' => 'customer']);
    }

    public function test_permisos_no_se_duplican_al_reseedear(): void
    {
        $this->ejecutarSeeder();
        $permsCountFirst = Permission::count();
        $this->ejecutarSeeder();
        $permsCountSecond = Permission::count();

        $this->assertSame($permsCountFirst, $permsCountSecond, 'Permission count must not grow on re-seed');
        $this->assertDatabaseHas('permissions', ['name' => 'manage-users']);
        $this->assertDatabaseHas('permissions', ['name' => 'view-accounts']);
        $this->assertDatabaseHas('permissions', ['name' => 'manage-accounts']);
    }

    public function test_usuario_test_conserva_un_solo_rol_administrator(): void
    {
        $this->ejecutarSeeder();
        $this->ejecutarSeeder();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $adminRole = Role::where('name', 'administrator')->firstOrFail();
        $pivotCount = $user->roles()->where('roles.id', $adminRole->id)->count();

        $this->assertSame(1, $pivotCount, 'test@example.com must have exactly one administrator role pivot row');
    }

    public function test_administrator_mantiene_permisos_despues_del_reseed(): void
    {
        $this->ejecutarSeeder();
        $this->ejecutarSeeder();

        $adminRole = Role::where('name', 'administrator')->firstOrFail();
        $permissionNames = $adminRole->permissions->pluck('name')->sort()->values();

        $this->assertCount(3, $adminRole->permissions, 'administrator role must have exactly 3 permissions');
        $this->assertEqualsCanonicalizing(
            ['manage-users', 'manage-accounts', 'view-accounts'],
            $permissionNames->toArray(),
            'administrator must retain all three permissions'
        );
    }

    public function test_pivots_role_user_no_tienen_filas_duplicadas(): void
    {
        $this->ejecutarSeeder();
        $this->ejecutarSeeder();

        $totalPivotRows = \DB::table('role_user')->count();
        $uniquePairs = \DB::table('role_user')
            ->select('user_id', 'role_id')
            ->distinct()
            ->count();

        $this->assertSame(
            $uniquePairs,
            $totalPivotRows,
            'role_user must have no duplicate (user_id, role_id) pairs'
        );
    }

    public function test_pivots_permission_role_no_tienen_filas_duplicadas(): void
    {
        $this->ejecutarSeeder();
        $this->ejecutarSeeder();

        $totalPivotRows = \DB::table('permission_role')->count();
        $uniquePairs = \DB::table('permission_role')
            ->select('role_id', 'permission_id')
            ->distinct()
            ->count();

        $this->assertSame(
            $uniquePairs,
            $totalPivotRows,
            'permission_role must have no duplicate (role_id, permission_id) pairs'
        );
    }
}
