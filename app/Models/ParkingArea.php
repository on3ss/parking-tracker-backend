<?php

namespace App\Models;

use App\Models\ParkingFacility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'parking_facility_id',
    'name',
    'code',
    'vehicle_type',
    'capacity',
    'is_active',
])]
class ParkingArea extends Model
{
    /** @use HasFactory<\Database\Factories\ParkingAreaFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parkingFacility(): BelongsTo
    {
        return $this->belongsTo(ParkingFacility::class);
    }
}
