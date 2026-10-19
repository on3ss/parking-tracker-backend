<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SyncSpatiePermissionsWithFilamentTenants
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $previousTeamId = getPermissionsTeamId();

        try {
            $user = $request->user();
            $tenant = Filament::getTenant();

            setPermissionsTeamId(
                $user !== null && $tenant !== null
                ? $tenant->getKey()
                : null,
            );

            return $next($request);
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
