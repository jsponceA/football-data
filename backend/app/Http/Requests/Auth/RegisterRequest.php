<?php

namespace App\Http\Requests\Auth;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// el registro propio solo pide datos de la cuenta: el rol lo decide el servidor, nunca el formulario
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'turnstile_token' => Turnstile::rules(),
        ];
    }

    public function messages(): array
    {
        return ['turnstile_token.required' => 'Confirma que no eres un robot.'];
    }
}
