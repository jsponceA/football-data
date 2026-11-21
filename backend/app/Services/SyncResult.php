<?php

namespace App\Services;

// lo que devuelve un servicio: los datos y si son una copia local desactualizada
class SyncResult
{
    public function __construct(
        public readonly mixed $data,
        public readonly bool $stale = false,
    ) {
    }
}
