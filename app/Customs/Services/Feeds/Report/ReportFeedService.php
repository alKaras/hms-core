<?php

namespace App\Customs\Services\Feeds\Report;

use App\Models\Hospital\Hospital;

class ReportFeedService
{

    public function __construct(
        public ReportQueryService $reportQueryService
    )
    {
    }

    public function getReportByHospital($hospitalId, $perPage, $page, $criteriaConditions = [])
    {
        $hospital = Hospital::find($hospitalId);

        $generalQuery = $this->reportQueryService->findGeneralHospitalReport($hospital->id, $criteriaConditions);


        $hospitalServicesQuery = $this->reportQueryService->findDetailedHospitalReport($hospitalId, $perPage, $page, $criteriaConditions);

        $hospitalServicesQuery->getCollection()->transform(function ($single) {
            return [
                'serviceId' => $single->serviceId,
                'serviceName' => $single->serviceName,
                'quantity' => $single->serviceQuantity,
                'serviceTotalSum' => $single->serviceTotalSum,
            ];
        });

        return response()->json([
            'status' => 'ok',
            'general' => $generalQuery,
            'detailed' => $hospitalServicesQuery->items(),
        ]);

    }
}
