<?php

// La SPA y la API viven en dominios distintos, así que el navegador pide permiso (CORS).
// Solo se acepta el origen de la SPA (FRONTEND_URL); los tokens van en la cabecera Authorization, no en cookies.
return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // sin esto el navegador esconde Retry-After a la SPA y no puede decir "vuelve a intentar en N segundos"
    'exposed_headers' => ['Retry-After'],

    // el navegador recuerda el permiso una hora y no repite la consulta previa en cada petición
    'max_age' => 3600,

    'supports_credentials' => false,

];
