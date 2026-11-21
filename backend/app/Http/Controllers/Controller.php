<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    // tamaño de página de todos los listados, salvo que pidan otro con ?per_page=
    protected const PER_PAGE = 15;

    protected function perPage(Request $request): int
    {
        return $request->integer('per_page', self::PER_PAGE);
    }
}
