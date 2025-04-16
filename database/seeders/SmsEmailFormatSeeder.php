<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsEmailFormat;
use Carbon\Carbon;

class SmsEmailFormatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsEmailFormat::insert([
            [
                'actions' => 'Number of days before planned arrival',
                'delay_sending_duration_silent_hours' => 2,
                'send_at' => Carbon::now()->addHours(1),
                'sms_messages' => "We will deliver to [[ADDITIONAL_ATTRIBUTE:pdServicelevel]POSTCODE] tomorrow.
                 You don't need to do anything as we'll text you around 8pm tonight to confirm your 2-hour slot.",
                'email_subject' => 'Your order [ORDER_NAME] will be delivered on [PLANNED_END_DATE]',
                'email_messages' => 'Hi! Your order [ADDITIONAL_ATTRIBUTE:pdWebref] will be delivered to [ADDRESS] on [PLANNED_END_DATE] at
                [PLANNED_START_TIME].[ACCOUNT][PLANNED_START_TIME][ACCOUNT][PLANNED_END_DATE][ACCOUNT].',
                'sms' => 1,
                'email' => 1,
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'attachPod' => 0,
                'fileName' => null,
                'NumberOfDaysBeforePlannedArrival' => 3,
                'sentAt' => Carbon::now(),
                'NotificationBefore' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'actions' => 'Order detail sent',
                'delay_sending_duration_silent_hours' => 1,
                'send_at' => Carbon::now(),
                'sms_messages' => 'Arriving [PLANNED_START_TIME_60_MIN_BEFORE]-[PLANNED_START_TIME_60_MIN_AFTER].
                 Track Here:
                 [LOCATION_WIDGET][COST][DEPO_NAME][ADDITIONAL_ATTRIBUTE:pdBrand][ADDITIONAL_ATTRIBUTE:pdServicelevel]',
                'email_subject' => 'Your order [ORDER_NAME] is due to be delivered on [PLANNED_END_DATE]',
                'email_messages' => 'Hi! Your order [ADDITIONAL_ATTRIBUTE:pdWebref] is due to be delivered to [ADDRESS]
                  between [PLANNED_START_TIME_30_MIN_BEFORE] and [PLANNED_START_TIME_30_MIN_AFTER][DRIVER_NAME][DRIVER_NAME]',
                'sms' => 1,
                'email' => 1,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'attachPod' => 1,
                'fileName' => 'delivery_note.pdf',
                'NumberOfDaysBeforePlannedArrival' => 1,
                'sentAt' => Carbon::now(),
                'NotificationBefore' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'actions' => "Driver set 'on move' for next order",
                'delay_sending_duration_silent_hours' => 1,
                'send_at' => Carbon::now(),
                'sms_messages' => "We're on our way to [ADDRESS]. Your order [ADDITIONAL_ATTRIBUTE:pdWebref] is next. We'll be with you at approximately [ETA].",
                'email_subject' => 'Your order [ORDER_NAME] is next',
                'email_messages' => 'Hi! We are on the move to [ADDRESS]. Your order [ADDITIONAL_ATTRIBUTE:pdWebref] is next. We will be with you at [PLANNED_START_TIME].',
                'sms' => 1,
                'email' => 1,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'attachPod' => 1,
                'fileName' => 'delivery_note.pdf',
                'NumberOfDaysBeforePlannedArrival' => 1,
                'sentAt' => Carbon::now(),
                'NotificationBefore' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'actions' => "N minutes before estimated time of arrival",
                'delay_sending_duration_silent_hours' => 1,
                'send_at' => Carbon::now(),
                'sms_messages' => "Your order [ORDER_NAME] will be delivered to [ADDRESS] between [PLANNED_START_TIME_60_MIN_BEFORE] and [PLANNED_START_TIME_60_MIN_AFTER]. For any queries, please contact out chat team via our website",
                'email_subject' => 'Your order [ORDER_NAME] will be in [ETA]',
                'email_messages' => 'Hi! Your order [ORDER_NAME] will be delivered to [ADDRESS] at [ETA].',
                'sms' => 1,
                'email' => 1,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'attachPod' => 1,
                'fileName' => 'delivery_note.pdf',
                'NumberOfDaysBeforePlannedArrival' => 1,
                'sentAt' => Carbon::now(),
                'NotificationBefore' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'actions' => "Order is completed",
                'delay_sending_duration_silent_hours' => 1,
                'send_at' => Carbon::now(),
                'sms_messages' => "Your order [ORDER_NAME] was delivered to [ADDRESS] at [ORDER_COMPLETE_TIME].[PLANNED_END_DATE]",
                'email_subject' => 'Your order [ORDER_NAME] has been delivered',
                'email_messages' => 'Hi! Your order [ORDER_NAME] was delivered to [ADDRESS] at [ORDER_COMPLETE_TIME][PLANNED_START_TIME]',
                'sms' => 1,
                'email' => 1,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'attachPod' => 1,
                'fileName' => 'delivery_note.pdf',
                'NumberOfDaysBeforePlannedArrival' => 1,
                'sentAt' => Carbon::now(),
                'NotificationBefore' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'actions' => "Order is failed",
                'delay_sending_duration_silent_hours' => 1,
                'send_at' => Carbon::now(),
                'sms_messages' => "Your order [ORDER_NAME] could not be delivered today. Our Customer Relations Team will be in touch to discuss this. We apologise for any inconvenience caused.",
                'email_subject' => 'We have been unable to deliver your order – Reference: [ORDER_NAME]',
                'email_messages' => 'Hello. Your order [ORDER_NAME] was not delivered to [ADDRESS] at [ORDER_COMPLETE_TIME] due to the following reason -  [ORDER_FAIL_REASON]. Sorry for any inconvenience',
                'sms' => 1,
                'email' => 1,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'attachPod' => 1,
                'fileName' => 'delivery_note.pdf',
                'NumberOfDaysBeforePlannedArrival' => 1,
                'sentAt' => Carbon::now(),
                'NotificationBefore' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
