<?php

namespace Modules\Orders\Services;

use Modules\Orders\Models\Invoice;
use Modules\Orders\Models\Order;

class InvoiceService
{
    /**
     * Generate an invoice for an order (idempotent).
     */
    public function generateFor(Order $order): Invoice
    {
        if ($order->relationLoaded('invoice') && $order->invoice) {
            return $order->invoice;
        }

        $existing = Invoice::where('order_id', $order->id)->first();

        if ($existing) {
            return $existing;
        }

        $invoice = $order->invoice()->create([
            'invoice_number'   => Invoice::generateInvoiceNumber(),
            'subtotal'         => $order->subtotal,
            'shipping_charge'  => $order->shipping_charge,
            'discount_amount'  => $order->discount_amount,
            'tax_amount'       => $order->tax_amount,
            'total_amount'     => $order->total_amount,
            'status'           => 'issued',
            'issued_at'        => now(),
        ]);

        return $invoice->fresh();
    }

    /**
     * Keep the invoice in sync when the order status / payment changes.
     */
    public function syncWithOrder(Order $order): void
    {
        $invoice = $order->invoice ?? $this->generateFor($order);

        $data = [
            'subtotal'        => $order->subtotal,
            'shipping_charge' => $order->shipping_charge,
            'discount_amount' => $order->discount_amount,
            'tax_amount'      => $order->tax_amount,
            'total_amount'    => $order->total_amount,
        ];

        if ($order->payment_status === 'paid' && $invoice->status !== 'paid') {
            $data['status']   = 'paid';
            $data['paid_at']  = $order->paid_at ?? now();
        }

        if ($order->status === 'cancelled' && $invoice->status !== 'void') {
            $data['status'] = 'void';
        }

        $invoice->update($data);
    }
}
