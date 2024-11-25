<?php

namespace App\Customs\Services\OrderProcessing;

use App\Customs\Services\AppointmentService\AppointmentService;
use App\Customs\Services\NotificationService\NotificationService;
use Illuminate\Http\Request;

class OrderProcessingService
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private PaymentService $paymentService,
        private AppointmentService $appointmentService,
        private NotificationService $notificationService
    )
    {
    }

    /**
     * Order checkout action method
     * @return \Illuminate\Http\JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function checkoutProcessing()
    {
        $user = auth()->user();

        $cart = $this->cartService->getUserCart($user->id);
        ;
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 404);
        }

        $order = $this->cartService->createOrderFromCart($cart);

        if ($this->paymentService->createPaymentSession($order))
        {
            $this->cartService->clearCart($cart);
            return $this->paymentService->prepareSessionRespond($order);
        } else {
            $this->cartService->clearCart($cart);
            $this->orderService->confirmOrder($order);
            $this->appointmentService->makeAppointmentOnConfirmation($order);
            $this->notificationService->sendOrderConfirmation($order);

            return response()->json([
                'status' => 'ok',
                'success_url' => env("REACT_APP_URL") . "/checkout/payment/success?order_id={$order->id}",
                'order' => $order,
            ]);
        }
    }

    /**
     * Stripe webhook processing method
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stripeHookProcessing(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        return $this->paymentService->stripeWebhookHandler($payload, $sig_header, $endpoint_secret);
    }

    /**
     * Cancel processing method
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelProcessing(Request $request)
    {
        if ($request->session_id) {
            $order = $this->orderService->getOrderBy(['session_id' => $request->session_id]);
        } elseif ($request->order_id) {
            $order = $this->orderService->getOrderBy(['order_id' => $request->order_id]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No provided data'
            ], 404);
        }

        $this->paymentService->logPaymentOnCancellation($order);

        $this->orderService->cancelOrder($order);

        return response()->json([
            'status' => 'ok',
            'message' => 'Order canceled successfully',
        ]);
    }

    /**
     * Send confirmation mail action method
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendConfirmation(Request $request)
    {
        $order = $this->orderService->getOrderBy(['order_id' => $request->order_id]);

        if ($order) {
            $this->notificationService->sendOrderConfirmation($order);
            return response()->json([
                'status' => 'ok',
                'message' => 'Order confirmation sent successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no data for provided orderId'
            ]);
        }
    }
}
