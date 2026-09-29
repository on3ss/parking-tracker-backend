<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ProviderPanelProvider;
use App\Providers\MorphMapServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ProviderPanelProvider::class,
    MorphMapServiceProvider::class,
];
