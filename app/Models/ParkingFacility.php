<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingFacilityType;
use App\Enums\ParkingSource;
use App\Enums\ParkingStatus;
use App\Models\Concerns\HasSlug;
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
    'availability_source',
    'availability_report_id',
    'availability_updated_at',
    'description',
])]
class ParkingFacility extends Model
{
    /** @use HasFactory<ParkingFacilitiesFactory> */
    use HasFactory;

    use HasSlug;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ParkingFacilityType::class,
            'status' => ParkingStatus::class,
            'availability_status' => AvailabilityStatus::class,
            'availability_source' => ParkingSource::class,

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

    public function availabilityReport(): BelongsTo
    {
        return $this->belongsTo(OccupancyReport::class, 'availability_report_id');
    }
}
