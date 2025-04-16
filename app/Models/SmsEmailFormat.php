<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SmsEmailFormat extends Model
{
    protected $fillable = [
        'actions',
        'delay_sending_duration_silent_hours',
        'send_at',
        'sms_messages',
        'email_subject',
        'email_messages',
        'sms',
        'email',
        'start_time',
        'end_time',
        'attachPod',
        'fileName',
        'NumberOfDaysBeforePlannedArrival',
        'sentAt',
        'NotificationBefore'
    ];

    protected $casts = [
        'sms' => 'integer',
        'email' => 'integer',
        'attachPod' => 'integer',
        'NumberOfDaysBeforePlannedArrival' => 'integer',
        'NotificationBefore' => 'integer',
        'sentAt' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Check if SMS notifications are enabled.
     */
    public function isSmsEnabled(): bool
    {
        return (bool) $this->sms;
    }

    /**
     * Check if email notifications are enabled.
     */
    public function isEmailEnabled(): bool
    {
        return (bool) $this->email;
    }

    /**
     * Check if POD attachment is enabled.
     */
    public function isPodAttachmentEnabled(): bool
    {
        return (bool) $this->attachPod;
    }

    /**
     * Check if the current time is within the allowed sending window.
     */
    public function isWithinSendingWindow(): bool
    {
        if (!$this->start_time || !$this->end_time) {
            return true;
        }

        $now = Carbon::now();
        $start = Carbon::createFromTimeString($this->start_time);
        $end = Carbon::createFromTimeString($this->end_time);

        return $now->between($start, $end);
    }

    /**
     * Get the delay duration in hours.
     */
    public function getDelayDuration(): int
    {
        return (int) $this->delay_sending_duration_silent_hours ?? 0;
    }

    /**
     * Parse message template with variables.
     *
     * @param array<string, string> $variables
     */
    public function parseMessageTemplate(string $template, array $variables): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function($matches) use ($variables) {
            return $variables[$matches[1]] ?? $matches[0];
        }, $template);
    }

    /**
     * Get formatted SMS message with variables.
     *
     * @param array<string, string> $variables
     */
    public function getFormattedSmsMessage(array $variables): string
    {
        return $this->parseMessageTemplate($this->sms_messages, $variables);
    }

    /**
     * Get formatted email message with variables.
     */
    public function getFormattedEmailMessage(array $variables): string
    {
        return $this->parseMessageTemplate($this->email_messages, $variables);
    }
} 