<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Cart;
use App\Models\User;
use App\Models\CartItems;
use App\Models\TimeSlots;
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
                }

                $cart->items()->create([
                    'time_slot_id' => $timeSlot->id,
                    'price' => $timeSlot->price,
                ]);

                return response()->json(['message' => 'Item added to cart']);

            } else {
                $existingItem = $cart->items()->where('time_slot_id', $request->time_slot_id)->first();
                if ($existingItem) {
                    return response()->json(['message' => 'TimeSlot already in cart'], 400);
                }

                $timeSlot = TimeSlots::find($request->time_slot_id);
                if (!$timeSlot) {
                    return response()->json(['message' => 'No timeslot for provided id'], 404);
                }

                $cart->items()->create([
                    'time_slot_id' => $timeSlot->id,
                    'price' => $timeSlot->price,
                ]);

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
            return response()->json(['message' => 'Cart is empty', 'data' => []]);
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
                    ],
                    'price' => $item->price,
                ];
            })

        ]);
    }

    public function removeItem($itemId)
    {
        $cartItem = CartItems::find($itemId);

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in the cart'], 404);
        }

        $cartItem->delete();
        return response()->json(['message' => 'Item removed successfully']);
    }

    public function cancelCart($id)
    {
        $cart = Cart::find($id);
        if (!$cart) {
            return response()->json(['message' => 'Cart is not found'], 404);
        }

        $cart->items()->delete();
        $cart->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cart deleted successfully',
        ]);
    }
}
