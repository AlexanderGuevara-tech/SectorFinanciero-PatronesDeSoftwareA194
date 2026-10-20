<?php

namespace App\Infrastructure\Persistence;

use App\Models\User;
use Database\Factories\CuentaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['saldo', 'moneda', 'estado', 'tipo', 'familia', 'customer_id', 'operado_por'])]
class Cuenta extends Model
{
    /** @use HasFactory<CuentaFactory> */
    use HasFactory;

    protected $table = 'accounts';

    protected function casts(): array
    {
        return ['es_interna' => 'boolean'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'customer_id');
    }

    public function operadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operado_por');
    }
}
