<?php

use App\Http\Middleware\AdminBasicAuth;
use App\Http\Middleware\TrackVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind CloudFront / a load balancer: trust X-Forwarded-* so isSecure() works.
        // Lock the EC2 security group to CloudFront only, otherwise these headers can be spoofed.
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->trustProxies(at: '*');
        $middleware->alias(['admin' => AdminBasicAuth::class, 'track' => TrackVisit::class]);

        // Check admin credentials BEFORE route-model binding, so guests never learn which IDs exist.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: AdminBasicAuth::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();