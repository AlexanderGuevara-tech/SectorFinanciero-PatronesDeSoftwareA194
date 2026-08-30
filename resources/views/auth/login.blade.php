<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso — Sistema Bancario CORE</title>
</head>
<body>
    <main>
        <h1>Ingreso al Sistema Bancario CORE</h1>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required>

            <label>
                <input name="remember" type="checkbox" value="1">
                Recordarme
            </label>

            <button type="submit">Ingresar</button>
        </form>
    </main>
</body>
</html>
