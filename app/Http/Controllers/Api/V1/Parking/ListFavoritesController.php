<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\ListFavorites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Parking\ListFavoritesRequest;
use App\Http\Resources\FavoriteResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListFavoritesController extends Controller
{
    public function __invoke(
        ListFavoritesRequest $request,
        ListFavorites $listFavorites,
    ): AnonymousResourceCollection {
        return FavoriteResource::collection(
            $listFavorites->execute(
                userId: $request->user()->id,
                perPage: $request->perPage(),
                page: $request->page(),
            ),
        );
    }
}
