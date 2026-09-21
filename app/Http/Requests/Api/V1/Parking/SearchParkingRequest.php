<?php

namespace App\Http\Requests\Api\V1\Parking;

use App\Enums\AvailabilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SearchParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter.type' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'facility',
                    'street',
                ]),
            ],

            'filter.availability' => [
                'sometimes',
                'nullable',
                Rule::enum(AvailabilityStatus::class),
            ],

            'filter.provider_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:parking_providers,id',
            ],

            'latitude' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'radius' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:50000',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $filter = $this->input('filter', []);

        if (is_array($filter) && isset($filter['availability'])) {
            $filter['availability'] = strtoupper(
                (string) $filter['availability'],
            );

            $this->merge([
                'filter' => $filter,
            ]);
        }
    }

    public function data($key = null, $default = null): \App\Data\Parking\SearchParkingData
    {
        $filter = $this->input('filter', []);

        $latitude = $this->input('latitude');
        $longitude = $this->input('longitude');

        return new \App\Data\Parking\SearchParkingData(
            type: $filter['type'] ?? null,
            availability: isset($filter['availability'])
            ? AvailabilityStatus::from($filter['availability'])
            : null,
            providerId: isset($filter['provider_id'])
            ? (int) $filter['provider_id']
            : null,
            latitude: $latitude !== null
            ? (float) $latitude
            : null,
            longitude: $longitude !== null
            ? (float) $longitude
            : null,
            radiusMeters: isset($this->radius)
            ? (int) $this->radius
            : null,
            perPage: (int) ($this->input('per_page', 20)),
        );
    }
}