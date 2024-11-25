<?php

namespace App\Http\Controllers;

use App\Customs\Services\OrderProcessing\OrderProcessingService;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    /**
     * OrderController construct
     * @param \App\Customs\Services\OrderProcessing\OrderProcessingService $orderProcessingService
     */
    public function __construct(
        private OrderProcessingService $orderProcessingService,
    )
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
