<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// quién puede abrir la documentación de la API (/docs/api):
//  - en local, cualquiera;
//  - en producción, solo con el usuario y la contraseña de DOCS_USER y DOCS_PASSWORD (el navegador los pide),
//    y si no se definió la contraseña, nadie.
// No se usa el token de la SPA porque un navegador que abre una dirección no envía la cabecera Authorization.
class DocsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        $password = (string) config('app.docs_password');

        if ($password === '') {
            abort(Response::HTTP_FORBIDDEN);
        }

        // las dos comparaciones se hacen siempre (sin "&&" directo), así no se nota cuál de las dos falló
        $userOk = hash_equals((string) config('app.docs_user'), (string) $request->getUser());
        $passwordOk = hash_equals($password, (string) $request->getPassword());

        if ($userOk && $passwordOk) {
            return $next($request);
        }

        return response('Documentación protegida.', Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => 'Basic realm="Documentación de la API", charset="UTF-8"',
        ]);
    }
}
