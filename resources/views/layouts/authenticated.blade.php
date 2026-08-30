<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel CORE' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen lg:flex">
        <aside class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 lg:min-h-screen lg:w-72 lg:border-b-0 lg:border-r">
            <div class="flex items-center justify-between gap-4 px-6 py-5">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-tight text-indigo-700 focus:outline-2 focus:outline-offset-4 focus:outline-indigo-600 dark:text-indigo-300">
                    CORE
                </a>
                <details class="relative lg:hidden">
                    <summary aria-label="Abrir navegación" class="cursor-pointer list-none rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:border-slate-700">
                        <span class="sr-only">Abrir navegación</span>
                        Menú
                    </summary>
                    <div class="absolute right-0 z-10 mt-3 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                        <x-core.navigation :navegacion="$navegacion" />
                    </div>
                </details>
            </div>
            <div class="hidden px-4 pb-6 lg:block">
                <x-core.navigation :navegacion="$navegacion" />
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-5 lg:px-10">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Sistema Bancario</p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $usuario->name }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-right text-sm text-slate-600 dark:text-slate-400">
                            <span class="block font-semibold text-slate-900 dark:text-slate-100">{{ $usuario->roles->pluck('name')->join(', ') ?: 'Sin rol asignado' }}</span>
                            Usuario autenticado
                        </p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main id="main-content" class="px-6 py-8 lg:px-10">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>