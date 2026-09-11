<?php

namespace App\Application\Account;

interface RepositorioIdempotencia
{
    /**
     * @return array{request_key: string, payload_fingerprint: string, transaction_id: int}|null
     */
    public function buscar(string $requestKey): ?array;

    public function conflicto(string $requestKey, string $payloadFingerprint): bool;

    public function guardar(string $requestKey, string $payloadFingerprint, int $transactionId): void;
}
