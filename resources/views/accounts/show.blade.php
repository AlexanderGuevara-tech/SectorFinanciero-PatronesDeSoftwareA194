@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-7xl">
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Detalle de cuenta</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Cuenta #{{ $cuentaId }}</h1>
        </header>

        @if (session('exito'))
            <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950/30 dark:text-green-200">
                {{ session('exito') }}
            </div>
        @endif

        <section aria-labelledby="balance-heading" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <h2 id="balance-heading" class="text-xl font-bold">Saldo</h2>
            <p class="mt-4 text-4xl font-mono font-bold text-slate-900 dark:text-slate-100">{{ $moneda }} {{ $saldo }}</p>
        </section>

        @if ($usuario->can('manage-accounts'))
            <section aria-labelledby="actions-heading" class="mt-6 flex gap-4">
                <h2 id="actions-heading" class="sr-only">Acciones</h2>
                <form method="POST" action="{{ route('accounts.block', $cuentaId) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 focus:outline-2 focus:outline-offset-2 focus:outline-red-600">
                        Bloquear
                    </button>
                </form>
                <form method="POST" action="{{ route('accounts.unblock', $cuentaId) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500 focus:outline-2 focus:outline-offset-2 focus:outline-green-600">
                        Desbloquear
                    </button>
                </form>
            </section>
        @endif

        <div class="mt-8">
            <a href="{{ route('accounts.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
                &larr; Volver a cuentas
            </a>
        </div>
    </div>
@endsection
