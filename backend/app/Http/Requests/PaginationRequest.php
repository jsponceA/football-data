<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// para los listados que solo se paginan, sin filtros
class PaginationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
