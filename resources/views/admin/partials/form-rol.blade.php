{{-- Shared role fields for create and edit --}}
@php
    $rolForm = $rolForm ?? null;
@endphp

<div class="space-y-5">
    <div>
        <label for="name" class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Nombre del rol</label>
        <input type="text" id="name" name="name" value="{{ old('name', $rolForm?->name) }}" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" required>
        @error('name')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Descripción</label>
        <textarea id="description" name="description" rows="3" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">{{ old('description', $rolForm?->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>
