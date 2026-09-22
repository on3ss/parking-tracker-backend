<?php

namespace App\Http\Requests\Api\V1\Parking;

use App\Data\Parking\GetParkingAvailabilityHistoryData;
use Illuminate\Foundation\Http\FormRequest;

final class AvailabilityHistoryRequest extends FormRequest
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

    public function data(
        $parkingIdentifier = null,
        $default = null
    ): GetParkingAvailabilityHistoryData {
        return new GetParkingAvailabilityHistoryData(
            parkingIdentifier: $parkingIdentifier,
            perPage: (int) $this->input(
                'per_page',
                20,
            ),
            page: (int) $this->input(
                'page',
                1,
            ),
        );
    }
}
