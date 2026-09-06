<?php

namespace App\Domain\Account;

final class FabricaPaqueteEmpresarial implements FabricaFamiliaCuenta
{
    public function __construct(private FabricaDeCuentas $fabricaDeCuentas) {}

    public function familia(): string
    {
        return 'empresarial';
    }

    public function crear(string $tipo): PaqueteCuenta
    {
        $cuenta = $this->fabricaDeCuentas->crear($tipo);

        if ($cuenta === null) {
            throw new \InvalidArgumentException("Unknown account type: {$tipo}");
        }

        $cuenta = $cuenta->conFamilia($this->familia());

        return new PaqueteCuenta(
            familia: $this->familia(),
            cuenta: $cuenta,
            comision: new PoliticaComisionPorFamilia($this->familia()),
            sobregiro: new PoliticaSobregiroPorFamilia($this->familia()),
        );
    }
}
