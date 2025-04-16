<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonStock extends Model
{
    protected $table = 'amazon_stock';

    protected $fillable = [
        'item_code',
    ];
}
