<?php

namespace App\Customs\Services\OrderProcessing;

use App\Customs\Services\AppointmentService\AppointmentService;
use App\Customs\Services\NotificationService\NotificationService;
use App\Enums\OrderStatusEnum;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Models\Order\OrderPaymentLog;
use App\Models\User\User;
use Illuminate\Support\Carbon;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentService
{
    private const ORDER_PAYMENT_COMMISSION = 0.1;

    public static function getPaymentCommission()
    {
        return self::ORDER_PAYMENT_COMMISSION;
    }

    /**
     * Create payment session method
     * @param Order $order
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createPaymentSession(Order $order)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $lineItems = $this->prepareLineItems($order);
        if ($order->sum_total > 0){
            $session = \Stripe\Checkout\Session::create([
                'customer' => User::find($order->user_id)->stripe_customer_id ?? null,
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => env("REACT_APP_URL") . "/checkout/payment/success?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => env("REACT_APP_URL") . "/checkout/payment/cancel?session_id={CHECKOUT_SESSION_ID}",
            ]);

            $this->logCheckoutPaymentSession($session, $order);

            return true;
        }
        return false;
    }

    public function prepareSessionRespond(Order $order)
    {
        $orderPayment = OrderPayment::where('order_id', $order->id)->first();
        $stripePaymentSession = Session::retrieve($orderPayment->session_id);

        return response()->json([
            'session_id' => $stripePaymentSession->id,
            'session_url' => $stripePaymentSession->url,
            'customer' => $session->customer ?? null,
        ]);
    }

    public function stripeWebhookHandler($payload, $sig_header, $endpoint_secret)
    {
        $event = $this->verifyStripeWebhook($payload, $sig_header, $endpoint_secret);

        if (!$event) {
            return response()->json(['message' => 'Invalid webhook signature or payload'], 400);
        }

        switch ($event->type){
            case 'checkout.session.completed':
                $session = $event->data->object;
                $orderPayment = OrderPayment::where('session_id', $session->id)->first();

                if ($orderPayment) {
                    $order = Order::find($orderPayment->order_id);
                    app(OrderService::class)->confirmOrder($order);
                    $this->logPaymentOnConfirmation($orderPayment, $session);
                    app(AppointmentService::class)->makeAppointmentOnConfirmation($order);
                    app(NotificationService::class)->sendOrderConfirmation($order);

                }
                break;

            default:
                return response()->json(['message' => 'Unhandled event type'],400);
        }
        return response()->json(['message' => 'Webhook handled successfully']);
    }

    private function logPaymentOnConfirmation(OrderPayment $orderPayment, $session)
    {
        OrderPaymentLog::create([
            'order_payment_id' => $orderPayment->id,
            'event' => 'payment_success',
            'attributes' => json_encode($session) ?? json_encode('[]'),
        ]);

        $orderPayment->update([
            'payment_id' => $session->payment_intent,
            'updated_at' => now(),
        ]);
    }

    public function logPaymentOnCancellation(Order $order)
    {
        $orderPayment = OrderPayment::where('order_id', $order->id);
        if ($order->status === OrderStatusEnum::PENDING && $order->confirmed_at === null) {
            $orderPayment->paymentLogs()->create([
                'order_payment_id' => $orderPayment->id,
                'event' => 'payment_canceled',
                'attributes' => json_encode([
                    "code" => "payment_declined",
                    "status" => "failure",
                    "err_description" => "Failed to proceed payment. Check your parameters"
                ]),
                'updated_at' => now(),
            ]);
        }
    }


    private function prepareLineItems(Order $order)
    {
        $lineItems = [];

        foreach ($order->orderServices as $service) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'uah',
                    'product_data' => [
                        'name' => $service->timeSlot->service->name,
                    ],
                    'unit_amount' => ($service->price + $service->fee) * 100,
                ],
                'quantity' => 1,
            ];
        }

        return $lineItems;

    }

    private function logCheckoutPaymentSession($session, $order)
    {
        $orderPayment = OrderPayment::create([
            'order_id' => $order->id,
            'session_id' => $session->id,
        ]);

        if ($orderPayment){
            OrderPaymentLog::create([
                'order_payment_id' => $orderPayment->id,
                'event' => 'payment_created',
                'attributes' => '{}'
            ]);

            OrderPaymentLog::insert([
                'order_payment_id' => $orderPayment->id,
                'event' => $session->object,
                'attributes' => json_encode($session) ?? json_encode('[]'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }

    private function verifyStripeWebhook($payload, $sigHeader, $endpointSecret)
    {
        try {
            return Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException  | SignatureVerificationException $e) {
            return null;
        }
    }


}
