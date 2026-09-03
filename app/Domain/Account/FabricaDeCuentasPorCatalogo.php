<?php

namespace App\Domain\Account;

final class FabricaDeCuentasPorCatalogo implements FabricaDeCuentas
{
    public function __construct(
        private CatalogoTiposCuenta $catalogo,
    ) {}

    public function crear(string $tipo): ?CuentaProducto
    {
        $definicion = $this->catalogo->buscar($tipo);

        if ($definicion === null) {
            return null;
        }

        return match ($definicion->politicaSobregiro) {
            'not_allowed' => new CuentaAhorro,
            'allowed' => new CuentaCorriente,
            default => null,
        };
    }
}
