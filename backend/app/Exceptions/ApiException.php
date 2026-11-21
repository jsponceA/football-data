<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    public function __construct(int $status, string $message, array $headers = [])
    {
        parent::__construct($status, $message, null, $headers);
    }
}
