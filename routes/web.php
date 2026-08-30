<?php

use App\Http\Controllers\ControladorAutenticacion;
use App\Http\Controllers\ControladorCuentas;
use App\Http\Controllers\ControladorPanel;
use Illuminate\Support\Facades\Route;

Route::get('/', ControladorPanel::class)->middleware('auth');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [ControladorAutenticacion::class, 'create'])->name('login');
    Route::post('/login', [ControladorAutenticacion::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', ControladorPanel::class)->name('dashboard');
    Route::get('/accounts', [ControladorCuentas::class, 'index'])
        ->middleware('can:view-accounts')
        ->name('accounts.index');
    Route::post('/logout', [ControladorAutenticacion::class, 'destroy'])->name('logout');
});
