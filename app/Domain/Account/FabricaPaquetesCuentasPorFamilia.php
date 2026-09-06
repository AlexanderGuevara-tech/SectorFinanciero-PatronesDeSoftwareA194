<?php

namespace App\Domain\Account;

final class FabricaPaquetesCuentasPorFamilia implements FabricaPaquetesCuentas
{
    /**
     * @param  iterable<FabricaFamiliaCuenta>  $fabricas
     */
    public function __construct(iterable $fabricas)
    {
        foreach ($fabricas as $fabrica) {
            $this->fabricas[$fabrica->familia()] = $fabrica;
        }
    }

    /** @var array<string, FabricaFamiliaCuenta> */
    private array $fabricas = [];

    public function crear(string $familia, string $tipo): PaqueteCuenta
    {
        $fabrica = $this->fabricas[$familia] ?? null;

        if ($fabrica === null) {
            throw new \InvalidArgumentException("Unknown account family: {$familia}");
        }

        return $fabrica->crear($tipo);
    }
}
