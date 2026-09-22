<?php

namespace App\Http\Resources;

use App\Data\Parking\ParkingIdentifier;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FavoriteResource extends JsonResource
{
    public function toArray(
        Request $request,
    ): array {
        /** @var Favorite $favorite */
        $favorite = $this->resource;

        return [
            'id' => $favorite->id,

            'parking' => [
                'id' => ParkingIdentifier::for(
                    $favorite->favorable,
                ),

                'type' => ParkingIdentifier::type(
                    $favorite->favorable,
                ),

                'name' => $favorite->favorable->name,
            ],

            'created_at' => $favorite->created_at,
        ];
    }
}