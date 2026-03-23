<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f4f4;
            color: #333;
        }

        .wrapper {
            max-width: 620px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            padding: 32px 40px;
            text-align: center;
        }

        .header h1 {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
        }

        .header p {
            color: rgba(255, 255, 255, 0.85);
            margin-top: 6px;
            font-size: 14px;
        }

        .body {
            padding: 36px 40px;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .intro {
            color: #555;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .order-box {
            background: #f9f9f9;
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 28px;
        }

        .order-number {
            font-size: 20px;
            font-weight: 700;
            color: #2E7D32;
        }

        .order-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }

        .meta-item label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            display: block;
        }

        .meta-item span {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 14px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }

        .item-row {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-img {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            background: #e8f5e9;
            object-fit: cover;
            margin-right: 14px;
            flex-shrink: 0;
        }

        .item-name {
            flex: 1;
            font-size: 14px;
            font-weight: 500;
        }

        .item-variant {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        .item-price {
            font-size: 14px;
            font-weight: 700;
            color: #2E7D32;
        }

        .totals-table {
            width: 100%;
            margin-top: 16px;
        }

        .totals-table tr td {
            padding: 6px 0;
            font-size: 14px;
        }

        .totals-table tr td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .totals-table .grand-total td {
            font-size: 16px;
            font-weight: 700;
            color: #2E7D32;
            border-top: 2px solid #e8f5e9;
            padding-top: 12px;
        }

        .address-box {
            background: #f9f9f9;
            border-radius: 6px;
            padding: 16px;
            font-size: 14px;
            line-height: 1.7;
            color: #444;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            color: #fff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
            margin: 24px 0;
        }

        .footer {
            background: #f4f4f4;
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #999;
        }

        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }

        .badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2E7D32;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Header -->
        <div class="header">
            <h1>✅ Order Confirmed!</h1>
            <p>Thank you for shopping with {{ config('ecommerce.store_name', 'EcoShop') }}</p>
        </div>

        <!-- Body -->
        <div class="body">
            <p class="greeting">Hello, {{ $order->shipping_name }}!</p>
            <p class="intro">
                Your order has been received and is being processed. We'll send you another
                email as soon as your order ships.
            </p>

            <!-- Order Box -->
            <div class="order-box">
                <div class="order-number">#{{ $order->order_number }}</div>
                <div class="order-meta">
                    <div class="meta-item">
                        <label>Order Date</label>
                        <span>{{ $order->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <label>Payment Method</label>
                        <span>{{ strtoupper($order->payment_method) }}</span>
                    </div>
                    <div class="meta-item">
                        <label>Status</label>
                        <span class="badge">{{ ucfirst($order->status) }}</span>
                    </div>
                    <div class="meta-item">
                        <label>Total Amount</label>
                        <span>৳{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <p class="section-title">Items Ordered</p>
            @foreach ($order->items as $item)
                <div class="item-row">
                    @if ($item->product_image)
                        <img class="item-img" src="{{ asset('storage/' . $item->product_image) }}"
                            alt="{{ $item->product_name }}">
                    @else
                        <div class="item-img"></div>
                    @endif
                    <div style="flex:1">
                        <div class="item-name">{{ $item->product_name }}</div>
                        @if ($item->variant_attributes)
                            <div class="item-variant">
                                {{ collect($item->variant_attributes)->map(fn($v, $k) => "$k: $v")->implode(', ') }}
                            </div>
                        @endif
                        <div class="item-variant">Qty: {{ $item->quantity }}</div>
                    </div>
                    <div class="item-price">৳{{ number_format($item->subtotal, 2) }}</div>
                </div>
            @endforeach

            <!-- Totals -->
            <table class="totals-table">
                <tr>
                    <td>Subtotal</td>
                    <td>৳{{ number_format($order->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>Shipping</td>
                    <td>{{ $order->shipping_charge > 0 ? '৳' . number_format($order->shipping_charge, 2) : 'Free' }}</td>
                </tr>
                @if ($order->discount_amount > 0)
                    <tr>
                        <td>Discount @if ($order->coupon_code)
                                ({{ $order->coupon_code }})
                            @endif
                        </td>
                        <td>-৳{{ number_format($order->discount_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td>Total</td>
                    <td>৳{{ number_format($order->total_amount, 2) }}</td>
                </tr>
            </table>

            <!-- Shipping Address -->
            <p class="section-title" style="margin-top:28px">Shipping Address</p>
            <div class="address-box">
                <strong>{{ $order->shipping_name }}</strong><br>
                {{ $order->shipping_address_line1 }}<br>
                @if ($order->shipping_address_line2)
                    {{ $order->shipping_address_line2 }}<br>
                @endif
                {{ $order->shipping_city }}@if ($order->shipping_state)
                    , {{ $order->shipping_state }}
                @endif
                <br>
                {{ $order->shipping_country }}<br>
                📞 {{ $order->shipping_phone }}
            </div>

            <!-- CTA -->
            <div style="text-align:center">
                <a class="btn"
                    href="{{ config('app.frontend_url', 'http://localhost:3000') }}/orders/{{ $order->order_number }}">
                    Track Your Order →
                </a>
            </div>

            <p style="font-size:13px;color:#888;text-align:center">
                If you have any questions, reply to this email or contact us at
                <a href="mailto:{{ config('ecommerce.store_email') }}" style="color:#2E7D32">
                    {{ config('ecommerce.store_email') }}
                </a>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('ecommerce.store_name', 'EcoShop') }}. All rights reserved.</p>
            <p style="margin-top:6px">
                <a href="#">Privacy Policy</a> &nbsp;·&nbsp;
                <a href="#">Terms of Service</a> &nbsp;·&nbsp;
                <a href="#">Unsubscribe</a>
            </p>
        </div>
    </div>
</body>

</html>
