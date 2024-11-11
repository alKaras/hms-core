<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ReportExport;
use App\Enums\OrderFiltersEnum;
use App\Enums\ReportFiltersEnum;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Customs\Services\Feeds\OrderFeedService;
use App\Customs\Services\Feeds\ReportFeedService;
use App\Customs\Services\CriteriaCondition\CriteriaConditionService;

class FeedController extends Controller
{



    /**
     * Feed Controller constructor
     * @param CriteriaConditionService $criteriaConditionService,
     * @param OrderFeedService $orderFeedService,
     * @param ReportFeedService $reportFeedService
     */
    public function __construct(
        public CriteriaConditionService $criteriaConditionService,
        public OrderFeedService $orderFeedService,
        public ReportFeedService $reportFeedService,
    ) {
    }

    /**
     * Get Order/s by filter
     * @param Request $request {required filter [string] | session_id | order_id | doctor_id | user_id}
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOrderByFilter(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'filter' => ['required', 'string'],
            'session_id' => ['exists:order_payments,session_id'],
            'order_id' => ['exists:orders,id'],
            'doctor_id' => ['exists:doctors,id'],
            // 'user_id' => ['exists:users,id'],
            'hospital_id' => ['exists:hospital,id'],
        ]);


        $conditionRules = $this->criteriaConditionService->validateConditionRequest($request);

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $onlySold = (bool) $request->input('onlySold', default: 0);

        if ($validator->fails() || $conditionRules->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
                'criteria_errors' => $conditionRules->messages(),
            ], 422);
        }
        $limit = $request->limit ?? null;
        $filterEnum = OrderFiltersEnum::tryFrom($request->input('filter'));
        $sessionId = $request->input('session_id') ?? null;
        $orderId = $request->input('order_id') ?? null;
        $doctorId = $request->input('doctor_id') ?? null;
        $user = auth()->user();
        $hospitalId = $request->input('hospital_id') ?? null;

        $criteriaConditions = $request->input('criteriaCondition') ?? [];

        switch ($filterEnum) {
            case OrderFiltersEnum::OrdersById:
                if ($orderId !== null) {
                    return $this->orderFeedService->getOrderDataByOrderId($orderId);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Order Id is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrdersbySession:
                if ($sessionId !== null) {
                    return $this->orderFeedService->getOrderDataBySessionId($sessionId);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'SessionId is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrdersbyDoctor:
                if ($doctorId !== null) {
                    return $this->orderFeedService->getOrderDataByDoctorId($doctorId);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'DoctorId is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrdersbyUser:
                if ($user->id !== null) {
                    return $this->orderFeedService->getOrderDataByUserId($user->id, $limit, $perPage, $page, $onlySold);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'UserId is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrdersByHospital:
                if ($hospitalId !== null) {
                    return $this->orderFeedService->getOrderDataByHospitalId($hospitalId, $perPage, $page);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'HospitalId is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrderOperationsFeed:
                return $this->orderFeedService->getOrderOperationsFeed($hospitalId, $perPage, $page, $criteriaConditions);

            default:
                return response()->json([
                    'status' => 'error',
                    'error' => 'Provided filter is not recognised'
                ], 400);
        }
    }

    /**
     * GetReportByFilter method
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getReportByFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterType' => ['required', 'string'],
            'hospital_id' => ['exists:hospital,id'],
            'doctor_id' => ['exists:doctors,id'],
        ]);

        $conditionRule = $this->criteriaConditionService->validateConditionRequest($request);

        if ($validator->fails() || $conditionRule->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
                'criteria_errors' => $conditionRule->errors(),
            ], 422);
        }

        $filter = ReportFiltersEnum::tryFrom($request->input('filterType'));

        $hospitalId = $request->input('hospital_id') ?? null;

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $criteriaConditions = $request->input('criteriaCondition') ?? [];

        switch ($filter) {
            case ReportFiltersEnum::HOSPITAL_REPORT:
                if ($hospitalId !== null) {
                    return $this->reportFeedService->getReportByHospital($hospitalId, $perPage, $page, $criteriaConditions);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'HospitalId is required for this filter'
                    ], 500);
                }
            default:
                return response()->json(['status' => 'error', 'message' => 'Check provided filter'], 404);
        }
    }

    public function downloadReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $request->input('data');

        return Excel::download(new ReportExport($data), 'hospital-report.xlsx');
    }
}
