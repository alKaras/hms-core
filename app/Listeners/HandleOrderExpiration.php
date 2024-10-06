<?php

namespace App\Listeners;

use App\Events\OrderExpired;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use IlLuminate\Support\Facades\Log;

class HandleOrderExpiration
{

    /**
     * Handle the event.
     */
    public function handle(OrderExpired $event): void
    {
        Log::info('Order has expired', ['order' => $event->order->id]);
    }
}
