<?php

namespace App\Infrastructure\Persistence;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'doc_type', 'doc_number', 'email', 'phone'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'customers';

    protected static function newFactory(): ClienteFactory
    {
        return ClienteFactory::new();
    }
}
