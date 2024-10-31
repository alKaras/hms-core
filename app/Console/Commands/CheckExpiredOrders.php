<?php

namespace App\Console\Commands;

use App\Enums\TimeslotStateEnum;
use Carbon\Carbon;
use App\Models\TimeSlots;
use App\Models\Order\Order;
use App\Events\OrderExpired;
use Illuminate\Console\Command;

class CheckExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired orders and update their status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOrders = Order::where('status', '=', 1)
            ->where('confirmed_at', '=', null)
            ->where('reserve_exp', '<', now()->subMinutes(10))
            ->get();

        foreach ($expiredOrders as $order) {
            event(new OrderExpired($order));

            $order->update([
                'cancelled_at' => now(),
                'status' => 3,
                'cancel_reason' => 'Order Expired',
            ]);

            foreach ($order->orderServices as $service) {
                $timeSlot = TimeSlots::find($service->time_slot_id);

                if ($timeSlot) {
                    $timeSlot->state = TimeslotStateEnum::FREE;
                    $timeSlot->save();
                }
            }

            $order->orderServices()->update([
                'is_canceled' => 1,
                'updated_at' => now(),
            ]);
        }

        $this->info('Checked for expired orders and updated their status.');
    }
}
