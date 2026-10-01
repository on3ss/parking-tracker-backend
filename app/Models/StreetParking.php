<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Enums\ParkingStatus;
use App\Enums\StreetParkingSide;
use App\Enums\StreetParkingType;
use App\Models\Concerns\HasSlug;
use App\Models\Location;
use App\Models\OccupancyReport;
use App\Models\ParkingProvider;
use Clickbar\Magellan\Data\Geometries\LineString;
use Database\Factories\StreetParkingFactory;
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
    'availability_source',
    'availability_report_id',
    'availability_updated_at',
    'description',
    'geometry',
])]
class StreetParking extends Model
{
    /** @use HasFactory<StreetParkingFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return [
            'parking_type' => StreetParkingType::class,
            'status' => ParkingStatus::class,
            'availability_status' => AvailabilityStatus::class,
            'availability_source' => ParkingSource::class,

            'capacity' => 'integer',
            'available_spaces' => 'integer',
            'availability_updated_at' => 'datetime',

            'geometry' => LineString::class,
            'side' => StreetParkingSide::class,
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

    public function availabilityReport(): BelongsTo
    {
        return $this->belongsTo(OccupancyReport::class, 'availability_report_id');
    }
}
