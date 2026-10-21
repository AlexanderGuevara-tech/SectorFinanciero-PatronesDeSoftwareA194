<?php

namespace App\Http\Controllers;

use App\Domain\Customer\Cliente;
use App\Domain\Customer\RepositorioClientes;
use App\Http\Requests\PeticionClienteCrear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControladorClientes extends Controller
{
    public function __construct(private RepositorioClientes $repositorio) {}

    public function index(Request $request): View
    {
        $documento = $request->string('documento')->toString();
        $clientes = $documento === ''
            ? $this->repositorio->todos()
            : array_values(array_filter(
                $this->repositorio->todos(),
                fn ($cliente): bool => str_contains($cliente->numeroDocumento(), $documento)
                    || str_contains(strtolower($cliente->nombre()), strtolower($documento)),
            ));

        return view('clientes.index', [
            'clientes' => $clientes,
            'documento' => $documento,
            'usuario' => $request->user()->load('roles'),
            'navegacion' => $this->navegacion(),
        ]);
    }

    public function store(PeticionClienteCrear $request): RedirectResponse
    {
        $cliente = new Cliente(
            nombre: $request->validated('name'),
            tipoDocumento: $request->validated('doc_type'),
            numeroDocumento: $request->validated('doc_number'),
            email: $request->validated('email'),
            telefono: $request->validated('phone'),
        );
        $this->repositorio->guardar($cliente);

        return redirect()->route('admin.clientes.index')->with('exito', 'Cliente creado correctamente.');
    }

    private function navegacion(): array
    {
        return [
            ['identificador' => 'panel', 'etiqueta' => 'Panel', 'descripcion' => 'Resumen de CORE', 'estado' => 'activo', 'nombreRuta' => 'dashboard', 'permiso' => null],
            ['identificador' => 'cuentas', 'etiqueta' => 'Cuentas', 'descripcion' => 'Gestión de cuentas bancarias', 'estado' => 'activo', 'nombreRuta' => 'accounts.index', 'permiso' => 'view-accounts'],
            ['identificador' => 'usuarios', 'etiqueta' => 'Usuarios', 'descripcion' => 'Gestión de usuarios del sistema', 'estado' => 'activo', 'nombreRuta' => 'admin.users.index', 'permiso' => 'manage-users'],
            ['identificador' => 'roles', 'etiqueta' => 'Roles', 'descripcion' => 'Gestión de roles y permisos', 'estado' => 'activo', 'nombreRuta' => 'admin.roles.index', 'permiso' => 'manage-users'],
            ['identificador' => 'clientes', 'etiqueta' => 'Clientes', 'descripcion' => 'Gestión de clientes bancarios', 'estado' => 'activo', 'nombreRuta' => 'admin.clientes.index', 'permiso' => 'manage-customers'],
            ['identificador' => 'adapters', 'etiqueta' => 'Adapter demo', 'descripcion' => 'Validaciones simuladas de transferencias', 'estado' => 'activo', 'nombreRuta' => 'admin.adapters.demo', 'permiso' => 'manage-accounts'],
        ];
    }
}
