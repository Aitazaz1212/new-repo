<?php

namespace Database\Seeders;

use App\Models\OrderCancellationReason;
use Illuminate\Database\Seeder;

class OrderCancellationReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = OrderCancellationReason::getPredefinedReasons();
        
        foreach ($reasons as $reason) {
            OrderCancellationReason::create(['name' => $reason]);
        }
    }
}
