<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Assigned</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            background-color: #007bff;
            color: #ffffff;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 20px;
            color: #333333;
            line-height: 1.6;
        }
        .content h1 {
            color: #007bff;
        }
        .button {
            display: block;
            width: fit-content;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #aaaaaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Order Delivery Status</h2>
        </div>
        <div class="content">
            <h1>Hi!   {{ $order->clientName }}</h1>
            <p>
                Your order {{ $order->ref_no }} will be delivered to {{ $order->address }}  on {{ $date }}.
            </p>
            <p>
                To track your order, click the button below:
            </p>
            <a href="{{ $url }}" class="button">Track Your Order</a>
        </div>
        <div class="footer">
            {{-- <p>&copy; [Your Company Name]. All Rights Reserved.</p> --}}
        </div>
    </div>
</body>
</html>
