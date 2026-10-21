<?php

namespace App\Http\Controllers;

use App\Application\Account\PoliticaValidacionExterna;
use App\Application\Account\TipoFalloOperacion;
use App\Application\Account\TransferirFondosDTO;
use App\Infrastructure\Account\KycSimulado;
use App\Infrastructure\Account\LimiteSimulado;
use App\Infrastructure\Account\RiesgoSimulado;
use App\Models\User;
use Illuminate\View\View;

class ControladorDemoAdapters extends Controller
{
    public function __invoke(): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        /** @var list<array{nombre: string, actor: int, origen: int, monto: string}> $perfiles */
        $perfiles = [
            ['nombre' => 'Aprobada', 'actor' => 100, 'origen' => 200, 'monto' => '250.00'],
            ['nombre' => 'Rechazo KYC', 'actor' => 9001, 'origen' => 200, 'monto' => '250.00'],
            ['nombre' => 'Riesgo detectado', 'actor' => 100, 'origen' => 9002, 'monto' => '250.00'],
            ['nombre' => 'Límite excedido', 'actor' => 100, 'origen' => 200, 'monto' => '1500.00'],
        ];

        $escenarios = [];
        foreach ($perfiles as $perfil) {
            $comando = new TransferirFondosDTO(
                actorId: $perfil['actor'],
                sourceAccountId: $perfil['origen'],
                destinationAccountId: 300,
                currency: 'COP',
                amount: $perfil['monto'],
                requestKey: 'demo-only',
            );
            $kyc = new KycSimulado([9001]);
            $riesgo = new RiesgoSimulado([9002]);
            $limite = new LimiteSimulado(['COP' => '1000.00']);
            $fallo = (new PoliticaValidacionExterna($kyc, $riesgo, $limite))->validar($comando);

            $escenarios[] = [
                'nombre' => $perfil['nombre'],
                'monto' => $perfil['monto'],
                'kyc' => $kyc->aprobado($comando),
                'riesgo' => $riesgo->seguro($comando),
                'limite' => $limite->permitido($comando),
                'resultado' => match ($fallo) {
                    null => 'Validaciones aprobadas',
                    TipoFalloOperacion::KycRejected => 'Rechazo KYC',
                    TipoFalloOperacion::FraudSuspected => 'Riesgo detectado',
                    TipoFalloOperacion::TransferLimitExceeded => 'Límite excedido',
                    default => 'Validación rechazada',
                },
            ];
        }

        return view('adapters.demo', [
            'usuario' => $usuario->load('roles'),
            'navegacion' => [
                [
                    'identificador' => 'panel',
                    'etiqueta' => 'Panel',
                    'descripcion' => 'Resumen de CORE',
                    'estado' => 'activo',
                    'nombreRuta' => 'dashboard',
                    'permiso' => null,
                ],
                [
                    'identificador' => 'cuentas', 'etiqueta' => 'Cuentas', 'descripcion' => 'Gestión de cuentas bancarias',
                    'estado' => 'activo', 'nombreRuta' => 'accounts.index', 'permiso' => 'view-accounts',
                ],
                [
                    'identificador' => 'usuarios', 'etiqueta' => 'Usuarios', 'descripcion' => 'Gestión de usuarios del sistema',
                    'estado' => 'activo', 'nombreRuta' => 'admin.users.index', 'permiso' => 'manage-users',
                ],
                [
                    'identificador' => 'roles', 'etiqueta' => 'Roles', 'descripcion' => 'Gestión de roles y permisos',
                    'estado' => 'activo', 'nombreRuta' => 'admin.roles.index', 'permiso' => 'manage-users',
                ],
                [
                    'identificador' => 'clientes', 'etiqueta' => 'Clientes', 'descripcion' => 'Gestión de clientes bancarios',
                    'estado' => 'activo', 'nombreRuta' => 'admin.clientes.index', 'permiso' => 'manage-customers',
                ],
                [
                    'identificador' => 'adapters',
                    'etiqueta' => 'Demo de adapters',
                    'descripcion' => 'Validaciones simuladas de transferencias',
                    'estado' => 'activo',
                    'nombreRuta' => 'admin.adapters.demo',
                    'permiso' => 'manage-accounts',
                ],
            ],
            'escenarios' => $escenarios,
        ]);
    }
}
