<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAkses
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $userRole = auth()->user()->role;

        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
