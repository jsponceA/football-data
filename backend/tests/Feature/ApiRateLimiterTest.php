<?php

namespace Tests\Feature;

use App\Services\FootballData\ApiRateLimiter;
use Tests\TestCase;

class ApiRateLimiterTest extends TestCase
{
    private function gastar(ApiRateLimiter $limiter, int $veces): void
    {
        for ($i = 0; $i < $veces; $i++) {
            $limiter->hit();
        }
    }

    public function test_con_el_contador_en_cero_hay_cupo(): void
    {
        $limiter = new ApiRateLimiter(9);

        $this->assertTrue($limiter->hasCapacity());
        $this->assertSame(9, $limiter->remaining());
    }

    public function test_sin_cupo_cuando_se_llega_al_limite(): void
    {
        $limiter = new ApiRateLimiter(3);

        $this->gastar($limiter, 3);

        $this->assertFalse($limiter->hasCapacity());
        $this->assertSame(0, $limiter->remaining());
    }

    public function test_pide_cupo_para_varias_llamadas_a_la_vez(): void
    {
        $limiter = new ApiRateLimiter(9);

        $this->gastar($limiter, 8);

        // queda 1: alcanza para una llamada, pero no para las 2 de una importación
        $this->assertTrue($limiter->hasCapacity(1));
        $this->assertFalse($limiter->hasCapacity(2));
    }

    public function test_retry_after_dice_cuanto_falta_para_poder_llamar(): void
    {
        $limiter = new ApiRateLimiter(2);

        $this->gastar($limiter, 2);

        $this->assertGreaterThanOrEqual(1, $limiter->retryAfter());
        $this->assertLessThanOrEqual(60, $limiter->retryAfter());
    }

    public function test_el_cupo_vuelve_pasado_un_minuto(): void
    {
        $limiter = new ApiRateLimiter(2);

        $this->gastar($limiter, 2);
        $this->assertFalse($limiter->hasCapacity());

        $this->travel(61)->seconds();

        $this->assertTrue($limiter->hasCapacity());
        $this->assertSame(2, $limiter->remaining());
    }
}
