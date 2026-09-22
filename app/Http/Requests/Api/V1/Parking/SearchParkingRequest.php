<?php

namespace App\Http\Requests\Api\V1\Parking;

use App\Data\Parking\SearchParkingData;
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
            /*
             * Application-level query routing.
             */
            'type' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'facility',
                    'street',
                ]),
            ],

            /*
             * Spatie Query Builder filters.
             */
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

            /*
             * Spatial search.
             */
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

            /*
             * Application-level sorting.
             */
            'sort' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in([
                    'distance',
                    '-distance',
                    'name',
                    '-name',
                    'capacity',
                    '-capacity',
                    'available_spaces',
                    '-available_spaces',
                ]),
            ],

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

    protected function prepareForValidation(): void
    {
        $filter = $this->input('filter', []);

        if (
            is_array($filter) &&
            isset($filter['availability'])
        ) {
            $filter['availability'] = strtoupper(
                (string) $filter['availability'],
            );

            $this->merge([
                'filter' => $filter,
            ]);
        }
    }

    public function data($key = null, $default = null): SearchParkingData
    {
        $filter = $this->input('filter', []);

        return new SearchParkingData(
            type: $this->input('type'),

            availability: isset($filter['availability'])
            ? AvailabilityStatus::from(
                $filter['availability'],
            )
            : null,

            providerId: isset($filter['provider_id'])
            ? (int) $filter['provider_id']
            : null,

            latitude: $this->input('latitude') !== null
            ? (float) $this->input('latitude')
            : null,

            longitude: $this->input('longitude') !== null
            ? (float) $this->input('longitude')
            : null,

            radiusMeters: $this->input('radius') !== null
            ? (int) $this->input('radius')
            : null,

            sort: $this->input('sort'),

            perPage: (int) $this->input('per_page', 20),

            page: (int) $this->input('page', 1),
        );
    }
}
