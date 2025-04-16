<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;


class OrderExport implements FromCollection
{
    protected $orders;

    public function __construct($data)
    {
        $this->orders = $data;
    }

    public function collection()
    {
        return $this->orders;
    }
}
