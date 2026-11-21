<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // errores de la API siempre como { "message": "..." }; el 422 lo dejo como lo hace Laravel
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                $status = 401;
            } elseif ($e instanceof AuthorizationException) {
                $status = 403;
            } elseif ($e instanceof ModelNotFoundException) {
                $status = 404;
            } elseif ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } else {
                return null;
            }

            $messages = [
                401 => 'No has iniciado sesión.',
                403 => 'No tienes permiso para hacer esto.',
                404 => 'No se encontró lo que buscas.',
                405 => 'Método no permitido.',
                429 => 'Demasiadas peticiones, espera un momento e intenta de nuevo.',
                502 => 'No se pudo obtener la información del servicio externo.',
                503 => 'El servicio externo no está disponible por ahora.',
            ];

            $message = $e instanceof ApiException
                ? $e->getMessage()
                : ($messages[$status] ?? 'Ocurrió un error.');

            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            return response()->json(['message' => $message], $status, $headers);
        });
    })->create();
