@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Resumen de CORE</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Capacidades bancarias</h1>
                <p class="mt-3 max-w-2xl text-slate-600 dark:text-slate-400">Un mapa honesto de lo disponible hoy y lo planificado para el sistema bancario modular.</p>
            </div>
        </div>

        @can('manage-users')
            <p class="mt-8 rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm font-semibold text-indigo-900 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100">
                La administración de usuarios está disponible para vos.
            </p>
        @endcan

        <section aria-labelledby="module-overview" class="mt-10">
            <div class="flex items-center justify-between gap-4">
                        <h2 id="module-overview" class="text-xl font-bold">Resumen de módulos</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">No se muestran métricas financieras.</p>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <x-core.module-card :modulo="['identificador' => 'autenticacion-rbac', 'etiqueta' => 'Autenticación y RBAC', 'descripcion' => 'La autenticación de sesión y el control de acceso basado en roles están activos.', 'estado' => 'activo', 'nombreRuta' => 'dashboard', 'permiso' => null]" />
                @can('view-accounts')
                    <x-core.module-card :modulo="['identificador' => 'catalogo-tipos-cuenta', 'etiqueta' => 'Catálogo de tipos de cuenta', 'descripcion' => 'Catálogo de tipos de cuenta solo de referencia; no crea estado de cuenta.', 'estado' => 'activo', 'nombreRuta' => 'dashboard', 'permiso' => 'view-accounts']" />
                @endcan
                @foreach ($modulos as $modulo)
                    <x-core.module-card :modulo="$modulo" />
                @endforeach
            </div>
        </section>
    </div>
@endsection