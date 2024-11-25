<?php

namespace App\Customs\Services\Feeds\Report;

use App\Customs\Services\CriteriaCondition\CriteriaConditionService;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\DB;

class ReportQueryService
{
    public function __construct(
        private CriteriaConditionService $criteriaConditionService
    )
    {
    }

    public function findGeneralHospitalReport($hospitalId, $criteriaConditions = [])
    {
        return DB::table('orders as o')
            ->leftJoin('order_services as os', 'o.id', '=', 'os.order_id')
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
            ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
            // ->leftJoin('hospital_services as hs', 'hs.service_id', '=', 's.id')
            ->leftJoin('hospital as h', 'h.id', '=', 'o.hospital_id')
            ->leftJoin('hospital_content as hc', 'hc.hospital_id', '=', 'h.id')
            ->where('o.status', '=', OrderStatusEnum::SOLD)
            ->where('o.hospital_id', '=', $hospitalId)
            ->when($criteriaConditions, function ($query) use ($criteriaConditions) {
                return $this->criteriaConditionService->applyConditions($query, $criteriaConditions);
            })
            ->groupBy('o.hospital_id', 'hc.title', 'hc.address')
            ->selectRaw("
                o.hospital_id as `hospitalId`,
                hc.title as `hospitalTitle`,
                hc.address as `hospitalAddress`,
                COUNT(os.id) as `totalServiceQuantity`,
                SUM(o.sum_subtotal) as `totalSum`,
                COUNT(os.id) / NULLIF(COUNT(DISTINCT DATE(o.confirmed_at)), 0) as `averageServicesPerDay`
            ")
            ->get();
    }

    public function findDetailedHospitalReport($hospitalId, $perPage, $page, $criteriaConditions = [])
    {
        return DB::table('orders as o')
            ->leftJoin('order_services as os', 'o.id', '=', 'os.order_id')
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
            ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
            ->leftJoin('hospital as h', 'h.id', '=', 'o.hospital_id')
            ->where('o.status', '=', OrderStatusEnum::SOLD)
            ->where('o.hospital_id', '=', $hospitalId)
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
    }
}
