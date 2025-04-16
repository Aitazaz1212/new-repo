<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\OrderOperationTimeWindow;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class OrderImportSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'orderReference' => 'ORD-001',
                'date' => '29/11/2024',
                'contactNumber' => '+44 7123456789',
                'customerLocationAddress' => '123 Delivery St, London',
                'customerLocationName' => 'Office Building A',
                'latitude' => '51.5074',
                'longitude' => '-0.1278',
                'status' => 'pending',
                'clientName' => 'John Corp Ltd',
                'contactPerson' => 'John Smith',
                'contactEmail' => 'john@example.com',
                'webref' => 'WEB001',
                'orderItems' => '[Item1]123|EXT456|0|2|Office Supplies|10|20|30|5|2'
            ],
            [
                'orderReference' => 'ORD-002',
                'date' => '29/11/2024',
                'contactNumber' => '+44 7987654321',
                'customerLocationAddress' => '456 Shipping Ave, London',
                'customerLocationName' => 'Warehouse B',
                'latitude' => '51.5124',
                'longitude' => '-0.1258',
                'status' => 'pending',
                'clientName' => 'Smith Industries',
                'contactPerson' => 'Sarah Smith',
                'contactEmail' => 'sarah@example.com',
                'webref' => 'WEB002',
                'orderItems' => '[Item2]456|EXT789|0|3|Electronics|15|25|35|8|3'
            ]
        ];

        foreach ($orders as $orderData) {
            try {
                // Create customer
                $customer = Customer::create([
                    'businessName' => $orderData['clientName'],
                    'mob' => $orderData['contactNumber'],
                    'number' => $orderData['contactNumber'],
                    'email1' => $orderData['contactEmail'],
                    'web' => $orderData['webref'],
                    'street' => $orderData['customerLocationName'],
                    'postcode' => $orderData['customerLocationAddress']
                ]);

                // Create order
                $order = Order::create([
                    'ref_no' => $orderData['orderReference'],
                    'orderStatus' => $orderData['status'],
                    'deliverBy_datetime' => Carbon::createFromFormat('d/m/Y', $orderData['date'])->format('Y-m-d H:i:s'),
                    'lat' => $orderData['latitude'],
                    'lng' => $orderData['longitude'],
                    'cid' => $customer->id,
                    'warehouse_id' => 1 // Assuming default warehouse
                ]);

                // Create operation time window
                if ($order) {
                    OrderOperationTimeWindow::create([
                        'order_id' => $order->id,
                        'date' => Carbon::createFromFormat('d/m/Y', $orderData['date'])->format('Y-m-d')
                    ]);
                }

                // Process order items if present
                if (isset($orderData['orderItems']) && $order) {
                    // Split items string and create order items
                    $items = explode(';', $orderData['orderItems']);
                    foreach ($items as $item) {
                        $itemParts = explode('|', $item);
                        if (count($itemParts) >= 10) {
                            OrderItem::create([
                                'oid' => $order->id,
                                'itemid' => $itemParts[1],
                                'status' => $itemParts[2],
                                'qty' => $itemParts[3],
                                'description' => $itemParts[4],
                                'weight' => $itemParts[5],
                                'height' => $itemParts[6],
                                'width' => $itemParts[7],
                                'depth' => $itemParts[8],
                                'pieces' => $itemParts[9]
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error and continue
                \Log::error("Error seeding order {$orderData['orderReference']}: " . $e->getMessage());
                continue;
            }
        }
    }
}
