<?php

namespace App\Http\Requests\Api\V1\Parking;

use Illuminate\Foundation\Http\FormRequest;

final class ListFavoritesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input(
            'per_page',
            20,
        );
    }

    public function page(): int
    {
        return (int) $this->input(
            'page',
            1,
        );
    }
}