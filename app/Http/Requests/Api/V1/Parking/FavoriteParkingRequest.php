<?php

namespace App\Http\Requests\Api\V1\Parking;

use Illuminate\Foundation\Http\FormRequest;

final class FavoriteParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function parkingIdentifier(): string
    {
        return $this->route('parking');
    }
}
