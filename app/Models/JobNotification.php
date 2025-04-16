<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobNotification extends Model
{
    protected $fillable = [
        'job_id',
        'notification_id',
        'disp_id',
        'order_id',
        'error_message'
    ];

    protected $casts = [
        'job_id' => 'integer',
        'notification_id' => 'integer',
        'disp_id' => 'integer',
        'order_id' => 'integer'
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function disp(): BelongsTo
    {
        return $this->belongsTo(Disp::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
} 