<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->withCount('items');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        // Sorting (default: newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 15), 50);

        return OrderResource::collection($query->paginate($perPage));
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, Order $order): OrderResource
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'This order does not belong to you.');
        }

        $order->load(['items.product.brand', 'paymentLogs']);

        return new OrderResource($order);
    }
}
