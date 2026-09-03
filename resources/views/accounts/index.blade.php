@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-7xl">
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Módulo de cuentas</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Cuentas</h1>
            <p class="mt-3 max-w-3xl text-slate-600 dark:text-slate-400">Gestiona las cuentas bancarias registradas en el sistema.</p>
        </header>

        {{-- Empty state --}}
        @if (empty($cuentas))
            <section aria-labelledby="empty-state-heading" class="mt-10 rounded-2xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900 dark:bg-amber-950/30">
                <h2 id="empty-state-heading" class="text-xl font-bold">No hay cuentas registradas todavía</h2>
                <p class="mt-3 max-w-3xl leading-7 text-amber-950 dark:text-amber-100">Crea una cuenta bancaria para comenzar a operar.</p>
            </section>
        @else
            {{-- Accounts list --}}
            <section aria-labelledby="accounts-heading" class="mt-10">
                <h2 id="accounts-heading" class="sr-only">Cuentas registradas</h2>
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($cuentas as $cuenta)
                        <a href="{{ route('accounts.show', $cuenta->id()) }}" class="block rounded-2xl border border-slate-200 bg-white p-6 transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:shadow-slate-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">{{ $cuenta->tipo() === 'savings' ? 'Cuenta de ahorros' : 'Cuenta corriente' }}</h3>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cuenta->estado()->value === 'activa' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                    {{ ucfirst($cuenta->estado()->value) }}
                                </span>
                            </div>
                            <dl class="mt-4 grid gap-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500 dark:text-slate-400">Saldo</dt>
                                    <dd class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ $cuenta->moneda()->codigo() }} {{ $cuenta->saldo() }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500 dark:text-slate-400">ID</dt>
                                    <dd class="font-mono text-slate-900 dark:text-slate-100">#{{ $cuenta->id() }}</dd>
                                </div>
                            </dl>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Open account form (admin only) --}}
        @if ($puedeCrearCuenta)
            <section aria-labelledby="open-account-heading" class="mt-10 rounded-2xl border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-900 dark:bg-indigo-950/30">
                <h2 id="open-account-heading" class="text-xl font-bold">Abrir nueva cuenta</h2>
                <form method="POST" action="{{ route('accounts.store') }}" class="mt-5">
                    @csrf
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label for="tipo" class="block text-sm font-semibold text-indigo-900 dark:text-indigo-200">Tipo de cuenta</label>
                            <select id="tipo" name="tipo" class="mt-2 block w-full rounded-lg border border-indigo-300 bg-white px-3 py-2 text-sm text-indigo-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-indigo-700 dark:bg-slate-800 dark:text-indigo-100">
                                @foreach ($tiposCuenta as $tipoCuenta)
                                    <option value="{{ $tipoCuenta['identificador'] }}">{{ $tipoCuenta['etiqueta'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600">
                            Abrir cuenta
                        </button>
                    </div>
                    @error('tipo')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </form>
            </section>
        @endif

        {{-- Catalog reference --}}
        <section aria-labelledby="catalog-heading" class="mt-10">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="catalog-heading" class="text-xl font-bold">Catálogo de tipos de cuenta</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Metadatos de producto; no describen el estado de una cuenta.</p>
                </div>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                @foreach ($tiposCuenta as $tipoCuenta)
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-lg font-semibold">{{ $tipoCuenta['etiqueta'] }}</h3>
                        <dl class="mt-5 grid gap-4 text-sm">
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">Identificador</dt>
                                <dd class="mt-1 font-mono text-slate-900 dark:text-slate-100">{{ $tipoCuenta['identificador'] }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">Monedas elegibles</dt>
                                <dd class="mt-1 text-slate-900 dark:text-slate-100">{{ implode(', ', $tipoCuenta['monedasElegibles']) }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">Política de sobregiro</dt>
                                <dd class="mt-1 text-slate-900 dark:text-slate-100">{{ $tipoCuenta['politicaSobregiro'] }}</dd>
                            </div>
                        </dl>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Singleton evidence --}}
        <section aria-labelledby="singleton-heading" class="mt-10 rounded-2xl border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-900 dark:bg-indigo-950/30">
            <h2 id="singleton-heading" class="text-xl font-bold">Evidencia educativa del Singleton</h2>
            <p class="mt-3 max-w-3xl leading-7 text-indigo-950 dark:text-indigo-100">Esta evidencia proviene de dos resoluciones reales del contenedor de Laravel. El Singleton conserva metadatos de referencia inmutables; no contiene cuentas, saldos ni estado de la solicitud.</p>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="font-semibold text-indigo-800 dark:text-indigo-200">Contrato resuelto</dt>
                    <dd class="mt-1 break-words font-mono text-indigo-950 dark:text-indigo-100">{{ $evidenciaSingleton['contrato'] }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-indigo-800 dark:text-indigo-200">Implementación concreta</dt>
                    <dd class="mt-1 break-words font-mono text-indigo-950 dark:text-indigo-100">{{ $evidenciaSingleton['implementacion'] }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-indigo-800 dark:text-indigo-200">Identidad observada</dt>
                    <dd class="mt-1 text-indigo-950 dark:text-indigo-100">{{ $evidenciaSingleton['mismaInstancia'] ? 'Sí, es la misma instancia.' : 'No, son instancias diferentes.' }}</dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
