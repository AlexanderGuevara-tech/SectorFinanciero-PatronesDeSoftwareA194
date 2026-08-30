<?php

namespace App\Domain\Account;

final class CatalogoTiposCuentaEstatico implements CatalogoTiposCuenta
{
    /**
     * @var array<string, DefinicionTipoCuenta>
     */
    private array $definitions;

    public function __construct()
    {
        $this->definitions = [
            'savings' => new DefinicionTipoCuenta(
                identificador: 'savings',
                nombreVisible: 'Savings Account',
                monedasElegibles: ['COP', 'USD'],
                politicaSobregiro: 'not_allowed',
            ),
            'checking' => new DefinicionTipoCuenta(
                identificador: 'checking',
                nombreVisible: 'Checking Account',
                monedasElegibles: ['COP', 'USD'],
                politicaSobregiro: 'allowed',
            ),
        ];
    }

    /**
     * @return list<DefinicionTipoCuenta>
     */
    public function listar(): array
    {
        return array_values($this->definitions);
    }

    public function buscar(string $identificador): ?DefinicionTipoCuenta
    {
        return $this->definitions[$identificador] ?? null;
    }
}
