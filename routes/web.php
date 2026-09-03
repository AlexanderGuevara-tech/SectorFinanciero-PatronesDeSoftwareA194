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

    Route::middleware('can:view-accounts')->group(function (): void {
        Route::get('/accounts', [ControladorCuentas::class, 'index'])->name('accounts.index');
        Route::get('/accounts/{account}', [ControladorCuentas::class, 'show'])->name('accounts.show');
    });

    Route::middleware('can:manage-accounts')->group(function (): void {
        Route::post('/accounts', [ControladorCuentas::class, 'store'])->name('accounts.store');
        Route::post('/accounts/{account}/block', [ControladorCuentas::class, 'block'])->name('accounts.block');
        Route::post('/accounts/{account}/unblock', [ControladorCuentas::class, 'unblock'])->name('accounts.unblock');
    });

    Route::post('/logout', [ControladorAutenticacion::class, 'destroy'])->name('logout');
});
