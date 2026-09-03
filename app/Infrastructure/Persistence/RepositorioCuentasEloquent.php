<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Account\Cuenta as CuentaDominio;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\CuentaCorriente;
use App\Domain\Account\CuentaProducto;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\Moneda;
use App\Domain\Account\RepositorioCuentas;

final class RepositorioCuentasEloquent implements RepositorioCuentas
{
    public function guardar(CuentaDominio $cuenta): void
    {
        $modelo = ($cuenta->id() === null)
            ? new Cuenta
            : Cuenta::query()->findOrFail($cuenta->id());

        $modelo->saldo = $cuenta->saldo();
        $modelo->moneda = $cuenta->moneda()->codigo();
        $modelo->estado = $cuenta->estado()->value;
        $modelo->tipo = $cuenta->tipo();
        $modelo->user_id = $cuenta->userId();

        $modelo->save();

        if ($cuenta->id() === null) {
            $cuenta->asignarId($modelo->id);
        }
    }

    public function porId(int $id): ?CuentaDominio
    {
        $modelo = Cuenta::query()->find($id);

        return $modelo === null ? null : $this->mapear($modelo);
    }

    public function porUsuario(int $userId): array
    {
        return $this->mapearMuchos(Cuenta::query()->where('user_id', $userId)->get());
    }

    public function todos(): array
    {
        return $this->mapearMuchos(Cuenta::query()->get());
    }

    public function porIdYPropietario(int $id, int $userId): ?CuentaDominio
    {
        $modelo = Cuenta::query()->where('id', $id)->where('user_id', $userId)->first();

        return $modelo === null ? null : $this->mapear($modelo);
    }

    /**
     * @param  iterable<Cuenta>  $modelos
     * @return list<CuentaDominio>
     */
    private function mapearMuchos(iterable $modelos): array
    {
        $resultado = [];
        foreach ($modelos as $modelo) {
            $resultado[] = $this->mapear($modelo);
        }

        return $resultado;
    }

    private function mapear(Cuenta $modelo): CuentaDominio
    {
        return new CuentaDominio(
            saldo: $this->normalizarSaldo($modelo->saldo),
            moneda: new Moneda($modelo->moneda),
            estado: EstadoCuenta::from($modelo->estado),
            tipo: $modelo->tipo,
            userId: (int) $modelo->user_id,
            producto: $this->producto($modelo->tipo),
            id: (int) $modelo->id,
        );
    }

    private function producto(string $tipo): CuentaProducto
    {
        return $tipo === 'checking' ? new CuentaCorriente : new CuentaAhorro;
    }

    /**
     * Normalize a raw DECIMAL value from the database reader into an exact
     * 2-decimal string. SQLite returns int/float for numeric columns; the
     * value is converted to a string before any money handling, so money is
     * never stored or carried as a float.
     */
    private function normalizarSaldo(string|int|float $saldo): string
    {
        return bcadd((string) $saldo, '0', 2);
    }
}
