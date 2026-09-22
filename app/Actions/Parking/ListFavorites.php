<?php

namespace App\Actions\Parking;

use App\Models\Favorite;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListFavorites
{
    public function execute(
        int $userId,
        int $perPage = 20,
        int $page = 1,
    ): LengthAwarePaginator {
        return Favorite::query()
            ->with('favorable')
            ->where('user_id', $userId)
            ->latest('created_at')
            ->latest('id')
            ->paginate(
                perPage: $perPage,
                page: $page,
            );
    }
}