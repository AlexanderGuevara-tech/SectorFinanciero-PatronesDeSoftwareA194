<nav aria-label="Navegación principal">
    <ul class="grid gap-2">
        @foreach ($navegacion as $elemento)
            @if ($elemento['permiso'] === null || auth()->user()->can($elemento['permiso']))
                @if ($elemento['nombreRuta'] !== null)
                    <li>
                        <a href="{{ route($elemento['nombreRuta']) }}" @if (request()->routeIs($elemento['nombreRuta']) || ($elemento['nombreRuta'] === 'dashboard' && request()->is('/'))) aria-current="page" @endif class="block rounded-lg px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-indigo-300">
                            {{ $elemento['etiqueta'] }}
                            <span class="mt-1 block text-xs font-normal text-slate-500 dark:text-slate-400">{{ $elemento['descripcion'] }}</span>
                        </a>
                    </li>
                @endif
            @endif
        @endforeach
    </ul>
</nav>