@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-6xl space-y-8">
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Integraciones / Adapter</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Validación externa de transferencias</h1>
            <p class="mt-3 max-w-3xl text-slate-600 dark:text-slate-400">Conocé el papel de cada puerto antes de conectar un proveedor. Los ejemplos usan identificadores ficticios, no cuentas reales.</p>
        </header>

        <section aria-label="Alcance de la simulación" class="rounded-2xl border border-amber-300 bg-amber-50 p-6 text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
            <p class="text-lg font-bold">Simulación local; sin llamadas a proveedores reales</p>
            <p class="mt-2">No se ejecuta ninguna transferencia. No se consultan cuentas ni se modifican saldos, transacciones o asientos contables. Los resultados solo representan validaciones de ejemplo, no autorizaciones bancarias.</p>
        </section>

        <section aria-labelledby="ports-title">
            <h2 id="ports-title" class="text-xl font-bold">Roles de los puertos</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="font-semibold">KYC</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Confirma si el actor cumple la validación de identidad simulada.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="font-semibold">Riesgo</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Marca cuentas de origen ficticias con riesgo simulado.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="font-semibold">Límite de transferencia</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Compara el monto ficticio con un máximo local de COP 1000.00, inclusive.</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="scenarios-title">
            <h2 id="scenarios-title" class="text-xl font-bold">Escenarios locales</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">KYC rechaza el actor ficticio 9001; riesgo marca el origen ficticio 9002. La política evalúa KYC, luego riesgo y por último el límite. Ningún dato de la solicitud modifica estos perfiles.</p>
            <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left">Perfil</th>
                            <th scope="col" class="px-5 py-3 text-left">Monto COP</th>
                            <th scope="col" class="px-5 py-3 text-left">KYC</th>
                            <th scope="col" class="px-5 py-3 text-left">Riesgo</th>
                            <th scope="col" class="px-5 py-3 text-left">Límite</th>
                            <th scope="col" class="px-5 py-3 text-left">Resultado de validación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($escenarios as $escenario)
                            <tr>
                                <th scope="row" class="px-5 py-4 text-left font-semibold">{{ $escenario['nombre'] }}</th>
                                <td class="px-5 py-4">{{ $escenario['monto'] }}</td>
                                <td class="px-5 py-4">{{ $escenario['kyc'] ? 'Aprobado' : 'Rechazado' }}</td>
                                <td class="px-5 py-4">{{ $escenario['riesgo'] ? 'Seguro' : 'Marcado' }}</td>
                                <td class="px-5 py-4">{{ $escenario['limite'] ? 'Permitido' : 'Excedido' }}</td>
                                <td class="px-5 py-4 font-semibold">{{ $escenario['resultado'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
