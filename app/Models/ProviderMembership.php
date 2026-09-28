<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_provider_id',
        'user_id',
        'role',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(
            ParkingProvider::class,
            'parking_provider_id',
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
