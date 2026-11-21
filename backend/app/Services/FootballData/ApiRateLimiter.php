<?php

namespace App\Services\FootballData;

use Illuminate\Support\Facades\RateLimiter;

class ApiRateLimiter
{
    private const KEY = 'football-data';

    private const WINDOW_SECONDS = 60;

    public function __construct(private int $maxPerMinute)
    {
    }

    public function hasCapacity(int $calls = 1): bool
    {
        return $this->remaining() >= $calls;
    }

    public function remaining(): int
    {
        return max(0, RateLimiter::remaining(self::KEY, $this->maxPerMinute));
    }

    public function hit(): void
    {
        RateLimiter::hit(self::KEY, self::WINDOW_SECONDS);
    }

    public function retryAfter(): int
    {
        return RateLimiter::availableIn(self::KEY);
    }
}
