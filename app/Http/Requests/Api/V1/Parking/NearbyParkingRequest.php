<?php

namespace App\Http\Requests\Api\V1\Parking;

use App\Data\Parking\FindNearbyParkingData;
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

    public function data($key = null, $default = null): FindNearbyParkingData
    {
        return new FindNearbyParkingData(
            latitude: (float) $this->input('latitude'),
            longitude: (float) $this->input('longitude'),
            radiusMeters: (int) $this->input('radius', 2000),
            limit: (int) $this->input('limit', 50),
        );
    }
}