<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use Database\Factories\ParkingFacilitiesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'parking_provider_id',
    'location_id',
    'name',
    'slug',
    'type',
    'status',
    'capacity',
    'opening_time',
    'closing_time',
    'available_spaces',
    'availability_status',
    'availability_updated_at',
    'description',
])]
class ParkingFacility extends Model
{
    /** @use HasFactory<ParkingFacilitiesFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ParkingFacilityType::class,
            'status' => ParkingStatus::class,
            'availability_status' => AvailabilityStatus::class,

            'capacity' => 'integer',
            'available_spaces' => 'integer',
            'availability_updated_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ParkingProvider::class, 'parking_provider_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(ParkingArea::class);
    }

    public function occupancyReports(): HasMany
    {
        return $this->hasMany(OccupancyReport::class);
    }

    public function unavailable(): static
    {
        return $this->state(fn() => [
            'availability_status' => 'FULL',
            'available_spaces' => 0,
            'availability_updated_at' => now(),
        ]);
    }

    public function available(int $spaces = 20): static
    {
        return $this->state(fn() => [
            'availability_status' => 'AVAILABLE',
            'available_spaces' => $spaces,
            'availability_updated_at' => now(),
        ]);
    }
}
