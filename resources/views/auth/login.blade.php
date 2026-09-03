<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso — Sistema Bancario CORE</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-100 dark:bg-slate-950 font-sans antialiased">
    <main class="w-full max-w-md px-6 py-10">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm px-8 py-10">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 text-center mb-8">Ingreso al Sistema Bancario CORE</h1>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Correo electrónico</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                        class="block w-full rounded-lg border @if ($errors->has('email')) border-red-500 @else border-slate-300 dark:border-slate-700 @endif bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600"
                    >
                    @error('email')
                        <p id="email-error" role="alert" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contraseña</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif
                        class="block w-full rounded-lg border @if ($errors->has('password')) border-red-500 @else border-slate-300 dark:border-slate-700 @endif bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600"
                    >
                    @error('password')
                        <p id="password-error" role="alert" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember --}}
                <div class="flex items-center gap-2 mb-6">
                    <input id="remember" name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600">
                    <label for="remember" class="text-sm text-slate-700 dark:text-slate-300">Recordarme</label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="w-full rounded-lg bg-slate-900 text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white px-4 py-2 text-sm font-semibold focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600">
                    Ingresar
                </button>
            </form>
        </div>
    </main>
</body>
</html>
