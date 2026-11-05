<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Security hardening enterprise: enforce HTTPS, add security headers,
        // dan protect sensitive web routes from common attack vectors.
        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\EnforceHttps::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureAccountApproved::class,
            \App\Http\Middleware\RestrictDashboardAccess::class,
            \App\Http\Middleware\RefreshReportData::class,
            \App\Http\Middleware\RenderReportInFilament::class,
        ]);
        $middleware->prependToPriorityList(
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\RestrictDashboardAccess::class,
        );

        // Alias middleware spatie/laravel-permission (role & permission)
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
