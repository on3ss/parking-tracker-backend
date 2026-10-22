<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SyncSpatiePermissionsWithFilamentTenants
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        setPermissionsTeamId(Filament::getTenant()?->getKey());

        return $next($request);
    }
}
