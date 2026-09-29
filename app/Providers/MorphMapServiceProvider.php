<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class MorphMapServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Relation::enforceMorphMap(config('morph-map'));
    }
}
