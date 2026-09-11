<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Account\Cuenta as CuentaDominio;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Domain\Account\Moneda;
use App\Domain\Account\RepositorioCuentas;
use App\Domain\Customer\Cliente as ClienteDominio;

final class RepositorioCuentasEloquent implements RepositorioCuentas
{
    public function __construct(private FabricaPaquetesCuentas $fabricaPaquetes) {}

    public function guardar(CuentaDominio $cuenta): void
    {
        $modelo = ($cuenta->id() === null)
            ? new Cuenta
            : Cuenta::query()->findOrFail($cuenta->id());

        $modelo->saldo = $cuenta->saldo();
        $modelo->moneda = $cuenta->moneda()->codigo();
        $modelo->estado = $cuenta->estado()->value;
        $modelo->tipo = $cuenta->tipo();
        $modelo->familia = $cuenta->familia();
        $modelo->customer_id = $cuenta->customerId();
        $modelo->operado_por = $cuenta->operadoPorId();

        $modelo->save();

        if ($cuenta->id() === null) {
            $cuenta->asignarId($modelo->id);
        }
    }

    public function porId(int $id): ?CuentaDominio
    {
        $modelo = Cuenta::query()->with('cliente')->find($id);

        return $modelo === null ? null : $this->mapear($modelo);
    }

    public function porCliente(int $customerId): array
    {
        return $this->mapearMuchos(Cuenta::query()->where('customer_id', $customerId)->with('cliente')->get());
    }

    public function todos(): array
    {
        return $this->mapearMuchos(Cuenta::query()->with('cliente')->get());
    }

    public function porIdYCliente(int $id, int $customerId): ?CuentaDominio
    {
        $modelo = Cuenta::query()->where('id', $id)->where('customer_id', $customerId)->with('cliente')->first();

        return $modelo === null ? null : $this->mapear($modelo);
    }

    public function porIdsBloqueadas(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);

        return $this->mapearMuchos(
            Cuenta::query()
                ->whereIn('id', $ids)
                ->with('cliente')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
        );
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
            customerId: (int) $modelo->customer_id,
            operadoPorId: $modelo->operado_por === null ? null : (int) $modelo->operado_por,
            producto: ($paquete = $this->fabricaPaquetes->crear($modelo->familia ?? 'personal', $modelo->tipo))->cuenta,
            familia: $paquete->familia,
            paquete: $paquete,
            id: (int) $modelo->id,
            cliente: $modelo->relationLoaded('cliente') && $modelo->cliente !== null
                ? new ClienteDominio($modelo->cliente->name, $modelo->cliente->doc_type, $modelo->cliente->doc_number, $modelo->cliente->email, $modelo->cliente->phone, (int) $modelo->cliente->id)
                : null,
        );
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
