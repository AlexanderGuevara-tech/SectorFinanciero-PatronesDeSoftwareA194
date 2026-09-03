<?php

namespace App\Infrastructure\Persistence;

use Database\Factories\CuentaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['saldo', 'moneda', 'estado', 'tipo', 'user_id'])]
class Cuenta extends Model
{
    /** @use HasFactory<CuentaFactory> */
    use HasFactory;

    protected $table = 'accounts';
}
