<?php

namespace App\Http\Controllers;

use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\DefinicionTipoCuenta;
use App\Models\User;
use Illuminate\View\View;

class ControladorCuentas extends Controller
{
    public function index(CatalogoTiposCuenta $catalogo): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();
        $primeraResolucion = app(CatalogoTiposCuenta::class);
        $segundaResolucion = app(CatalogoTiposCuenta::class);

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
                'descripcion' => 'Vista de cuentas de solo lectura',
                'estado' => 'activo',
                'nombreRuta' => 'accounts.index',
                'permiso' => 'view-accounts',
            ],
        ];

        return view('accounts.index', [
            'navegacion' => $navegacion,
            'tiposCuenta' => $tiposCuenta,
            'evidenciaSingleton' => $evidenciaSingleton,
            'usuario' => $usuario->load('roles'),
        ]);
    }
}
