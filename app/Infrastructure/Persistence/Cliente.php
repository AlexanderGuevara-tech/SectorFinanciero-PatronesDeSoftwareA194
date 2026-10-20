<?php

namespace App\Infrastructure\Persistence;

use App\Models\User;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'doc_type', 'doc_number', 'email', 'phone'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'customers';

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'customer_id');
    }

    protected static function newFactory(): ClienteFactory
    {
        return ClienteFactory::new();
    }
}
