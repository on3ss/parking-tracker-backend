<?php

it('returns nearby parking', function () {
    $this
        ->getJson(
            '/api/v1/parking/nearby'
            . '?latitude=25.5779199'
            . '&longitude=91.8837004'
            . '&radius=2000',
        )
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'name',
                    'distance' => [
                        'meters',
                        'kilometers',
                    ],
                    'provider',
                    'location',
                    'capacity',
                    'availability',
                ],
            ],
        ]);
});