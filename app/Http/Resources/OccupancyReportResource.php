<?php

namespace App\Http\Resources;

use App\Models\OccupancyReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OccupancyReportResource extends JsonResource
{
    public function toArray(
        Request $request,
    ): array {
        /** @var OccupancyReport $report */
        $report = $this->resource;

        return [
            'available_spaces' => $report->available_spaces,

            'occupied_spaces' => $report->occupied_spaces,

            'source' => $report->source->value,

            'confidence' => $report->confidence !== null
                ? (float) $report->confidence
                : null,

            'reported_at' => $report->reported_at,
        ];
    }
}
