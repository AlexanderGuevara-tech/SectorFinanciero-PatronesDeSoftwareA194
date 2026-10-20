<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Loan\CatalogoProductosPrestamo;
use App\Domain\Loan\DefinicionProductoPrestamo;
use App\Domain\Loan\Prestamo;
use App\Domain\Loan\RepositorioPrestamos;
use Illuminate\Support\Facades\DB;

final class RepositorioPrestamosEloquent implements RepositorioPrestamos
{
    public function __construct(private CatalogoProductosPrestamo $catalog) {}

    public function porClave(string $requestKey): ?Prestamo
    {
        $record = DB::table('loans')->where('request_key', $requestKey)->first();

        return $record === null ? null : $this->hydrate($record);
    }

    public function porIdBloqueado(int $id): ?Prestamo
    {
        $record = DB::table('loans')->where('id', $id)->lockForUpdate()->first();

        return $record === null ? null : $this->hydrate($record);
    }

    public function solicitar(int $borrowerId, int $destinationAccountId, int $applicantId, DefinicionProductoPrestamo $product, string $principal, string $requestKey): Prestamo
    {
        $id = (int) DB::table('loans')->insertGetId([
            'borrower_id' => $borrowerId, 'destination_account_id' => $destinationAccountId,
            'applicant_id' => $applicantId, 'product_id' => $product->id,
            'product_name' => $product->name, 'currency' => $product->currency,
            'annual_rate' => $product->annualRate, 'term_months' => $product->termMonths,
            'principal' => $principal, 'request_key' => $requestKey, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return new Prestamo($id, $borrowerId, $destinationAccountId, $applicantId, $product, $principal, $requestKey, 'pending', null, null);
    }

    public function actualizarEstado(int $id, string $status, ?int $approvedBy = null, ?int $transactionId = null): void
    {
        DB::table('loans')->where('id', $id)->update([
            'status' => $status, 'approved_by' => $approvedBy,
            'transaction_id' => $transactionId, 'updated_at' => now(),
        ]);
    }

    private function hydrate(object $record): Prestamo
    {
        $product = $this->catalog->buscar($record->product_id);
        // The historical agreement must not acquire new terms if the catalog is reconfigured.
        if ($product === null || $product->name !== $record->product_name
            || $product->currency !== $record->currency
            || $product->annualRate !== bcadd((string) $record->annual_rate, '0', 4)
            || $product->termMonths !== (int) $record->term_months) {
            $product = new DefinicionProductoPrestamo(
                $record->product_id, $record->product_name, $record->currency,
                bcadd((string) $record->annual_rate, '0', 4), (int) $record->term_months,
            );
        }

        return new Prestamo(
            (int) $record->id, (int) $record->borrower_id, (int) $record->destination_account_id,
            (int) $record->applicant_id, $product, bcadd((string) $record->principal, '0', 2),
            $record->request_key, $record->status,
            $record->approved_by === null ? null : (int) $record->approved_by,
            $record->transaction_id === null ? null : (int) $record->transaction_id,
        );
    }
}
