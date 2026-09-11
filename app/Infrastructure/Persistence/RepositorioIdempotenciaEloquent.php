<?php

namespace App\Infrastructure\Persistence;

use App\Application\Account\RepositorioIdempotencia;
use Illuminate\Support\Facades\DB;

final class RepositorioIdempotenciaEloquent implements RepositorioIdempotencia
{
    public function buscar(string $requestKey): ?array
    {
        $registro = DB::table('idempotency_keys')->where('request_key', $requestKey)->first();

        return $registro === null ? null : [
            'request_key' => $registro->request_key,
            'payload_fingerprint' => $registro->payload_fingerprint,
            'transaction_id' => (int) $registro->transaction_id,
        ];
    }

    public function conflicto(string $requestKey, string $payloadFingerprint): bool
    {
        $registro = $this->buscar($requestKey);

        return $registro !== null && $registro['payload_fingerprint'] !== $payloadFingerprint;
    }

    public function guardar(string $requestKey, string $payloadFingerprint, int $transactionId): void
    {
        DB::table('idempotency_keys')->insert([
            'request_key' => $requestKey,
            'payload_fingerprint' => $payloadFingerprint,
            'transaction_id' => $transactionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
