<?php

namespace App\Http\Controllers;

use App\Application\Loan\ConsultaPrestamo;
use App\Models\User;
use Illuminate\View\View;

class ControladorPrestamos extends Controller
{
    public function __construct(private ConsultaPrestamo $consultaPrestamo) {}

    public function show(int $loan): View
    {
        /** @var User $usuario */
        $usuario = auth()->user();
        $prestamo = $this->consultaPrestamo->porId($usuario, $loan);

        if ($prestamo === null) {
            abort(404);
        }

        return view('prestamos.show', [
            'prestamo' => $prestamo,
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
            ],
        ]);
    }
}
