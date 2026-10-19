<?php

use RuntimeException;

it('restores the previous team when downstream execution throws', function () {
    setPermissionsTeamId(123);

    $middleware = app(
        \App\Http\Middleware\SyncSpatiePermissionsWithFilamentTenants::class,
    );

    $request = \Illuminate\Http\Request::create('/test');

    try {
        $middleware->handle(
            $request,
            function () {
                expect(getPermissionsTeamId())->toBeNull();

                throw new RuntimeException('Downstream failure');
            },
        );

        $this->fail('Expected downstream exception.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Downstream failure');
    }

    expect(getPermissionsTeamId())->toBe(123);
});