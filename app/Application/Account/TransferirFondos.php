<?php

namespace App\Application\Account;

use App\Application\Account\Especificaciones\AlcanceDeCuentas;
use App\Application\Account\Especificaciones\CuentasExistentes;
use App\Application\Account\Especificaciones\MismaCuenta;
use App\Application\Account\Especificaciones\MonedaCoincidente;
use App\Application\Account\Especificaciones\SaldoSuficiente;
use App\Domain\Account\RepositorioCuentas;

final class TransferirFondos
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

    public function ejecutar(TransferirFondosDTO $command): ResultadoOperacion
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

            $context = new ContextoValidacionTransferencia($command, $this->accounts, $this->scope);
            $failure = (new CadenaValidacionTransferencia([
                new MismaCuenta,
                new CuentasExistentes,
                new AlcanceDeCuentas,
                new MonedaCoincidente,
                new SaldoSuficiente,
            ]))->validar($context);
            if ($failure !== null) {
                return ResultadoOperacion::rejected($failure);
            }

            $source = $context->cuentaOrigen();
            $destination = $context->cuentaDestino();

            $source->aplicarSaldo('-'.$command->amount);
            $destination->aplicarSaldo($command->amount);
            $this->accounts->guardar($source);
            $this->accounts->guardar($destination);
            $transactionId = $this->operations->crear([
                'kind' => 'transfer', 'currency' => $command->currency, 'actor_id' => $command->actorId,
                'source_account_id' => $command->sourceAccountId, 'destination_account_id' => $command->destinationAccountId,
            ]);
            $this->ledger->guardarDosLineas($transactionId, [
                ['account_id' => $command->sourceAccountId, 'amount' => '-'.$command->amount, 'currency' => $command->currency, 'actor_id' => $command->actorId],
                ['account_id' => $command->destinationAccountId, 'amount' => $command->amount, 'currency' => $command->currency, 'actor_id' => $command->actorId],
            ]);
            $this->idempotency->guardar($command->requestKey, $fingerprint, $transactionId);

            return ResultadoOperacion::committed($transactionId);
        });
    }
}
