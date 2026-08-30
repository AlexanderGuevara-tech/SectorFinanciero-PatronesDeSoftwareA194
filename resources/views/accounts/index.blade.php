@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-7xl">
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Módulo de cuentas</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Cuentas</h1>
            <p class="mt-3 max-w-3xl text-slate-600 dark:text-slate-400">Consulta la referencia de tipos de cuenta disponible hoy, sin confundirla con cuentas financieras registradas.</p>
        </header>

        <section aria-labelledby="empty-state-heading" class="mt-10 rounded-2xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900 dark:bg-amber-950/30">
            <h2 id="empty-state-heading" class="text-xl font-bold">No hay cuentas registradas todavía</h2>
            <p class="mt-3 max-w-3xl leading-7 text-amber-950 dark:text-amber-100">La persistencia de cuentas todavía no está habilitada. La información de esta página es de referencia y no representa cuentas reales.</p>
        </section>

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