<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->canAccessSystem()) {
            return $next($request);
        }

        AuditLog::record('auth.session_blocked', $user, [
            'description' => $user->isDisabled()
                ? 'Sesion bloqueada porque la cuenta esta deshabilitada.'
                : 'Sesion bloqueada porque la cuenta esta pendiente de aprobacion.',
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', $user->isDisabled()
            ? 'Tu cuenta esta deshabilitada. Contacta a un administrador.'
            : 'Tu cuenta esta pendiente de aprobacion por un administrador.'
        );
    }
}
