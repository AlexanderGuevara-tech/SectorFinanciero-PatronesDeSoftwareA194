<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ControladorPanel extends Controller
{
    public function __invoke(): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();

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

        /** @var list<array{identificador: string, etiqueta: string, descripcion: string, estado: 'activo'|'planificado', nombreRuta: ?string, permiso: ?string}> $modulos */
        $modulos = [
            [
                'identificador' => 'cliente',
                'etiqueta' => 'Cliente',
                'descripcion' => 'Las capacidades de clientes están planificadas para un módulo futuro.',
                'estado' => 'planificado',
                'nombreRuta' => null,
                'permiso' => null,
            ],
            [
                'identificador' => 'cuentas',
                'etiqueta' => 'Cuentas',
                'descripcion' => 'Vista de cuentas de solo lectura disponible. Las operaciones y la persistencia siguen fuera de alcance.',
                'estado' => 'activo',
                'nombreRuta' => 'accounts.index',
                'permiso' => 'view-accounts',
            ],
            [
                'identificador' => 'transacciones',
                'etiqueta' => 'Transacciones',
                'descripcion' => 'Los flujos de transacciones están planificados para un módulo futuro.',
                'estado' => 'planificado',
                'nombreRuta' => null,
                'permiso' => null,
            ],
            [
                'identificador' => 'prestamos',
                'etiqueta' => 'Préstamos',
                'descripcion' => 'Los flujos de préstamos están planificados para un módulo futuro.',
                'estado' => 'planificado',
                'nombreRuta' => null,
                'permiso' => null,
            ],
            [
                'identificador' => 'inversiones',
                'etiqueta' => 'Inversiones',
                'descripcion' => 'Los flujos de inversiones están planificados para un módulo futuro.',
                'estado' => 'planificado',
                'nombreRuta' => null,
                'permiso' => null,
            ],
            [
                'identificador' => 'fraude',
                'etiqueta' => 'Fraude',
                'descripcion' => 'Las capacidades de fraude están planificadas para un módulo futuro.',
                'estado' => 'planificado',
                'nombreRuta' => null,
                'permiso' => null,
            ],
            [
                'identificador' => 'cumplimiento',
                'etiqueta' => 'Cumplimiento / KYC-AML',
                'descripcion' => 'Las capacidades de cumplimiento están planificadas para un módulo futuro.',
                'estado' => 'planificado',
                'nombreRuta' => null,
                'permiso' => null,
            ],
        ];

        return view('dashboard', [
            'navegacion' => $navegacion,
            'modulos' => $modulos,
            'usuario' => $usuario->load('roles'),
        ]);
    }
}
