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
