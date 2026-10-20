<?php

namespace App\Application\Loan;

use App\Application\Account\AlcanceClientes;
use App\Application\Account\PoliticaAutorizacion;
use App\Application\Account\RepositorioIdempotencia;
use App\Application\Account\RepositorioLedger;
use App\Application\Account\RepositorioOperaciones;
use App\Application\Account\UnidadDeTrabajo;
use App\Domain\Account\RepositorioCuentas;
use App\Domain\Loan\CatalogoProductosPrestamo;
use App\Domain\Loan\Prestamo;
use App\Domain\Loan\RepositorioPrestamos;

final class ServicioPrestamos
{
    public function __construct(
        private CatalogoProductosPrestamo $catalog,
        private RepositorioPrestamos $loans,
        private RepositorioCuentas $accounts,
        private PoliticaAutorizacion $authorization,
        private AlcanceClientes $scope,
        private UnidadDeTrabajo $unit,
        private RepositorioOperaciones $operations,
        private RepositorioLedger $ledger,
        private RepositorioIdempotencia $idempotency,
    ) {}

    public function solicitar(int $applicantId, int $borrowerId, int $destinationAccountId, string $productId, string $principal, string $requestKey): Prestamo
    {
        if (! preg_match('/^\d{1,16}\.\d{2}$/', $principal) || bccomp($principal, '0.00', 2) <= 0
            || $requestKey === '' || strlen($requestKey) > 191 || str_starts_with($requestKey, 'loan-disbursement:')) {
            throw new \InvalidArgumentException('A positive principal and valid application key are required.');
        }

        return $this->unit->ejecutar(function () use ($applicantId, $borrowerId, $destinationAccountId, $productId, $principal, $requestKey): Prestamo {
            $this->authorize($applicantId);
            $existing = $this->loans->porClave($requestKey);
            if ($existing !== null) {
                if ($existing->applicantId !== $applicantId || $existing->borrowerId !== $borrowerId
                    || $existing->destinationAccountId !== $destinationAccountId || $existing->producto->id !== $productId
                    || $existing->principal !== bcadd($principal, '0', 2)) {
                    throw new \InvalidArgumentException('Application key belongs to a different application.');
                }

                return $existing;
            }
            $product = $this->catalog->buscar($productId);
            $destination = $this->accounts->porIdYCliente($destinationAccountId, $borrowerId);
            if ($product === null || $destination === null || $destination->moneda()->codigo() !== $product->currency
                || ! $destination->estado()->permiteEscritura() || ! $this->scope->contiene($applicantId, $borrowerId)) {
                throw new \InvalidArgumentException('Product or borrower destination is ineligible.');
            }

            return $this->loans->solicitar($borrowerId, $destinationAccountId, $applicantId, $product, bcadd($principal, '0', 2), $requestKey);
        });
    }

    public function evaluar(int $loanId, int $officerId, bool $approve): Prestamo
    {
        return $this->unit->ejecutar(function () use ($loanId, $officerId, $approve): Prestamo {
            $this->authorize($officerId);
            $loan = $this->loans->porIdBloqueado($loanId);
            if ($loan === null || $loan->status !== 'pending' || $loan->applicantId === $officerId) {
                throw new \RuntimeException('Application cannot be evaluated by this operator.');
            }
            if ($approve && $this->accounts->porIdYCliente($loan->destinationAccountId, $loan->borrowerId) === null) {
                throw new \RuntimeException('Borrower no longer owns the destination.');
            }
            $this->loans->actualizarEstado($loanId, $approve ? 'approved' : 'rejected', $officerId);

            return $this->loans->porIdBloqueado($loanId);
        });
    }

    public function desembolsar(int $loanId, int $officerId): Prestamo
    {
        return $this->unit->ejecutar(function () use ($loanId, $officerId): Prestamo {
            $this->authorize($officerId);
            $loan = $this->loans->porIdBloqueado($loanId);
            if ($loan === null || $loan->approvedBy !== $officerId || $loan->applicantId === $officerId
                || ! in_array($loan->status, ['approved', 'disbursed'], true) || $loan->requestKey === '') {
                throw new \RuntimeException('Only the approving operator can disburse an approved application.');
            }
            $fundingId = config('loans.funding_account_id');
            if (! is_numeric($fundingId) || (int) $fundingId < 1 || (string) (int) $fundingId !== (string) $fundingId
                || (int) $fundingId === $loan->destinationAccountId) {
                throw new \RuntimeException('An existing distinct CORE funding account must be configured.');
            }
            $fundingId = (int) $fundingId;
            $key = 'loan-disbursement:'.$loan->id;
            $fingerprint = hash('sha256', implode('|', [
                $key, $loan->requestKey, $loan->borrowerId, $loan->destinationAccountId,
                $loan->principal, $loan->producto->currency, $fundingId, $officerId,
            ]));
            $existing = $this->idempotency->buscar($key);
            if ($existing !== null) {
                if ($existing['payload_fingerprint'] !== $fingerprint || $loan->status !== 'disbursed'
                    || $loan->transactionId !== $existing['transaction_id']) {
                    throw new \RuntimeException('Disbursement idempotency conflict.');
                }

                return $loan;
            }
            if ($loan->status !== 'approved' || $loan->transactionId !== null) {
                throw new \RuntimeException('Loan is already disbursed or cannot be posted.');
            }
            $locked = $this->accounts->porIdsBloqueadas([$fundingId, $loan->destinationAccountId]);
            $byId = [];
            foreach ($locked as $account) {
                $byId[$account->id()] = $account;
            }
            $source = $byId[$fundingId] ?? null;
            $destination = $byId[$loan->destinationAccountId] ?? null;
            if ($source === null || $destination === null || ! $source->esInterna()
                || $source->customerId() === $loan->borrowerId
                || $destination->customerId() !== $loan->borrowerId
                || $source->moneda()->codigo() !== $loan->producto->currency
                || $destination->moneda()->codigo() !== $loan->producto->currency
                || ! $source->estado()->permiteEscritura() || ! $destination->estado()->permiteEscritura()
                || bccomp($source->saldo(), $loan->principal, 2) < 0) {
                throw new \RuntimeException('Funding or borrower account is ineligible or unfunded.');
            }
            $source->aplicarSaldo('-'.$loan->principal);
            $destination->aplicarSaldo($loan->principal);
            $this->accounts->guardar($source);
            $this->accounts->guardar($destination);
            $transactionId = $this->operations->crear([
                'kind' => 'loan_disbursement', 'currency' => $loan->producto->currency,
                'actor_id' => $officerId, 'source_account_id' => $fundingId,
                'destination_account_id' => $loan->destinationAccountId,
            ]);
            $this->ledger->guardarDosLineas($transactionId, [
                ['account_id' => $fundingId, 'amount' => '-'.$loan->principal, 'currency' => $loan->producto->currency, 'actor_id' => $officerId],
                ['account_id' => $loan->destinationAccountId, 'amount' => $loan->principal, 'currency' => $loan->producto->currency, 'actor_id' => $officerId],
            ]);
            $this->idempotency->guardar($key, $fingerprint, $transactionId);
            $this->loans->actualizarEstado($loanId, 'disbursed', $officerId, $transactionId);

            return $this->loans->porIdBloqueado($loanId);
        });
    }

    private function authorize(int $actorId): void
    {
        $this->authorization->autorizar($actorId);
    }
}
