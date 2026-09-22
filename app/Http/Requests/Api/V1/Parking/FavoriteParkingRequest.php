<?php

namespace App\Http\Requests\Api\V1\Parking;

use App\Data\Parking\FavoriteParkingData;
use Illuminate\Foundation\Http\FormRequest;

final class FavoriteParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function data($key = null, $default = null): FavoriteParkingData
    {
        return new FavoriteParkingData(
            userId: $this->user()->id,
            parkingIdentifier: $this->route('parking'),
        );
    }
}