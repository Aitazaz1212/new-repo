<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Helpers\CommunicationLogger;
use Illuminate\Database\Seeder;

final class CommunicationLogSeeder extends Seeder
{
    public function run(): void
    {
        Order::chunk(100, function ($orders) {
            foreach ($orders as $order) {
                // Log a sample SMS
                CommunicationLogger::logSms(
                    orderId: $order->id,
                    phoneNumber: '+44123456789',
                    message: "Your order #{$order->ref_no} has been confirmed and will be delivered soon.",
                    status: 'delivered',
                    userId: 1
                );

                // Log a sample Email
                CommunicationLogger::logEmail(
                    orderId: $order->id,
                    email: 'customer@example.com',
                    subject: "Order Confirmation #{$order->ref_no}",
                    body: "Thank you for your order. Your order #{$order->ref_no} has been confirmed and is being processed.",
                    status: 'sent',
                    userId: 1
                );
            }
        });
    }
}
