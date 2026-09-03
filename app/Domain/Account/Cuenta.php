<?php

namespace App\Domain\Account;

final class Cuenta
{
    public function __construct(
        private string $saldo,
        private Moneda $moneda,
        private EstadoCuenta $estado,
        private string $tipo,
        private int $userId,
        private CuentaProducto $producto,
        private ?int $id = null,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function saldo(): string
    {
        return $this->saldo;
    }

    public function moneda(): Moneda
    {
        return $this->moneda;
    }

    public function estado(): EstadoCuenta
    {
        return $this->estado;
    }

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function producto(): CuentaProducto
    {
        return $this->producto;
    }

    /**
     * @throws \InvalidArgumentException if the account's estado does not permit mutations.
     */
    public function aplicarSaldo(string $delta): void
    {
        if (! $this->estado->permiteEscritura()) {
            throw new \InvalidArgumentException(
                "Account in estado '{$this->estado->value}' does not allow saldo mutations."
            );
        }

        $this->saldo = $this->producto->aplicaSaldo($this->saldo, $delta);
    }

    /**
     * @throws \InvalidArgumentException if the account is not in activa estado.
     */
    public function bloquear(): void
    {
        if ($this->estado !== EstadoCuenta::Activa) {
            throw new \InvalidArgumentException(
                "Only activa accounts can be blocked; current estado is '{$this->estado->value}'."
            );
        }

        $this->estado = EstadoCuenta::Bloqueada;
    }

    /**
     * @throws \InvalidArgumentException if the account is not in bloqueada estado.
     */
    public function desbloquear(): void
    {
        if ($this->estado !== EstadoCuenta::Bloqueada) {
            throw new \InvalidArgumentException(
                "Only bloqueada accounts can be unblocked; current estado is '{$this->estado->value}'."
            );
        }

        $this->estado = EstadoCuenta::Activa;
    }

    public function asignarId(int $id): void
    {
        $this->id = $id;
    }
}
