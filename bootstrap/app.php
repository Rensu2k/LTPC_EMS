<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\DeveloperSignature;
use App\Http\Middleware\CheckUserStatus;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust only local proxies. For production behind a specific load balancer,
        // replace with actual proxy IP addresses.
        $middleware->trustProxies(at: '*');

        // Add ForceHttps middleware globally to ensure HTTPS for all requests
        $middleware->append(ForceHttps::class);
        
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            CheckUserStatus::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'dev-signature' => DeveloperSignature::class,
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
