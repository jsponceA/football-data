<?php

namespace App\Http\Requests\Auth;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'turnstile_token' => Turnstile::rules(),
        ];
    }

    public function messages(): array
    {
        return ['turnstile_token.required' => 'Confirma que no eres un robot.'];
    }
}
