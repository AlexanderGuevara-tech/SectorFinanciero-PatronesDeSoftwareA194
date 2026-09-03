<?php

namespace App\Application\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\CuentaProducto;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\FabricaDeCuentas;
use App\Domain\Account\Moneda;
use App\Domain\Account\RepositorioCuentas;

final class AbrirCuenta
{
    public function __construct(
        private FabricaDeCuentas $fabrica,
        private RepositorioCuentas $repositorio,
    ) {}

    public function ejecutar(string $tipo, int $userId): Cuenta
    {
        $producto = $this->fabrica->crear($tipo);

        if ($producto === null) {
            throw new \InvalidArgumentException("Unknown account type: {$tipo}");
        }

        $cuenta = $this->nuevaCuenta($tipo, $userId, $producto);

        $this->repositorio->guardar($cuenta);

        return $cuenta;
    }

    private function nuevaCuenta(string $tipo, int $userId, CuentaProducto $producto): Cuenta
    {
        return new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: $tipo,
            userId: $userId,
            producto: $producto,
        );
    }
}
