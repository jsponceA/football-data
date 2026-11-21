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
use Symfony\Component\HttpFoundation\Response;
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
                $status = Response::HTTP_UNAUTHORIZED;
            } elseif ($e instanceof AuthorizationException) {
                $status = Response::HTTP_FORBIDDEN;
            } elseif ($e instanceof ModelNotFoundException) {
                $status = Response::HTTP_NOT_FOUND;
            } elseif ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } else {
                return null;
            }

            $messages = [
                Response::HTTP_UNAUTHORIZED => 'No has iniciado sesión.',
                Response::HTTP_FORBIDDEN => 'No tienes permiso para hacer esto.',
                Response::HTTP_NOT_FOUND => 'No se encontró lo que buscas.',
                Response::HTTP_METHOD_NOT_ALLOWED => 'Método no permitido.',
                Response::HTTP_TOO_MANY_REQUESTS => 'Demasiadas peticiones, espera un momento e intenta de nuevo.',
                Response::HTTP_BAD_GATEWAY => 'No se pudo obtener la información del servicio externo.',
                Response::HTTP_SERVICE_UNAVAILABLE => 'El servicio externo no está disponible por ahora.',
            ];

            $message = $e instanceof ApiException
                ? $e->getMessage()
                : ($messages[$status] ?? 'Ocurrió un error.');

            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            return response()->json(['message' => $message], $status, $headers);
        });
    })->create();
