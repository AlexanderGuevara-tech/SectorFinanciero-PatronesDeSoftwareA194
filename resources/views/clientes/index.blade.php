@extends('layouts.authenticated')

@section('content')
    <div class="mx-auto max-w-7xl">
        <header><p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Registro bancario</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Clientes</h1></header>
        @if (session('exito')) <div class="mt-6 rounded-lg bg-green-50 p-4 text-green-800">{{ session('exito') }}</div> @endif
        <form method="GET" class="mt-8 flex gap-3"><label for="documento" class="sr-only">Buscar cliente</label><input id="documento" name="documento" value="{{ $documento }}" placeholder="Nombre o documento" class="rounded-lg border px-3 py-2"><button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white">Buscar</button></form>
        @if (empty($clientes))
            <p class="mt-8">No hay clientes registrados todavía.</p>
        @else
            <div class="mt-8 overflow-hidden rounded-2xl border"><table class="w-full text-left text-sm"><thead><tr><th class="p-4">Nombre</th><th class="p-4">Documento</th><th class="p-4">Contacto</th></tr></thead><tbody>@foreach ($clientes as $cliente)<tr class="border-t"><td class="p-4">{{ $cliente->nombre() }}</td><td class="p-4">{{ $cliente->tipoDocumento() }} {{ $cliente->numeroDocumento() }}</td><td class="p-4">{{ $cliente->email() ?? '—' }} / {{ $cliente->telefono() ?? '—' }}</td></tr>@endforeach</tbody></table></div>
        @endif
    </div>
@endsection
