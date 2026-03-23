<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Shipped</title>
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
            background: linear-gradient(135deg, #1565C0, #42A5F5);
            padding: 32px 40px;
            text-align: center;
        }

        .header h1 {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
        }

        .body {
            padding: 36px 40px;
        }

        .tracking-box {
            background: #E3F2FD;
            border: 1px solid #BBDEFB;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }

        .tracking-number {
            font-size: 22px;
            font-weight: 700;
            color: #1565C0;
            letter-spacing: 2px;
        }

        .carrier {
            color: #555;
            font-size: 14px;
            margin-top: 4px;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin: 28px 0;
        }

        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }

        .step::after {
            content: '';
            position: absolute;
            top: 14px;
            left: 60%;
            width: 80%;
            height: 2px;
            background: #ddd;
        }

        .step:last-child::after {
            display: none;
        }

        .step-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #4CAF50;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .step-circle.inactive {
            background: #ddd;
            color: #999;
        }

        .step-label {
            font-size: 11px;
            margin-top: 6px;
            color: #555;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #1565C0, #42A5F5);
            color: #fff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
            margin-top: 20px;
        }

        .footer {
            background: #f4f4f4;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <h1>🚚 Your Order is On Its Way!</h1>
        </div>
        <div class="body">
            <p style="font-size:16px;margin-bottom:16px">Hello, <strong>{{ $order->shipping_name }}</strong>!</p>
            <p style="color:#555;line-height:1.6">
                Great news! Your order <strong>#{{ $order->order_number }}</strong> has been shipped
                and is on its way to you.
            </p>

            <!-- Tracking -->
            @if ($order->tracking_number)
                <div class="tracking-box">
                    <p style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#888;margin-bottom:8px">
                        Tracking Number</p>
                    <div class="tracking-number">{{ $order->tracking_number }}</div>
                    @if ($order->shipping_carrier)
                        <div class="carrier">via {{ $order->shipping_carrier }}</div>
                    @endif
                </div>
            @endif

            <!-- Progress Steps -->
            <div class="steps">
                <div class="step">
                    <div class="step-circle">✓</div>
                    <div class="step-label">Ordered</div>
                </div>
                <div class="step">
                    <div class="step-circle">✓</div>
                    <div class="step-label">Confirmed</div>
                </div>
                <div class="step">
                    <div class="step-circle">✓</div>
                    <div class="step-label">Shipped</div>
                </div>
                <div class="step">
                    <div class="step-circle inactive">4</div>
                    <div class="step-label">Delivered</div>
                </div>
            </div>

            <!-- Delivery Address -->
            <p style="font-size:13px;color:#555;line-height:1.7">
                📦 Delivering to: <strong>{{ $order->shipping_address_line1 }}, {{ $order->shipping_city }}</strong>
            </p>

            <div style="text-align:center">
                <a class="btn"
                    href="{{ config('app.frontend_url', 'http://localhost:3000') }}/orders/{{ $order->order_number }}">
                    Track Order →
                </a>
            </div>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('ecommerce.store_name', 'EcoShop') }}</p>
        </div>
    </div>
</body>

</html>
