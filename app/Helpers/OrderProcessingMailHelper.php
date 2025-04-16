<?php

namespace App\Helpers;

use App\Mail\OrderProcessingMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
/**
 * Helper class for handling orders processing
 * when the order status changes sent email to the customer
 */
class OrderProcessingMailHelper
{
    /**
     *Sent email to the customer when the route is assigned to the customer.
    */
    public static function  sentEmailToCustomerWhenRouteIsAssigned($orders)
    {

        if(!empty($orders)){
            foreach ($orders as $order) {

                $appUrl = env('APP_URL');
                $url =  $appUrl .'/track'.'/'. $order->oid;
                /**
                 * Send an email to the customer only if the environment variable APP_ENV is set to 'production'.
                 */
                if (app()->environment('production')) {

                    Mail::to(['akbar@giomani-designs.com'])->send(new OrderProcessingMail($order , $url));
                }
                CommunicationLogger::logEmail($order->id, 'akbar@giomani-designs.com', 'Order Processing', 'Order Processing', 'sent');

                /**
                * Send sms to the customer get customer number from order object $order->fax
                * only if the environment variable APP_ENV is set to 'production'.
                */
                // $message = "We will deliver to " . $order->ref_no
                //  . "tomorrow.You don't need to do anything as we'll text you around 8pm tonight to confirm your 2-hour slot.";
                $message = 'To track your order, follow the  link.'. $url;
                $number = '+44 7575 958440';
                // $number = $order->fax;
                if (app()->environment('production')) {
                    send_sms($number, $message);
                }
                CommunicationLogger::logSms($order->id, $number, $message);
            }
        }

    }
}
