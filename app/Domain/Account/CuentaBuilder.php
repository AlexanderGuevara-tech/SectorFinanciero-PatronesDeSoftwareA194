<?php

namespace App\Domain\Account;

use App\Domain\Customer\Cliente;

final class CuentaBuilder
{
    private ?string $balance = '0';

    private ?Moneda $currency = null;

    private ?EstadoCuenta $status = EstadoCuenta::Activa;

    private ?string $type = null;

    private ?int $customerId = null;

    private ?int $operatorId = null;

    private ?CuentaProducto $product = null;

    private string $family = 'personal';

    private ?PaqueteCuenta $package = null;

    private ?int $id = null;

    private ?Cliente $customer = null;

    public function withBalance(string $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function withCurrency(Moneda $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function withStatus(EstadoCuenta $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function withType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function forCustomer(int $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function operatedBy(?int $operatorId): self
    {
        $this->operatorId = $operatorId;

        return $this;
    }

    public function withProduct(CuentaProducto $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function withFamily(string $family): self
    {
        $this->family = $family;

        return $this;
    }

    public function withPackage(?PaqueteCuenta $package): self
    {
        $this->package = $package;

        return $this;
    }

    public function withId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function withCustomer(?Cliente $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function build(): Cuenta
    {
        if ($this->type === null || trim($this->type) === '') {
            throw new \InvalidArgumentException('An account type is required.');
        }

        if ($this->customerId === null) {
            throw new \InvalidArgumentException('A customer is required.');
        }

        if ($this->product === null) {
            throw new \InvalidArgumentException('An account product is required.');
        }

        return new Cuenta(
            saldo: $this->balance ?? '0',
            moneda: $this->currency ?? Moneda::default(),
            estado: $this->status ?? EstadoCuenta::Activa,
            tipo: $this->type,
            customerId: $this->customerId,
            operadoPorId: $this->operatorId,
            producto: $this->product,
            familia: $this->family,
            paquete: $this->package,
            id: $this->id,
            cliente: $this->customer,
        );
    }
}
