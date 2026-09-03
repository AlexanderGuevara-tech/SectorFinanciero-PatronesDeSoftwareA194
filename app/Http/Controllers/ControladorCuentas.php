<?php

namespace App\Http\Controllers;

use App\Application\Account\AbrirCuenta;
use App\Application\Account\BloquearCuenta;
use App\Application\Account\ConsultarSaldo;
use App\Application\Account\DesbloquearCuenta;
use App\Application\Account\ListarCuentas;
use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\DefinicionTipoCuenta;
use App\Http\Requests\PeticionAbrirCuenta;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ControladorCuentas extends Controller
{
    public function __construct(
        private ListarCuentas $listarCuentas,
        private AbrirCuenta $abrirCuenta,
        private ConsultarSaldo $consultarSaldo,
        private BloquearCuenta $bloquearCuenta,
        private DesbloquearCuenta $desbloquearCuenta,
    ) {}

    public function index(CatalogoTiposCuenta $catalogo): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();
        $esAdministrador = $usuario->hasRole('administrator');
        $cuentas = $this->listarCuentas->ejecutar(userId: $usuario->id, esAdministrador: $esAdministrador);

        /** @var list<array{identificador: string, etiqueta: string, monedasElegibles: list<string>, politicaSobregiro: string}> $tiposCuenta */
        $tiposCuenta = array_map(
            fn (DefinicionTipoCuenta $definicion): array => [
                'identificador' => $definicion->identificador,
                'etiqueta' => match ($definicion->identificador) {
                    'savings' => 'Cuenta de ahorros',
                    'checking' => 'Cuenta corriente',
                    default => $definicion->nombreVisible,
                },
                'monedasElegibles' => $definicion->monedasElegibles,
                'politicaSobregiro' => match ($definicion->politicaSobregiro) {
                    'not_allowed' => 'Sobregiro no permitido',
                    'allowed' => 'Sobregiro permitido',
                    default => $definicion->politicaSobregiro,
                },
            ],
            $catalogo->listar(),
        );

        $primeraResolucion = app(CatalogoTiposCuenta::class);
        $segundaResolucion = app(CatalogoTiposCuenta::class);

        /** @var array{contrato: string, implementacion: string, mismaInstancia: bool} $evidenciaSingleton */
        $evidenciaSingleton = [
            'contrato' => CatalogoTiposCuenta::class,
            'implementacion' => $primeraResolucion::class,
            'mismaInstancia' => $primeraResolucion === $segundaResolucion,
        ];

        /** @var list<array{identificador: string, etiqueta: string, descripcion: string, estado: 'activo'|'planificado', nombreRuta: ?string, permiso: ?string}> $navegacion */
        $navegacion = [
            [
                'identificador' => 'panel',
                'etiqueta' => 'Panel',
                'descripcion' => 'Resumen de CORE',
                'estado' => 'activo',
                'nombreRuta' => 'dashboard',
                'permiso' => null,
            ],
            [
                'identificador' => 'cuentas',
                'etiqueta' => 'Cuentas',
                'descripcion' => 'Gestión de cuentas bancarias',
                'estado' => 'activo',
                'nombreRuta' => 'accounts.index',
                'permiso' => 'view-accounts',
            ],
        ];

        return view('accounts.index', [
            'navegacion' => $navegacion,
            'cuentas' => $cuentas,
            'tiposCuenta' => $tiposCuenta,
            'evidenciaSingleton' => $evidenciaSingleton,
            'usuario' => $usuario->load('roles'),
            'puedeCrearCuenta' => $usuario->can('manage-accounts'),
        ]);
    }

    public function store(PeticionAbrirCuenta $request): RedirectResponse
    {
        $cuenta = $this->abrirCuenta->ejecutar(
            tipo: $request->validated('tipo'),
            userId: auth()->id(),
        );

        return redirect()->route('accounts.show', $cuenta->id())->with('exito', 'Cuenta abierta correctamente.');
    }

    public function show(int $account): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();
        $esAdministrador = $usuario->hasRole('administrator');

        $cuenta = $this->consultarSaldo->ejecutar(
            cuentaId: $account,
            userId: $usuario->id,
            esAdministrador: $esAdministrador,
        );

        return view('accounts.show', [
            'cuentaId' => $account,
            'saldo' => $cuenta['saldo'],
            'moneda' => $cuenta['moneda'],
            'usuario' => $usuario->load('roles'),
            'navegacion' => $this->navegacionBasica(),
        ]);
    }

    public function block(int $account): RedirectResponse
    {
        $this->bloquearCuenta->ejecutar(
            cuentaId: $account,
            userId: auth()->id(),
        );

        return redirect()->route('accounts.show', $account)->with('exito', 'Cuenta bloqueada correctamente.');
    }

    public function unblock(int $account): RedirectResponse
    {
        $this->desbloquearCuenta->ejecutar(
            cuentaId: $account,
            userId: auth()->id(),
        );

        return redirect()->route('accounts.show', $account)->with('exito', 'Cuenta desbloqueada correctamente.');
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
                'identificador' => 'cuentas',
                'etiqueta' => 'Cuentas',
                'descripcion' => 'Gestión de cuentas bancarias',
                'estado' => 'activo',
                'nombreRuta' => 'accounts.index',
                'permiso' => 'view-accounts',
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
