<?php

namespace App\Http\Controllers;

use App\Models\SmsEmailFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @tags SMS & Email Format Management
 */
class SmsEmailFormatController extends Controller
{
    /**
     * Get SMS & Email Format
     * 
     * Retrieve SMS and email format settings.
     * 
     * @group SMS & Email Format Management
     * 
     * @response {
     *   "smsEmailFormat": {
     *     "id": 1,
     *     "actions": "notify",
     *     "delay_sending_duration_silent_hours": "2",
     *     "send_at": "09:00",
     *     "sms_messages": "Your delivery is scheduled",
     *     "email_subject": "Delivery Update",
     *     "email_messages": "Your delivery is scheduled for tomorrow",
     *     "sms": false,
     *     "email": true,
     *     "start_time": "09:00",
     *     "end_time": "17:00",
     *     "attach_pod": false,
     *     "file_name": "delivery_pod",
     *     "number_of_days_before_planned_arrival": 1,
     *     "sent_at": "00:00:00",
     *     "notification_before": 30,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "smsEmailFormat": "not found",
     *   "success": false
     * }
     */
    public function getSmsEmailFormat(): JsonResponse
    {
        try {
            $smsEmailFormat = SmsEmailFormat::all();

            if ($smsEmailFormat->isEmpty()) {
                return response()->json([
                    'smsEmailFormat' => 'not found',
                    'success' => false,
                    'success' => false
                ], 200);
            }

            return response()->json([
                'smsEmailFormat' => $smsEmailFormat,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch SMS & email format',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update SMS & Email Format
     * 
     * Create or update SMS and email format settings.
     * 
     * @group SMS & Email Format Management
     * 
     * @bodyParam actions string The actions setting. Example: "notify"
     * @bodyParam delay_sending_duration_silent_hours string The delay duration for silent hours. Example: "2"
     * @bodyParam send_at string The send at time. Example: "09:00"
     * @bodyParam sms_messages string The SMS message template. Example: "Your delivery is scheduled"
     * @bodyParam email_subject string The email subject. Example: "Delivery Update"
     * @bodyParam email_messages string The email message template. Example: "Your delivery is scheduled for tomorrow"
     * @bodyParam sms boolean Enable/disable SMS. Example: true
     * @bodyParam email boolean Enable/disable email. Example: true
     * @bodyParam start_time string The start time. Example: "09:00"
     * @bodyParam end_time string The end time. Example: "17:00"
     * @bodyParam attach_pod boolean Enable/disable POD attachment. Example: true
     * @bodyParam file_name string The file name for POD. Example: "delivery_pod"
     * @bodyParam number_of_days_before_planned_arrival integer Days before planned arrival. Example: 1
     * @bodyParam sent_at time The sent at time. Example: "00:00:00"
     * @bodyParam notification_before integer Minutes before notification. Example: 30
     * 
     * @response {
     *   "smsEmailFormat": {
     *     "id": 1,
     *     "actions": "notify",
     *     "delay_sending_duration_silent_hours": "2",
     *     "send_at": "09:00",
     *     "sms_messages": "Your delivery is scheduled",
     *     "email_subject": "Delivery Update",
     *     "email_messages": "Your delivery is scheduled for tomorrow",
     *     "sms": true,
     *     "email": true,
     *     "start_time": "09:00",
     *     "end_time": "17:00",
     *     "attach_pod": true,
     *     "file_name": "delivery_pod",
     *     "number_of_days_before_planned_arrival": 1,
     *     "sent_at": "00:00:00",
     *     "notification_before": 30,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     */
    public function createOrUpdateSmsEmailFormat(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'actions' => 'nullable|string',
                'delay_sending_duration_silent_hours' => 'nullable|string',
                'send_at' => 'nullable|string',
                'sms_messages' => 'nullable|string',
                'email_subject' => 'nullable|string',
                'email_messages' => 'nullable|string',
                'sms' => 'boolean',
                'email' => 'boolean',
                'start_time' => 'nullable|string',
                'end_time' => 'nullable|string',
                'attach_pod' => 'boolean',
                'file_name' => 'nullable|string',
                'number_of_days_before_planned_arrival' => 'integer',
                'sent_at' => 'date_format:H:i:s',
                'notification_before' => 'integer'
            ]);



            $smsEmailFormat = SmsEmailFormat::find(1);
            if (!$smsEmailFormat) {
                $smsEmailFormat = new SmsEmailFormat();
            }

            $smsEmailFormat->fill($validated);
            $smsEmailFormat->save();



            return response()->json([
                'smsEmailFormat' => $smsEmailFormat,
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to create/update SMS & email format',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
