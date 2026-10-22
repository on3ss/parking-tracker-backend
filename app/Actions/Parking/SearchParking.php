<?php

namespace App\Actions\Parking;

use App\Data\Parking\SearchParkingData;
use Illuminate\Pagination\LengthAwarePaginator;

final class SearchParking
{
    public function __construct(
        private ParkingSearchQueryBuilder $queryBuilder,
        private ParkingSearchResultHydrator $resultHydrator,
    ) {
    }

    public function execute(
        SearchParkingData $data,
    ): LengthAwarePaginator {
        $page = $this->queryBuilder
            ->build($data)
            ->paginate(
                perPage: $data->perPage,
                page: $data->page,
            );

        return $page->setCollection(
            $this->resultHydrator->hydrate(
                $page->getCollection(),
            ),
        );
    }
}
