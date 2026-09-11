<?php

namespace App\Application\Account;

use App\Domain\Account\RepositorioCuentas;

final class ReversarTransferencia
{
    public function __construct(
        private UnidadDeTrabajo $unit,
        private RepositorioCuentas $accounts,
        private PoliticaAutorizacion $authorization,
        private AlcanceClientes $scope,
        private RepositorioOperaciones $operations,
        private RepositorioLedger $ledger,
        private RepositorioIdempotencia $idempotency,
    ) {}

    public function ejecutar(ReversarTransferenciaDTO $command): ResultadoOperacion
    {
        return $this->unit->ejecutar(function () use ($command): ResultadoOperacion {
            $fingerprint = $command->fingerprint();
            $existing = $this->idempotency->buscar($command->requestKey);
            if ($existing !== null) {
                return $existing['payload_fingerprint'] === $fingerprint
                    ? ResultadoOperacion::committed($existing['transaction_id'])
                    : ResultadoOperacion::rejected(TipoFalloOperacion::IdempotencyConflict);
            }
            try {
                $this->authorization->autorizar($command->actorId);
            } catch (\Throwable) {
                return ResultadoOperacion::rejected(TipoFalloOperacion::Unauthorized);
            }
            $original = $this->operations->porId($command->transactionId);
            if ($original === null) {
                return ResultadoOperacion::rejected(TipoFalloOperacion::OperationNotFound);
            }
            if ($this->operations->tieneReversion($command->transactionId)) {
                return ResultadoOperacion::rejected(TipoFalloOperacion::AlreadyReversed);
            }
            $lines = $this->ledger->porTransaccion($command->transactionId);
            if (count($lines) !== 2) {
                return ResultadoOperacion::rejected(TipoFalloOperacion::InvalidOperation);
            }
            $ids = array_column($lines, 'account_id');
            $accounts = $this->accounts->porIdsBloqueadas($ids);
            if (count($accounts) !== 2) {
                return ResultadoOperacion::rejected(TipoFalloOperacion::AccountNotFound);
            }
            foreach ($accounts as $account) {
                if (! $this->scope->contiene($command->actorId, $account->customerId())) {
                    return ResultadoOperacion::rejected(TipoFalloOperacion::OutOfScope);
                }
            }
            $transactionId = $this->operations->crear([
                'kind' => 'reversal', 'currency' => $original['currency'], 'actor_id' => $command->actorId,
                'source_account_id' => $original['source_account_id'], 'destination_account_id' => $original['destination_account_id'],
                'reversal_of_transaction_id' => $command->transactionId,
            ]);
            foreach ($accounts as $account) {
                $delta = collect($lines)->firstWhere('account_id', $account->id())['amount'];
                $account->aplicarSaldo(bcsub('0.00', $delta, 2));
                $this->accounts->guardar($account);
            }
            $this->ledger->guardarDosLineas($transactionId, array_map(static fn (array $line): array => [
                'account_id' => $line['account_id'], 'amount' => bcsub('0.00', $line['amount'], 2), 'currency' => $line['currency'], 'actor_id' => $command->actorId,
            ], $lines));
            $this->idempotency->guardar($command->requestKey, $fingerprint, $transactionId);

            return ResultadoOperacion::committed($transactionId);
        });
    }
}
