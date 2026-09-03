{{-- Shared user form fields for create and edit --}}
@php
    $usuarioForm = $usuarioForm ?? null;
@endphp

<div class="space-y-5">
    <div>
        <label for="name" class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $usuarioForm?->name) }}" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" required>
        @error('name')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $usuarioForm?->email) }}" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" required>
        @error('email')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $usuarioForm ? 'Nueva contraseña (dejar vacío para mantener)' : 'Contraseña' }}</label>
        <input type="password" id="password" name="password" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" {{ $usuarioForm ? '' : 'required' }}>
        @error('password')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Roles</span>
        <div class="mt-3 space-y-2">
            @foreach ($roles as $rol)
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="roles[]" value="{{ $rol->id }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800" {{ in_array($rol->id, old('roles', $usuarioForm?->roles->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $rol->name }}</span>
                </label>
            @endforeach
        </div>
        @error('roles')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>
