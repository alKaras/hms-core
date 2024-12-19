<?php

namespace App\Customs\Services\OrderProcessing;

use App\Enums\OrderStatusEnum;
use App\Enums\TimeslotStateEnum;
use App\Models\Cart\Cart;
use App\Models\Order\Order;
use App\Models\User\User;
use Illuminate\Contracts\Auth\Authenticatable;

class CartService
{

    public function make($user, $hospital)
    {
        return Cart::create([
            "user_id" => $user->id,
            'hospital_id' => $hospital->id,
            "session_id" => session()->getId(),
            "expired_at" => now()->addMinutes(15),
        ]);
    }

    public function addItems($cart, $timeslot)
    {
        $cart->items()->create([
            'time_slot_id' => $timeslot->id,
            'price' => $timeslot->price,
        ]);

        $timeslot->state = TimeslotStateEnum::RESERVED;
        $timeslot->save();

        return response()->json(['message' => 'Item added to cart']);
    }

    public function getUserCart($userId)
    {
        return Cart::where('user_id', $userId)->with('items')->first();
    }

    public function getExtendedUserCart($userId)
    {
        return Cart::where('user_id', $userId)->with('items.timeslot')->first();
    }

    public function createOrderFromCart(Cart $cart): Order
    {
        $sumPrice = $cart->items->sum('price');
        $totalAmount = $sumPrice + $sumPrice * PaymentService::getPaymentCommission();
        $subtotalAmount = $cart->items->sum('price');

        $order = Order::create([
            'user_id' => $cart->user_id,
            'hospital_id' => $cart->hospital_id,
            'sum_total' => $totalAmount,
            'sum_subtotal' => $subtotalAmount,
            'status' => OrderStatusEnum::PENDING, //pending
            'created_at' => now(),
            'reserve_exp' => now()->addMinutes(15),
            'updated_at' => now(),
        ]);

        foreach ($cart->items as $item) {
            $order->orderServices()->create([
                'time_slot_id' => $item->time_slot_id,
                'price' => $item->price,
                'fee' => $item->price * PaymentService::getPaymentCommission(),
            ]);
        }

        return $order;
    }

    public function clearCart(Cart $cart)
    {
        $cart->items()->delete();
        $cart->delete();
    }
}
