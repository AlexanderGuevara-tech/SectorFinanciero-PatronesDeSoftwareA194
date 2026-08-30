<?php

namespace App\Domain\Account;

interface CatalogoTiposCuenta
{
    /**
     * @return list<DefinicionTipoCuenta>
     */
    public function listar(): array;

    public function buscar(string $identificador): ?DefinicionTipoCuenta;
}
