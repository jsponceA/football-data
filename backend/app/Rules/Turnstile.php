<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

// valida el token del captcha de Cloudflare contra su API
class Turnstile implements ValidationRule
{
    // el captcha es opcional: solo se exige si hay un secreto configurado
    public static function enabled(): bool
    {
        return filled(config('services.turnstile.secret'));
    }

    // reglas listas para el campo del token; vacías cuando el captcha está apagado
    public static function rules(): array
    {
        return self::enabled() ? ['required', 'string', new self()] : [];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $response = Http::asForm()
                ->timeout(config('services.turnstile.timeout'))
                ->post(config('services.turnstile.verify_url'), [
                    'secret' => config('services.turnstile.secret'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (Throwable) {
            // si Cloudflare no responde no se deja pasar a nadie: es mejor pedir reintentar
            $fail('No pudimos verificar que eres una persona. Inténtalo de nuevo.');

            return;
        }

        if (! $response->successful() || ! $response->json('success')) {
            $fail('No pudimos verificar que eres una persona. Inténtalo de nuevo.');
        }
    }
}
