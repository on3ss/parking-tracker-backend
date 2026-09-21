<?php

namespace App\Models;

use App\Enums\ParkingSource;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'parking_facility_id',
    'street_parking_id',
    'user_id',
    'source',
    'occupied_spaces',
    'available_spaces',
    'confidence',
    'reported_at',
])]
class OccupancyReport extends Model
{
    /** @use HasFactory<\Database\Factories\OccupancyReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source' => ParkingSource::class,

            'occupied_spaces' => 'integer',
            'available_spaces' => 'integer',
            'confidence' => 'decimal:4',
            'reported_at' => 'datetime',
        ];
    }

    public function parkingFacility(): BelongsTo
    {
        return $this->belongsTo(ParkingFacility::class);
    }

    public function streetParking(): BelongsTo
    {
        return $this->belongsTo(StreetParking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function forFacility(): static
    {
        return $this->state(fn() => [
            'parking_facility_id' => ParkingFacility::factory(),
            'street_parking_id' => null,
        ]);
    }

    public function forStreetParking(): static
    {
        return $this->state(fn() => [
            'parking_facility_id' => null,
            'street_parking_id' => StreetParking::factory(),
        ]);
    }
}
