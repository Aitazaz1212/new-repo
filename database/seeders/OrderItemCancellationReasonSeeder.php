<?php

namespace Database\Seeders;

use App\Models\OrderItemCancellationReason;
use Illuminate\Database\Seeder;

class OrderItemCancellationReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['name' => 'Item Out of Stock'],
            ['name' => 'Item Damaged'],
            ['name' => 'Wrong Item Picked'],
            ['name' => 'Quality Issue'],
            ['name' => 'Expired Item'],
            ['name' => 'Package Damaged'],
            ['name' => 'Customer Request']
        ];

        foreach ($reasons as $reason) {
            OrderItemCancellationReason::create($reason);
        }
    }
}
