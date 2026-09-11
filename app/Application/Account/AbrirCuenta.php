<?php

namespace App\Application\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\CuentaBuilder;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Domain\Account\PaqueteCuenta;
use App\Domain\Account\RepositorioCuentas;
use Illuminate\Support\Facades\Gate;

final class AbrirCuenta
{
    public function __construct(
        private FabricaPaquetesCuentas $fabrica,
        private RepositorioCuentas $repositorio,
    ) {}

    public function ejecutar(string $tipo, int $customerId, int $operadoPorId, string $familia = 'personal'): Cuenta
    {
        if (auth()->check()) {
            Gate::authorize('manage-accounts');
        }

        if ($customerId < 1) {
            throw new \InvalidArgumentException('A customer is required.');
        }

        $paquete = $this->fabrica->crear($familia, $tipo);
        $cuenta = $this->nuevaCuenta($tipo, $customerId, $operadoPorId, $paquete);

        $this->repositorio->guardar($cuenta);

        return $cuenta;
    }

    private function nuevaCuenta(string $tipo, int $customerId, int $operadoPorId, PaqueteCuenta $paquete): Cuenta
    {
        return (new CuentaBuilder)
            ->withType($tipo)
            ->forCustomer($customerId)
            ->operatedBy($operadoPorId)
            ->withProduct($paquete->cuenta)
            ->withFamily($paquete->familia)
            ->withPackage($paquete)
            ->build();
    }
}
