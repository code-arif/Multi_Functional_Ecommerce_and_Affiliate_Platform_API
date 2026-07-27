<?php

namespace Modules\Finance\Listeners;

use Modules\Orders\Events\OrderStatusUpdated;
use Modules\Finance\Services\FinanceService;
use Illuminate\Support\Facades\Log;

class CalculateCommissionOnDelivered
{
    public function __construct(private FinanceService $financeService) {}

    /**
     * Handle the event. Auto-calculate commission when order is delivered.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        if ($event->toStatus !== 'delivered') {
            return;
        }

        $order = $event->order;

        if (!$order->vendor_id) {
            return; // No vendor involved
        }

        try {
            $commission = $this->financeService->calculateCommission($order);

            if ($commission) {
                Log::info('Commission auto-calculated on delivery', [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'vendor_id'    => $order->vendor_id,
                    'amount'       => $commission->commission_amount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to auto-calculate commission on delivery', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}
