<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function providerMemberships(): HasMany
    {
        return $this->hasMany(ProviderMembership::class);
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(
            ParkingProvider::class,
            'provider_memberships',
            'user_id',
            'parking_provider_id',
        )->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Providers available to the authenticated user
     * in the Filament Provider Panel.
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->providers()
            ->where('is_active', true)
            ->get();
    }

    /**
     * Determine whether the user can access a provider.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        if (! $tenant instanceof ParkingProvider) {
            return false;
        }

        if (! $tenant->is_active) {
            return false;
        }

        return $this->providerMemberships()
            ->where('parking_provider_id', $tenant->getKey())
            ->exists();
    }
}
