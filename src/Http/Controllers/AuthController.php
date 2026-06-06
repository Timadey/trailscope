<?php

namespace Trail\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Trail\Models\TrailUser;

class AuthController
{
    public function login(): Response
    {
        return Inertia::render('Auth/Login', [
            'submitUrl' => route('trail.authenticate'),
        ]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = TrailUser::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are invalid.',
            ]);
        }

        $request->session()->put('trail_user_id', $user->id);
        $request->session()->regenerate();

        return redirect(config('trail.path', 'trail') . '/traces');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('trail_user_id');

        return redirect(config('trail.path', 'trail') . '/login');
    }
}
