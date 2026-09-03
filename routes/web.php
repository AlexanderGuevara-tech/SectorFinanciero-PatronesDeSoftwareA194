<?php

use App\Http\Controllers\ControladorAutenticacion;
use App\Http\Controllers\ControladorCuentas;
use App\Http\Controllers\ControladorPanel;
use App\Http\Controllers\ControladorRoles;
use App\Http\Controllers\ControladorUsuarios;
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

    Route::middleware('can:manage-users')->group(function (): void {
        Route::get('/admin/usuarios', [ControladorUsuarios::class, 'index'])->name('admin.users.index');
        Route::get('/admin/usuarios/crear', [ControladorUsuarios::class, 'create'])->name('admin.users.create');
        Route::post('/admin/usuarios', [ControladorUsuarios::class, 'store'])->name('admin.users.store');
        Route::get('/admin/usuarios/{user}/editar', [ControladorUsuarios::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/usuarios/{user}', [ControladorUsuarios::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/usuarios/{user}', [ControladorUsuarios::class, 'destroy'])->name('admin.users.destroy');

        Route::get('/admin/roles', [ControladorRoles::class, 'index'])->name('admin.roles.index');
        Route::get('/admin/roles/crear', [ControladorRoles::class, 'create'])->name('admin.roles.create');
        Route::post('/admin/roles', [ControladorRoles::class, 'store'])->name('admin.roles.store');
        Route::get('/admin/roles/{role}/editar', [ControladorRoles::class, 'edit'])->name('admin.roles.edit');
        Route::put('/admin/roles/{role}', [ControladorRoles::class, 'update'])->name('admin.roles.update');
        Route::delete('/admin/roles/{role}', [ControladorRoles::class, 'destroy'])->name('admin.roles.destroy');
    });

    Route::post('/logout', [ControladorAutenticacion::class, 'destroy'])->name('logout');
});
