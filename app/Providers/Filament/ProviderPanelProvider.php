<?php

namespace App\Providers\Filament;

use App\Filament\Provider\Pages\Tenancy\EditProviderProfile;
use App\Filament\Provider\Pages\Tenancy\RegisterProvider;
use App\Filament\Resources\ParkingFacilities\ParkingFacilityResource;
use App\Filament\Resources\StreetParkings\StreetParkingResource;
use App\Models\ParkingProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ProviderPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('provider')
            ->path('provider')
            ->login()
            ->tenant(ParkingProvider::class, ownershipRelationship: 'provider')
            ->tenantRegistration(RegisterProvider::class)
            ->tenantProfile(EditProviderProfile::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                'panels::head.start',
                fn(): string => Vite::withEntryPoints([
                    'resources/js/filament/geometry-map.js',
                ])->toHtml(),
            )
            ->discoverResources(in: app_path('Filament/Provider/Resources'), for: 'App\Filament\Provider\Resources')
            ->resources([
                ParkingFacilityResource::class,
                StreetParkingResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Provider/Pages'), for: 'App\Filament\Provider\Pages')
            ->discoverWidgets(in: app_path('Filament/Provider/Widgets'), for: 'App\Filament\Provider\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->maxContentWidth(Width::Full);
    }
}
