<?php

namespace App\Http\Requests\Parking;

use Illuminate\Foundation\Http\FormRequest;

final class NearbyParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'radius' => [
                'sometimes',
                'integer',
                'min:1',
                'max:50000',
            ],

            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function latitude(): float
    {
        return (float) $this->validated('latitude');
    }

    public function longitude(): float
    {
        return (float) $this->validated('longitude');
    }

    public function radius(): int
    {
        return (int) ($this->validated('radius') ?? 2000);
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? 50);
    }
}
