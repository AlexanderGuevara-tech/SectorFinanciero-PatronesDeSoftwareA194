{{-- Permission checkbox grid shared by role create/edit --}}
@php
    $permisosSeleccionados = old('permissions', $permisosSeleccionados ?? []);
@endphp

<fieldset>
    <legend class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Permisos</legend>
    <div class="mt-3 grid gap-2 sm:grid-cols-2">
        @foreach ($permisos as $permiso)
            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                <input type="checkbox" name="permissions[]" value="{{ $permiso->id }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800" {{ in_array($permiso->id, $permisosSeleccionados) ? 'checked' : '' }}>
                <span class="text-sm text-slate-700 dark:text-slate-300">{{ $permiso->name }}</span>
            </label>
        @endforeach
    </div>
    @error('permissions')
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</fieldset>
