@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-3xl">
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Módulo de administración</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Crear usuario</h1>
        </header>

        <form method="POST" action="{{ route('admin.users.store') }}" class="mt-8">
            @csrf
            @include('admin.partials.form-usuario')

            <div class="mt-5">
                <label for="customer_id" class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Cliente asociado (opcional)</label>
                <select id="customer_id" name="customer_id" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Sin cliente asociado</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ (string) old('customer_id') === (string) $cliente->id ? 'selected' : '' }}>{{ $cliente->name }} ({{ $cliente->doc_type }} {{ $cliente->doc_number }})</option>
                    @endforeach
                </select>
                @error('customer_id')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex items-center gap-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600">
                    Crear usuario
                </button>
                <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
