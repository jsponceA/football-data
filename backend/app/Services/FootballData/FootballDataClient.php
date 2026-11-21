<?php

namespace App\Services\FootballData;

use App\Exceptions\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class FootballDataClient
{
    public function __construct(private ApiRateLimiter $limiter)
    {
    }

    public function competitions(): array
    {
        return $this->get('competitions');
    }

    // trae los equipos con su plantilla en una sola llamada
    public function competitionTeams(int|string $id): array
    {
        return $this->get("competitions/{$id}/teams");
    }

    private function get(string $path): array
    {
        $config = config('services.football_data');

        if (empty($config['token'])) {
            throw new ApiException(HttpResponse::HTTP_SERVICE_UNAVAILABLE, 'El servicio externo no está configurado.');
        }

        if (! $this->limiter->hasCapacity()) {
            throw new ApiException(HttpResponse::HTTP_TOO_MANY_REQUESTS, 'Se alcanzó el límite de consultas al servicio externo.', [
                'Retry-After' => $this->limiter->retryAfter(),
            ]);
        }

        // se cuenta antes de llamar: si falla igual gastó cupo
        $this->limiter->hit();

        try {
            $response = Http::baseUrl($config['base_url'])
                ->withHeaders(['X-Auth-Token' => $config['token']])
                ->acceptJson()
                ->timeout($config['timeout'])
                ->get($path);
        } catch (ConnectionException $e) {
            Log::warning('football-data no respondió', ['path' => $path, 'error' => $e->getMessage()]);

            throw new ApiException(HttpResponse::HTTP_SERVICE_UNAVAILABLE, 'El servicio externo no respondió.');
        }

        return $this->parse($response, $path);
    }

    private function parse(Response $response, string $path): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        $status = $response->status();

        if ($status === HttpResponse::HTTP_FORBIDDEN) {
            throw new ApiException(HttpResponse::HTTP_FORBIDDEN, 'Esa liga no está disponible con el plan gratuito.');
        }

        if ($status === HttpResponse::HTTP_NOT_FOUND) {
            throw new ApiException(HttpResponse::HTTP_NOT_FOUND, 'La liga no existe en el servicio externo.');
        }

        if ($status === HttpResponse::HTTP_TOO_MANY_REQUESTS) {
            $retryAfter = (int) $response->header('Retry-After') ?: ($this->limiter->retryAfter() ?: 60);

            throw new ApiException(HttpResponse::HTTP_TOO_MANY_REQUESTS, 'El servicio externo pidió esperar antes de volver a consultar.', [
                'Retry-After' => $retryAfter,
            ]);
        }

        // 400/401 (token malo) y 5xx son problema del servicio o nuestro, no del usuario
        Log::error('football-data respondió con error', ['path' => $path, 'status' => $status]);

        throw new ApiException(HttpResponse::HTTP_BAD_GATEWAY, 'El servicio externo devolvió un error.');
    }
}
