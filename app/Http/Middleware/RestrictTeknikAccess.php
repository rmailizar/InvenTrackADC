<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictTeknikAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user?->isTeknik()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $allowedRoutes = [
            'dashboard',
            'dashboard.*',
            'items.*',
            'transactions.*',
            'api.items.show',
        ];

        foreach ($allowedRoutes as $allowedRoute) {
            if ($request->routeIs($allowedRoute)) {
                return $next($request);
            }
        }

        abort(403, 'User bidang Teknik hanya dapat mengakses Dashboard, Master SOH, Goods Receipt, dan Goods Issue.');
    }
}
