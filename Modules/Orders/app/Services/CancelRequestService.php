<?php

namespace Modules\Orders\Services;

use App\Models\User;
use Modules\Orders\Models\CancelRequest;
use Modules\Orders\Models\Order;

class CancelRequestService
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * Customer submits a cancellation request for their order.
     */
    public function request(Order $order, User $user, string $reason): CancelRequest
    {
        if (!$order->is_guest_order && $order->user_id !== $user->id) {
            throw new \DomainException('This order does not belong to you.');
        }

        if (!$order->can_be_cancelled) {
            throw new \DomainException('This order can no longer be cancelled.');
        }

        if ($order->cancelRequests()->where('status', 'pending')->exists()) {
            throw new \DomainException('A cancellation request is already pending for this order.');
        }

        return $order->cancelRequests()->create([
            'user_id' => $user->id,
            'reason'  => $reason,
            'status'  => 'pending',
        ]);
    }

    /**
     * Admin (or Super Admin) approves / rejects a pending cancellation request.
     */
    public function review(CancelRequest $cancelRequest, string $status, ?string $response, ?User $actor): CancelRequest
    {
        if ($cancelRequest->status !== 'pending') {
            throw new \DomainException('This cancellation request has already been reviewed.');
        }

        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new \InvalidArgumentException('Invalid review status.');
        }

        $cancelRequest->update([
            'status'         => $status,
            'admin_response' => $response,
            'reviewed_by'    => $actor?->id,
            'reviewed_at'    => now(),
        ]);

        if ($status === 'approved') {
            $this->orderService->cancelOrder(
                $cancelRequest->order,
                'Cancellation approved: ' . ($response ?: $cancelRequest->reason),
                $actor,
                'admin'
            );
        }

        return $cancelRequest->fresh(['order']);
    }
}
