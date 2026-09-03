@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-7xl">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Módulo de administración</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Usuarios</h1>
                <p class="mt-3 max-w-2xl text-slate-600 dark:text-slate-400">Gestiona los usuarios registrados en el sistema.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600">
                Crear usuario
            </a>
        </header>

        @if (session('exito'))
            <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-200">
                {{ session('exito') }}
            </div>
        @endif

        @if (empty($usuarios))
            <section aria-labelledby="empty-state-heading" class="mt-10 rounded-2xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900 dark:bg-amber-950/30">
                <h2 id="empty-state-heading" class="text-xl font-bold">No hay usuarios registrados todavía</h2>
                <p class="mt-3 max-w-3xl leading-7 text-amber-950 dark:text-amber-100">Crea un usuario para comenzar.</p>
            </section>
        @else
            <section aria-labelledby="users-heading" class="mt-10">
                <h2 id="users-heading" class="sr-only">Usuarios registrados</h2>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Roles</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($usuarios as $u)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $u->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-400">{{ $u->email }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                        @forelse ($u->roles as $rol)
                                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">{{ $rol->name }}</span>
                                        @empty
                                            <span class="text-xs text-slate-400">Sin rol</span>
                                        @endforelse
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <a href="{{ route('admin.users.edit', $u) }}" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Editar</a>
                                        @if ($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="ml-3 inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-semibold text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('¿Eliminar este usuario?')">Eliminar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">
                    {{ $usuarios->links() }}
                </div>
            </section>
        @endif
    </div>
@endsection
