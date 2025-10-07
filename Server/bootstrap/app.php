<?php

use App\Http\Middleware\checkIsAdminOrManager;
use App\Http\Middleware\checkIsLeadOrManager;
use App\Http\Middleware\CheckUserInProject;
use App\Http\Middleware\CheckUserIsProjectLeadOrHigher;
use App\Http\Middleware\CheckUserIsProjectManager;
use App\Http\Middleware\isAdmin;
use App\Http\Middleware\isLead;
use App\Http\Middleware\isManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'isAdmin' => isAdmin::class,
            'isProjectMember' => CheckUserInProject::class,
            'isProjectManager' => CheckUserIsProjectManager::class,
            'isProjectLeaderOrHigher'=>CheckUserIsProjectLeadOrHigher::class
        ]);
        // Add CORS middleware to global middleware stack
        $middleware->append([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
