<?php

namespace App\Jobs;

use App\Exceptions\ApiException;
use App\Services\CompetitionService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

// importa o refresca una liga en segundo plano. No habla con football-data por su cuenta: usa el mismo
// servicio que la API, así pasa por el mismo limitador de 9 peticiones por minuto.
class ImportCompetition implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    // lo que se espera antes de reintentar cuando no hay cupo o el servicio externo no responde
    public const WAIT_SECONDS = 60;

    public function __construct(public readonly int $externalId)
    {
    }

    // una liga no se encola dos veces mientras haya un trabajo suyo pendiente
    public function uniqueId(): string
    {
        return (string) $this->externalId;
    }

    // Posponer por falta de cupo cuenta como intento. Por eso en vez de un número de intentos se fija un plazo:
    // durante media hora sigue intentando y pasado ese tiempo el trabajo falla y queda registrado.
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(30);
    }

    public function handle(CompetitionService $competitions): void
    {
        try {
            $result = $competitions->import($this->externalId);
        } catch (ApiException $e) {
            $this->handleApiError($e);

            return;
        }

        // sin cupo o con el servicio caído, el servicio devuelve la copia vieja en vez de un error:
        // para quien consulta es suficiente, pero aquí el objetivo es refrescarla, así que se reintenta
        if ($result->stale) {
            $this->release(self::WAIT_SECONDS);
        }
    }

    private function handleApiError(ApiException $e): void
    {
        $status = $e->getStatusCode();

        if ($status === Response::HTTP_TOO_MANY_REQUESTS) {
            // se espera lo que indica el limitador, que es justo lo que falta para que haya cupo otra vez
            $this->release(max(1, (int) ($e->getHeaders()['Retry-After'] ?? self::WAIT_SECONDS)));

            return;
        }

        if (in_array($status, [Response::HTTP_BAD_GATEWAY, Response::HTTP_SERVICE_UNAVAILABLE], true)) {
            $this->release(self::WAIT_SECONDS);

            return;
        }

        // 403 (liga de pago) y 404 (no existe): reintentar no cambiaría nada, así que se descarta
        Log::warning('No se pudo refrescar la liga', [
            'competition' => $this->externalId,
            'status' => $status,
            'message' => $e->getMessage(),
        ]);
    }
}
