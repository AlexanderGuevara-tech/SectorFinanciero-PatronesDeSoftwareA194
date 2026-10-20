@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-7xl">
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Detalle de préstamo</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Préstamo #{{ $prestamo->id }}</h1>
        </header>

        <section aria-labelledby="loan-heading" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <h2 id="loan-heading" class="text-xl font-bold">Datos del préstamo</h2>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-600 dark:text-slate-400">Cliente prestatario</dt><dd>{{ $prestamo->borrowerId }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Cuenta de destino</dt><dd>{{ $prestamo->destinationAccountId }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Solicitante</dt><dd>{{ $prestamo->applicantId }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Producto</dt><dd>{{ $prestamo->producto->name }} ({{ $prestamo->producto->id }})</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Principal</dt><dd>{{ $prestamo->producto->currency }} {{ $prestamo->principal }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Tasa anual</dt><dd>{{ $prestamo->producto->annualRate }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Plazo (meses)</dt><dd>{{ $prestamo->producto->termMonths }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Clave de solicitud</dt><dd>{{ $prestamo->requestKey }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Estado</dt><dd>{{ $prestamo->status }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Aprobado por</dt><dd>{{ $prestamo->approvedBy ?? 'Sin aprobar' }}</dd></div>
                <div><dt class="text-slate-600 dark:text-slate-400">Transacción</dt><dd>{{ $prestamo->transactionId ?? 'Sin transacción' }}</dd></div>
            </dl>
        </section>

        <div class="mt-8">
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
                &larr; Volver al panel
            </a>
        </div>
    </div>
@endsection
