<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// lo que cada persona puede cambiar de sí misma: no incluye roles ni estado
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            // vacía = no cambiar la contraseña; para cambiarla hay que confirmar la actual
            'password' => ['nullable', 'string', 'min:8'],
            'current_password' => ['required_with:password', 'nullable', 'current_password:api'],
        ];
    }
}
