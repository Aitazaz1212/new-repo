<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class OrderProcessingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $url;

    public function __construct($order , $url)
    {
        $this->order = $order;
        $this->url = $url;
    }

    public function build()
    {
        $date =  Carbon::parse($this->order->deliverBy_datetime)->format('F j, Y, g:i A');
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject('Your order ' .' ' .$this->order->ref_no . 'will be delivered on' .'  '. $date)
                    ->view('emails_tempates.route_assigned_to_driver')
                    ->with(['order' => $this->order  ,'date'=>$date  , 'url' =>  $this->url]);
    }
}





