<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Votre compte est désactivé. Contactez l\'administrateur.');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        if (! $user->hasRole(...$allowed)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
