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

        if ($userRole === 'superadmin') {
            return $next($request);
        }

        if (auth()->user()->isManager() && auth()->user()->isTeknik() && in_array('admin', $roles, true)) {
            abort_if(
                $request->route()?->getName() === 'stock-requests.store',
                403,
                'Stok Request Teknik hanya dapat diajukan oleh Admin Teknik.'
            );

            return $next($request);
        }

        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
