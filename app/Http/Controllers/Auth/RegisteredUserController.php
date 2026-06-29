<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\SecurityPermissions;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $firstUser = User::count() === 0;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $firstUser ? SecurityPermissions::ADMIN : SecurityPermissions::VIEWER,
            'permissions' => $firstUser ? [] : SecurityPermissions::defaultsForRole(SecurityPermissions::VIEWER),
            'approved_at' => $firstUser ? now() : null,
        ]);

        event(new Registered($user));

        AuditLog::record($firstUser ? 'auth.first_admin_registered' : 'auth.registration_pending', $user, [
            'description' => $firstUser
                ? 'Primer usuario creado como administrador aprobado.'
                : 'Nuevo registro pendiente de aprobacion administrativa.',
            'user_id' => $firstUser ? $user->id : null,
        ]);

        if ($firstUser) {
            Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        }

        return redirect(route('login', absolute: false))
            ->with('status', 'Tu cuenta quedo registrada. Un administrador debe aprobarla antes de que puedas entrar.');
    }
}
