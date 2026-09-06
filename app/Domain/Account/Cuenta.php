<?php

namespace App\Domain\Account;

use App\Domain\Customer\Cliente;

final class Cuenta
{
    public function __construct(
        private string $saldo,
        private Moneda $moneda,
        private EstadoCuenta $estado,
        private string $tipo,
        private int $customerId,
        private ?int $operadoPorId,
        private CuentaProducto $producto,
        private string $familia = 'personal',
        private ?PaqueteCuenta $paquete = null,
        private ?int $id = null,
        private ?Cliente $cliente = null,
    ) {
        if ($this->paquete === null) {
            $this->paquete = new PaqueteCuenta(
                familia: $this->familia,
                cuenta: $this->producto,
                comision: new PoliticaComisionPorFamilia($this->familia),
                sobregiro: new PoliticaSobregiroPorFamilia($this->familia),
            );
        } else {
            if ($this->paquete->familia !== $this->familia || $this->paquete->cuenta !== $this->producto) {
                throw new \InvalidArgumentException('Account package does not match account family or product.');
            }
        }
    }

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

    public function customerId(): int
    {
        return $this->customerId;
    }

    public function operadoPorId(): ?int
    {
        return $this->operadoPorId;
    }

    public function producto(): CuentaProducto
    {
        return $this->producto;
    }

    public function familia(): string
    {
        return $this->familia;
    }

    public function paquete(): PaqueteCuenta
    {
        return $this->paquete;
    }

    public function cliente(): ?Cliente
    {
        return $this->cliente;
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
