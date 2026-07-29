<?php

namespace Modules\Support\Http\Controllers;

use Modules\AdminPanel\Models\Dispute;
use Modules\AdminPanel\Http\Resources\DisputeResource;
use Modules\Support\Http\Requests\StoreDisputeRequest;
use Modules\Orders\Models\Order;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDisputeController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $disputes = Dispute::with(['order', 'vendor'])
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(DisputeResource::collection($disputes));
    }

    public function store(StoreDisputeRequest $request): JsonResponse
    {
        $order = Order::findOrFail($request->order_id);

        if ($order->user_id !== $request->user()->id) {
            return $this->errorResponse('Order does not belong to you.', null, 403);
        }

        $dispute = Dispute::create([
            'order_id'    => $request->order_id,
            'customer_id' => $request->user()->id,
            'vendor_id'   => $order->vendor_id,
            'subject'     => $request->subject,
            'description' => $request->description,
            'status'      => 'open',
        ]);

        return $this->createdResponse(
            new DisputeResource($dispute->load(['order', 'vendor'])),
            'Dispute submitted. An admin will review it shortly.'
        );
    }

    public function show(Dispute $dispute, Request $request): JsonResponse
    {
        if ($dispute->customer_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $dispute->load(['order.items', 'vendor', 'messages.user']);
        return $this->successResponse(new DisputeResource($dispute));
    }
}
