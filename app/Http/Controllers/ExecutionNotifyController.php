<?php

namespace App\Http\Controllers;

use App\Models\ExecutionNotify;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * @tags Execution Notifications Management
 */
class ExecutionNotifyController extends Controller
{
    /**
     * Get Execution Notification
     * 
     * Retrieve execution notification settings.
     * 
     * @group Execution Notifications Management
     * 
     * @response {
     *   "executionNotify": {
     *     "id": 1,
     *     "en_dispat_noti_before_the_driver_completes_the_run": "30",
     *     "send_e_mail_notification_before_x_not_started_orders": "5",
     *     "en_driver_noti_when_order_det_are_sent_to_mobile_dev": "true",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Execution notification not found",
     *   "success": false
     * }
     */
    public function getNotification(): JsonResponse
    {
        try {
            $executionNotify = ExecutionNotify::find(1);

            if (!$executionNotify) {
                return response()->json([
                    'message' => 'Execution notification not found',
                    'success' => false
                ], 200);
            }

            return response()->json([
                'executionNotify' => $executionNotify,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch execution notification',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update Execution Notification
     * 
     * Create or update execution notification settings.
     * 
     * @group Execution Notifications Management
     * 
     * @bodyParam en_dispat_noti_before_the_driver_completes_the_run string The notification time before driver completion. Example: "30"
     * @bodyParam send_e_mail_notification_before_x_not_started_orders string The number of not started orders before notification. Example: "5"
     * @bodyParam en_driver_noti_when_order_det_are_sent_to_mobile_dev string Enable driver notification for mobile device orders. Example: "true"
     * 
     * @response {
     *   "executionNotify": {
     *     "id": 1,
     *     "en_dispat_noti_before_the_driver_completes_the_run": "30",
     *     "send_e_mail_notification_before_x_not_started_orders": "5",
     *     "en_driver_noti_when_order_det_are_sent_to_mobile_dev": "true",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "en_dispat_noti_before_the_driver_completes_the_run": ["The notification time field is required."]
     *   },
     *   "success": false
     * }
     */
    public function createOrUpdateExecutionNotification(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'en_dispat_noti_before_the_driver_completes_the_run' => 'required|boolean',
                'send_e_mail_notification_before_x_not_started_orders' => 'required|string',
                'en_driver_noti_when_order_det_are_sent_to_mobile_dev' => 'required|boolean'
            ]);



            $executionNotify = ExecutionNotify::find(1);
            if (!$executionNotify) {
                $executionNotify = new ExecutionNotify();
            }

            $executionNotify->fill($validated);
            $executionNotify->save();



            return response()->json([
                'executionNotify' => $executionNotify,
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
                'message' => 'Failed to create/update execution notification',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
