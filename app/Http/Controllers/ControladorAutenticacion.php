<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeticionIngreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ControladorAutenticacion extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(PeticionIngreso $peticion): RedirectResponse
    {
        if (! Auth::attempt($peticion->validated(), $peticion->boolean('remember'))) {
            return back()->withErrors(['email' => 'Las credenciales no son válidas.'])->onlyInput('email');
        }

        $peticion->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
