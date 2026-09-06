<?php

namespace App\Domain\Customer;

final class Cliente
{
    public function __construct(
        private string $nombre,
        private string $tipoDocumento,
        private string $numeroDocumento,
        private ?string $email = null,
        private ?string $telefono = null,
        private ?int $id = null,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function tipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function numeroDocumento(): string
    {
        return $this->numeroDocumento;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function telefono(): ?string
    {
        return $this->telefono;
    }

    public function asignarId(int $id): void
    {
        $this->id = $id;
    }
}
