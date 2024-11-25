<?php

namespace App\Customs\Services\Feeds\Order;

use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderServiceResource;

class OrderResponderService
{
    public function formatOrderWithServices($order, $orderServices)
    {
        return response()->json([
            'order' => new OrderResource($order),
            'order_services' => OrderServiceResource::collection($orderServices),
        ]);
    }

    public function formatError($message, $statusCode = 404)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $statusCode);
    }

    public function formatPaginatedOrders($paginatedOrders)
    {
        return response()->json([
            'status' => 'ok',
            'data' => $paginatedOrders->items(),
            'meta' => [
                'current_page' => $paginatedOrders->currentPage(),
                'per_page' => $paginatedOrders->perPage(),
                'total' => $paginatedOrders->total(),
                'last_page' => $paginatedOrders->lastPage(),
            ],
            'links' => [
                'first' => $paginatedOrders->url(1),
                'last' => $paginatedOrders->url($paginatedOrders->lastPage()),
                'prev' => $paginatedOrders->previousPageUrl(),
                'next' => $paginatedOrders->nextPageUrl(),
            ],
        ]);
    }
}
