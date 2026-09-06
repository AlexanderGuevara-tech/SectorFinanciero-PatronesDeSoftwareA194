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
            ['identificador' => 'clientes', 'etiqueta' => 'Clientes', 'descripcion' => 'Gestión de clientes bancarios', 'estado' => 'activo', 'nombreRuta' => 'admin.clientes.index', 'permiso' => 'manage-customers'],
        ];
    }
}
