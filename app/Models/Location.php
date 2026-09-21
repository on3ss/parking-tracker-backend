<?php

namespace App\Models;

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['address_line1', 'address_line2', 'locality', 'administrative_area', 'postal_code', 'country_code'])]
class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'coordinates' => Point::class,
        ];
    }

    public function parkingFacility(): HasOne
    {
        return $this->hasOne(ParkingFacility::class);
    }

    public function streetParking(): HasOne
    {
        return $this->hasOne(StreetParking::class);
    }
}
