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
        if (! $request->user()) {
            abort(403);
        }

        foreach ($roles as $role) {
            $check = UserRole::tryFrom($role);
            if ($check && $request->user()->role === $check) {
                return $next($request);
            }
        }

        abort(403, 'ليست لديك صلاحية الوصول إلى هذا القسم.');
    }
}
