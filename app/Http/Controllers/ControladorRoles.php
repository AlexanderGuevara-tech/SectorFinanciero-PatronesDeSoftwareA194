<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeticionRolActualizar;
use App\Http\Requests\PeticionRolCrear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ControladorRoles extends Controller
{
    public function index(): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();
        $roles = Role::with('permissions')->withCount('users')->get();

        return view('admin.roles.index', [
            'navegacion' => $this->navegacionBasica(),
            'usuario' => $usuario->load('roles'),
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return view('admin.roles.create', [
            'navegacion' => $this->navegacionBasica(),
            'usuario' => $usuario->load('roles'),
            'permisos' => Permission::all(),
        ]);
    }

    public function store(PeticionRolCrear $request): RedirectResponse
    {
        $rol = Role::create($request->validated());
        $rol->permissions()->sync($request->validated('permissions', []));

        return redirect()->route('admin.roles.index')->with('exito', 'Rol creado correctamente.');
    }

    public function edit(Role $role): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return view('admin.roles.edit', [
            'navegacion' => $this->navegacionBasica(),
            'usuario' => $usuario->load('roles'),
            'rol' => $role->load('permissions'),
            'permisos' => Permission::all(),
        ]);
    }

    public function update(PeticionRolActualizar $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());
        $role->permissions()->sync($request->validated('permissions', []));

        return redirect()->route('admin.roles.index')->with('exito', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'administrator') {
            abort(403);
        }

        $adminHoldsRole = $this->rolOtorgaManageUsersAlAdmin($role);

        if ($adminHoldsRole) {
            abort(403);
        }

        DB::transaction(function () use ($role): void {
            $role->permissions()->detach();
            $role->delete();
        });

        return redirect()->route('admin.roles.index')->with('exito', 'Rol eliminado correctamente.');
    }

    /**
     * The administrator-role guard: a role that provides the current admin's
     * `manage-users` door cannot be deleted, because that would lock the admin
     * out of the panel. This protects the USER↔ROLE seam.
     */
    private function rolOtorgaManageUsersAlAdmin(Role $role): bool
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        if ($usuario->roles()->where('roles.id', $role->id)->doesntExist()) {
            return false;
        }

        return $role->permissions()->where('name', 'manage-users')->exists();
    }

    /**
     * @return list<array{identificador: string, etiqueta: string, descripcion: string, estado: 'activo'|'planificado', nombreRuta: ?string, permiso: ?string}>
     */
    private function navegacionBasica(): array
    {
        return [
            [
                'identificador' => 'panel',
                'etiqueta' => 'Panel',
                'descripcion' => 'Resumen de CORE',
                'estado' => 'activo',
                'nombreRuta' => 'dashboard',
                'permiso' => null,
            ],
            [
                'identificador' => 'usuarios',
                'etiqueta' => 'Usuarios',
                'descripcion' => 'Gestión de usuarios del sistema',
                'estado' => 'activo',
                'nombreRuta' => 'admin.users.index',
                'permiso' => 'manage-users',
            ],
            [
                'identificador' => 'roles',
                'etiqueta' => 'Roles',
                'descripcion' => 'Gestión de roles y permisos',
                'estado' => 'activo',
                'nombreRuta' => 'admin.roles.index',
                'permiso' => 'manage-users',
            ],
        ];
    }
}
