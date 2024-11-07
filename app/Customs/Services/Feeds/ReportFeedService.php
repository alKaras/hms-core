<?php

namespace App\Customs\Services\Feeds;

use App\Models\Hospital\Hospital;
use Illuminate\Support\Facades\DB;
use App\Customs\Services\CriteriaCondition\CriteriaConditionService;

class ReportFeedService
{

    public function __construct(public CriteriaConditionService $criteriaConditionService)
    {
    }

    public function getReportByHospital($hospitalId, $perPage, $page, $criteriaConditions = [])
    {
        $hospital = Hospital::find($hospitalId);

        $generalQuery = DB::table('orders as o')
            ->leftJoin('order_services as os', 'o.id', '=', 'os.order_id')
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
            ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
            ->leftJoin('hospital_services as hs', 'hs.service_id', '=', 's.id')
            ->leftJoin('hospital as h', 'h.id', '=', 'hs.hospital_id')
            ->leftJoin('hospital_content as hc', 'hc.hospital_id', '=', 'h.id')
            ->where('o.status', '=', 2)
            ->where('hs.hospital_id', '=', $hospital->id)
            ->when($criteriaConditions, function ($query) use ($criteriaConditions) {
                return $this->criteriaConditionService->applyConditions($query, $criteriaConditions);
            })
            ->groupBy('hs.hospital_id', 'hc.title', 'hc.address')
            ->selectRaw("
                hs.hospital_id as `hospitalId`,
                hc.title as `hospitalTitle`,
                hc.address as `hospitalAddress`,
                COUNT(os.id) as `totalServiceQuantity`,
                SUM(o.sum_subtotal) as `totalSum`,
                COUNT(os.id) / NULLIF(COUNT(DISTINCT DATE(o.confirmed_at)), 0) as `averageServicesPerDay`
            ")
            ->get();


        $hospitalServicesQuery = DB::table('orders as o')
            ->leftJoin('order_services as os', 'o.id', '=', 'os.order_id')
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
            ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
            ->leftJoin('hospital_services as hs', 'hs.service_id', '=', 's.id')
            ->leftJoin('hospital as h', 'h.id', '=', 'hs.hospital_id')
            ->where('o.status', '=', 2)
            ->where('hs.hospital_id', '=', $hospital->id)
            ->when($criteriaConditions, function ($query) use ($criteriaConditions) {
                return $this->criteriaConditionService->applyConditions($query, $criteriaConditions);
            })
            ->groupBy('s.id', 's.name')
            ->selectRaw("
                s.id as `serviceId`,
                s.name as `serviceName`,
                COUNT(os.id) as `serviceQuantity`,
                SUM(os.price) as `serviceTotalSum`
            ")
            ->paginate($perPage, ["*"], "page", $page);

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