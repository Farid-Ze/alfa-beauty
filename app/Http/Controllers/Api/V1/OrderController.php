<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Order API Controller
 *
 * Handles order listing and detail operations for authenticated users.
 *
 * @package App\Http\Controllers\Api\V1
 */
class OrderController extends Controller
{
    /**
     * Display a paginated listing of the user's orders.
     *
     * @param Request $request The HTTP request with query parameters
     * @return AnonymousResourceCollection Paginated order collection
     *
     * @queryParam status string Filter by order status.
     * @queryParam payment_status string Filter by payment status.
     * @queryParam per_page integer Items per page (max 50). Default: 15.
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
     *
     * @param Request $request The HTTP request with authenticated user
     * @param Order $order The order model (auto-resolved)
     * @return OrderResource The order resource with items and payment logs
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 if order doesn't belong to user
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
