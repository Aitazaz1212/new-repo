<?php

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

if (!function_exists('send_sms')) {
    /**
     * Sends an SMS using AWS SNS.
     *
     * @param string $number The phone number to send the SMS to.
     * @param string $message The SMS message content.
     * @return array The result or error message.
     */
    function send_sms(string $number, string $message)
    {
        // Format the phone number uk base
        $number = preg_replace('/\s+/', '', $number); // Remove spaces
        if (strpos($number, '+44') !== 0) {
            if (strpos($number, '44') === 0) {
                $number = '+' . $number;
            } else {
                $number = '+44' . $number;
            }
        }


        try {
            $sns = new SnsClient([
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
                'region' => env('AWS_DEFAULT_REGION', 'eu-west-2'),
                'version' => 'latest',
            ]);

            $result = $sns->publish([
                'Message' => $message,
                'PhoneNumber' => $number,
                'MessageAttributes' => [
                    'AWS.SNS.SMS.SenderID' => [
                        'DataType' => 'String',
                        'StringValue' => 'Premier',
                    ],
                    'AWS.SNS.SMS.SMSType' => [
                        'DataType' => 'String',
                        'StringValue' => 'Transactional',
                    ],
                ],
            ]);

            return $result;
        } catch (AwsException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
