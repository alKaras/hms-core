<?php

namespace App\Http\Controllers;

use App\Customs\Services\OrderProcessingService;
use App\Enums\OrderFiltersEnum;
use App\Enums\TimeslotStateEnum;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderServiceResource;
use App\Models\Cart\Cart;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Models\Order\OrderPaymentLog;
use App\Models\Order\OrderServices;
use App\Models\TimeSlots;
use App\Models\User\User;
use App\Notifications\TimeSlotConfirmationNotification;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;

class OrderController extends Controller
{

    /**
     * OrderController construct
     * @param \App\Customs\Services\OrderProcessingService $orderProcessingService
     */
    public function __construct(public OrderProcessingService $orderProcessingService)
    {
    }
    /**
     * Order checkout method
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function checkout(Request $request)
    {
        return $this->orderProcessingService->checkoutProcessing();
    }

    /**
     * Stripe webhook handler
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function stripeHookHandler(Request $request)
    {
        return $this->orderProcessingService->stripeHookProcessing($request);

    }

    /**
     * Send confirmation email for order
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function sendOrderConfirmationMail(Request $request)
    {
        return $this->orderProcessingService->sendConfirmation($request);
    }

    /**
     * Cancel order method | Cancel order when user navigate to checkout/payment/cancel
     */
    public function cancel(Request $request)
    {
        return $this->orderProcessingService->cancelProcessing($request);
    }
}
