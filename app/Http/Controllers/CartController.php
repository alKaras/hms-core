<?php

namespace App\Http\Controllers;

use App\Enums\TimeslotStateEnum;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItems;
use App\Models\TimeSlots;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $user = auth()->user();
        $userRecord = User::find($user->id);

        if ($userRecord && $userRecord->email_verified_at !== null) {
            $cart = Cart::where("user_id", $user->id)->first();

            if (!$cart) {
                $cart = Cart::create([
                    "user_id" => $user->id,
                    "session_id" => session()->getId(),
                    "expired_at" => now()->addMinutes(15),
                ]);

                $timeSlot = TimeSlots::find($request->time_slot_id);
                if (!$timeSlot) {
                    return response()->json(['message' => 'No timeslot for provided id'], 404);
                } elseif ($timeSlot->state === TimeslotStateEnum::RESERVED || $timeSlot->state === TimeslotStateEnum::SOLD) {
                    return response()->json(['message' => 'Cannot create shopping cart. TimeSlot is already reserved or sold'], 500);
                }


                $cart->items()->create([
                    'time_slot_id' => $timeSlot->id,
                    'price' => $timeSlot->price,
                ]);

                $timeSlot->state = TimeslotStateEnum::RESERVED;
                $timeSlot->save();

                return response()->json(['message' => 'Item added to cart']);

            } else {
                $existingItem = $cart->items()->where('time_slot_id', $request->time_slot_id)->first();
                if ($existingItem) {
                    return response()->json(['message' => 'TimeSlot already in cart'], 400);
                }

                $timeSlot = TimeSlots::find($request->time_slot_id);

                if (!$timeSlot) {
                    return response()->json(['message' => 'No timeslot for provided id'], 404);
                } elseif ($timeSlot->state === TimeslotStateEnum::RESERVED || $timeSlot->state === TimeslotStateEnum::SOLD) {
                    return response()->json(['message' => 'Cannot create shopping cart. TimeSlot is already reserved or sold'], 500);
                }

                $cart->items()->create([
                    'time_slot_id' => $timeSlot->id,
                    'price' => $timeSlot->price,
                ]);

                $timeSlot->state = TimeslotStateEnum::RESERVED;
                $timeSlot->save();

                return response()->json(['message' => 'Item added to cart']);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'error' => 'Access denied',
                'message' => 'Provided user is not verified'
            ], 403);
        }

    }

    public function getCart()
    {
        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->with('items.timeslot')->first();

        if (empty($cart)) {
            return response()->json(['message' => 'Cart is empty'], 404);
        }
        return response()->json([
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'session_id' => $cart->session_id,
            'created_at' => Carbon::parse($cart->created_at),
            // 'items' => $cart->items,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'cart_id' => $item->cart_id,
                    'service' => [
                        'time_slot_id' => $item->time_slot_id,
                        'service_id' => TimeSlots::find($item->time_slot_id)->service->id,
                        'name' => TimeSlots::find($item->time_slot_id)->service->name,
                        'department' => TimeSlots::find($item->time_slot_id)->service->department->content->title,
                        'start_time' => TimeSlots::find($item->time_slot_id)->start_time,
                        'state' => TimeSlots::find($item->time_slot_id)->state,
                    ],
                    'price' => $item->price,
                ];
            })

        ]);
    }

    public function removeItem($itemId)
    {
        $cartItem = CartItems::find($itemId);
        $timeSlot = TimeSlots::find($cartItem->time_slot_id);

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in the cart'], 404);
        }

        $cartItem->delete();

        $timeSlot->state = TimeslotStateEnum::FREE;
        $timeSlot->save();
        return response()->json(['message' => 'Item removed successfully']);
    }

    public function cancelCart($id)
    {
        $cart = Cart::find($id);
        if (!$cart) {
            return response()->json(['message' => 'Cart is not found'], 404);
        }

        foreach ($cart->items as $item) {
            $timeSlot = TimeSlots::find($item->time_slot_id);

            if ($timeSlot) {
                $timeSlot->state = TimeslotStateEnum::FREE;
                $timeSlot->save();
            }

        }


        $cart->items()->delete();
        $cart->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cart deleted successfully',
        ]);
    }
}
