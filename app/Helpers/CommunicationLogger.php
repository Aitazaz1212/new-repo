<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\CommunicationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

final class CommunicationLogger
{
    /**
     * Log a communication event
     */
    public static function log(
        ?int $orderId,
        string $type,
        string $recipient,
        string $content,
        ?string $status = 'sent',
        ?int $userId = null
    ): CommunicationLog {
        return CommunicationLog::create([
            'order_id' => $orderId,
            'user_id' => $userId ?? Auth::id(),
            'type' => $type,
            'recipient' => $recipient,
            'content' => $content,
            'status' => $status,
            'ip' => Request::ip(),
            'proxy' => Request::header('x-forwarded-for'),
            'local' => Request::server('HTTP_HOST'),
        ]);
    }

    /**
     * Log an SMS communication
     */
    public static function logSms(
        ?int $orderId,
        string $phoneNumber,
        string $message,
        ?string $status = 'sent',
        ?int $userId = null
    ): CommunicationLog {
        return self::log(
            orderId: $orderId,
            type: 'SMS_SENT',
            recipient: $phoneNumber,
            content: $message,
            status: $status,
            userId: $userId
        );
    }

    /**
     * Log an email communication
     */
    public static function logEmail(
        ?int $orderId,
        string $email,
        string $subject,
        string $body,
        ?string $status = 'sent',
        ?int $userId = null
    ): CommunicationLog {
        return self::log(
            orderId: $orderId,
            type: 'EMAIL_SENT',
            recipient: $email,
            content: "Subject: {$subject}\nBody: {$body}",
            status: $status,
            userId: $userId
        );
    }
}
