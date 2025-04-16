<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class UnplannedOrders implements FromCollection
{
    protected $orders;

     public function __construct($orders)
     {
        $this->orders=$orders;

     }

    public function collection()
    {
        return $this->orders;
    }
}
