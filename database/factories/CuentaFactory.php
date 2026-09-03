<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Cuenta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuenta>
 */
class CuentaFactory extends Factory
{
    protected $model = Cuenta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'saldo' => '0',
            'moneda' => 'COP',
            'estado' => 'activa',
            'tipo' => 'savings',
            'user_id' => User::factory(),
        ];
    }
}
