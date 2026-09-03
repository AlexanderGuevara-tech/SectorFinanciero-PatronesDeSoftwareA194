<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeticionUsuarioActualizar;
use App\Http\Requests\PeticionUsuarioCrear;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ControladorUsuarios extends Controller
{
    public function index(): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();
        $usuarios = User::with('roles')->paginate(15);
        $roles = Role::all();

        return view('admin.usuarios.index', [
            'navegacion' => $this->navegacionBasica(),
            'usuario' => $usuario->load('roles'),
            'usuarios' => $usuarios,
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return view('admin.usuarios.create', [
            'navegacion' => $this->navegacionBasica(),
            'usuario' => $usuario->load('roles'),
            'roles' => Role::all(),
        ]);
    }

    public function store(PeticionUsuarioCrear $request): RedirectResponse
    {
        $usuario = User::create($request->validated());

        if ($request->has('roles')) {
            $usuario->roles()->sync($request->validated('roles'));
        }

        return redirect()->route('admin.users.index')->with('exito', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return view('admin.usuarios.edit', [
            'navegacion' => $this->navegacionBasica(),
            'usuario' => $usuario->load('roles'),
            'usuarioEditar' => $user->load('roles'),
            'roles' => Role::all(),
        ]);
    }

    public function update(PeticionUsuarioActualizar $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = $data['password'];
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->roles()->sync($request->validated('roles', []));

        return redirect()->route('admin.users.index')->with('exito', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            abort(403);
        }

        DB::transaction(function () use ($user): void {
            $user->roles()->detach();
            $user->delete();
        });

        return redirect()->route('admin.users.index')->with('exito', 'Usuario eliminado correctamente.');
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
