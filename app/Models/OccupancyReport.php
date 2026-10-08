<?php

namespace App\Models;

use App\Enums\ParkingSource;
use Database\Factories\OccupancyReportFactory;
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
    'reported_at',
])]
class OccupancyReport extends Model
{
    /** @use HasFactory<OccupancyReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source' => ParkingSource::class,

            'occupied_spaces' => 'integer',
            'available_spaces' => 'integer',
            'reported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \LogicException(
                'Occupancy reports are immutable observations.',
            );
        });
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
}
