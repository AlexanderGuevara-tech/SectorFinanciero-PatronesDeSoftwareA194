<article class="flex h-full flex-col rounded-2xl border p-6 {{ $modulo['estado'] === 'activo' ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/40' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' }}">
    <div class="flex items-start justify-between gap-4">
        <h3 class="text-lg font-semibold">{{ $modulo['etiqueta'] }}</h3>
        <span aria-label="{{ $modulo['estado'] === 'activo' ? 'Módulo activo' : 'Módulo planificado' }}" class="shrink-0 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $modulo['estado'] === 'activo' ? 'bg-emerald-200 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-100' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
            {{ $modulo['estado'] === 'activo' ? 'Activo' : 'Planificado' }}
        </span>
    </div>
    <p class="mt-4 flex-1 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $modulo['descripcion'] }}</p>
    @if ($modulo['estado'] === 'activo' && $modulo['nombreRuta'] !== null && ($modulo['permiso'] === null || auth()->user()->can($modulo['permiso'])))
        <a href="{{ route($modulo['nombreRuta']) }}" class="mt-5 inline-flex w-fit rounded-lg text-sm font-semibold text-indigo-700 underline decoration-indigo-300 underline-offset-4 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:text-indigo-300 dark:decoration-indigo-700">
            Ver vista
        </a>
    @endif
    @if ($modulo['estado'] === 'planificado')
        <p class="mt-5 text-sm font-semibold text-slate-500 dark:text-slate-400">Aún no disponible</p>
    @endif
</article>