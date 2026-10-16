<?php

namespace App\Support\Rbac\Concerns;

use Closure;

trait InteractsWithTeams
{
    /**
     * Run a callback with spatie's team context set to $teamId,
     * restoring the previous context afterwards.
     *
     * @template TReturn
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function withTeam(int|string|null $teamId, Closure $callback): mixed
    {
        $previous = getPermissionsTeamId();
        setPermissionsTeamId($teamId);

        try {
            return $callback();
        } finally {
            setPermissionsTeamId($previous);
        }
    }
}