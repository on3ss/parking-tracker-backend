<?php

namespace App\Models;

use App\Enums\ParkingProviderType;
use App\Models\Concerns\HasSlug;
use Database\Factories\ParkingProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'type', 'description', 'is_active'])]
class ParkingProvider extends Model
{
    /** @use HasFactory<ParkingProviderFactory> */
    use HasFactory;

    use HasSlug;
    use SoftDeletes;

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

    public function memberships(): HasMany
    {
        return $this->hasMany(
            ProviderMembership::class,
            'parking_provider_id',
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'provider_memberships',
            'parking_provider_id',
            'user_id',
        )->withPivot('role')
            ->withTimestamps();
    }
}
