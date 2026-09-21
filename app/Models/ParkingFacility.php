<?php

namespace App\Models;

use App\Models\Location;
use App\Models\OccupancyReport;
use App\Models\ParkingArea;
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
    /** @use HasFactory<\Database\Factories\ParkingFacilitiesFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
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
}
