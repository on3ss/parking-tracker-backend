<?php

namespace App\Http\Requests\Parking;

use Illuminate\Foundation\Http\FormRequest;

final class ReportParkingAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'available_spaces' => [
                'required',
                'integer',
                'min:0',
            ],

            'occupied_spaces' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function availableSpaces(): int
    {
        return (int) $this->validated('available_spaces');
    }

    public function occupiedSpaces(): ?int
    {
        $value = $this->validated('occupied_spaces');

        return $value === null ? null : (int) $value;
    }
}
