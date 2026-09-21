<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingStatus;
use App\Enums\StreetParkingType;
use App\Models\Location;
use App\Models\OccupancyReport;
use App\Models\ParkingProvider;
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
    'road_name',
    'side',
    'parking_type',
    'status',
    'capacity',
    'available_spaces',
    'availability_status',
    'availability_updated_at',
    'description',
])]
class StreetParking extends Model
{
    /** @use HasFactory<\Database\Factories\StreetParkingFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'parking_type' => StreetParkingType::class,
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

    public function occupancyReports(): HasMany
    {
        return $this->hasMany(OccupancyReport::class);
    }

    public function available(int $spaces = 5): static
    {
        return $this->state(fn() => [
            'availability_status' => 'AVAILABLE',
            'available_spaces' => $spaces,
            'availability_updated_at' => now(),
        ]);
    }

    public function full(): static
    {
        return $this->state(fn() => [
            'availability_status' => 'FULL',
            'available_spaces' => 0,
            'availability_updated_at' => now(),
        ]);
    }
}
