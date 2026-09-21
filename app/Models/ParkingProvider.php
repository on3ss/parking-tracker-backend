<?php

namespace App\Models;

use App\Enums\ParkingProviderType;
use Database\Factories\ParkingProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'type', 'description', 'is_active'])]
class ParkingProvider extends Model
{
    /** @use HasFactory<ParkingProviderFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ParkingProviderType::class,
            'is_active' => 'boolean',
        ];
    }

    public function parkingFacilities(): HasMany
    {
        return $this->hasMany(ParkingFacility::class);
    }

    public function streetParkings(): HasMany
    {
        return $this->hasMany(StreetParking::class);
    }
}
