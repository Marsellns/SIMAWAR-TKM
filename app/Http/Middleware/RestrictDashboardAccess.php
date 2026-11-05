<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->account_status !== 'approved' || ($role = $user->dashboardViewerRole()) === null) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            if ($request->routeIs('home', 'dashboard', 'concept.simawar', 'login', 'register', 'filament.report.home')) {
                return to_route($role->dashboardRoute());
            }

            abort_unless($request->routeIs('reports.data-version') || $role->canReadReport($request->route()?->getName()), 403);
        } else {
            // Livewire rechecks the source route when it refreshes an existing component.
            abort_unless($request->isMethod('POST') && $request->routeIs('logout', 'filament.report.auth.logout', '*livewire.update'), 403);
        }

        return $next($request);
    }
}
