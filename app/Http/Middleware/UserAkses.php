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

        if (auth()->user()->isManager() && auth()->user()->isTeknik()) {
            $routeName = $request->route()?->getName();
            $allowedRoutes = [
                'dashboard',
                'dashboard.searchItems',
                'dashboard.chartData',
                'dashboard.categoryByYear',
                'dashboard.monthlyData',
                'stock-requests.index',
                'stock-requests.export',
                'stock-requests.approve',
                'stock-requests.reject',
            ];

            abort_unless(in_array($routeName, $allowedRoutes, true), 403, 'Manager Teknik hanya dapat mengakses Dashboard dan Stock Request.');
        }

        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
