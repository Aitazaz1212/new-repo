<?php

namespace App\Http\Controllers;

use App\Models\ExecutionPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @tags Execution Preferences Management
 */
class ExecutionPreferenceController extends Controller
{
    /**
     * Get Execution Preferences
     * 
     * Retrieve the current execution preferences configuration.
     * 
     * @group Execution Preferences Management
     * 
     * @response {
     *   "executionPreference": {
     *     "id": 1,
     *     "use_driver_manifest_template": true,
     *     "prevent_order_completion_before_collecting_a_signature": true,
     *     "prevent_order_completion_before_attaching_photo": true,
     *     "attachments_upload_mode": "immediate",
     *     "display_order_priorities_on_the_mobile_app": true,
     *     "enable_order_item_checkbox": true,
     *     "enable_check_all_for_order_items": true,
     *     "items_default_state_checked": true,
     *     "display_price_info_on_the_mobile_app": true,
     *     "area_radius_for_track_trace_control_meters": 1000,
     *     "send_order_details_by_timer": true,
     *     "send_order_details_at": "09:00",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Execution preferences not found",
     *   "success": false
     * }
     */
    public function getExecutionPreference(): JsonResponse
    {
        try {
            $executionPreference = ExecutionPreference::first();
            if (!$executionPreference) {
                return response()->json([
                    'message' => 'Execution preferences not found',
                    'success' => false
                ], 200);
            }

            return response()->json([
                'executionPreference' => $executionPreference,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch execution preferences',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update Execution Preferences
     * 
     * Create or update execution preferences configuration.
     * 
     * @group Execution Preferences Management
     * 
     * @bodyParam use_driver_manifest_template boolean required Use driver manifest template. Example: true
     * @bodyParam prevent_order_completion_before_collecting_a_signature boolean required Require signature for completion. Example: true
     * @bodyParam prevent_order_completion_before_attaching_photo boolean required Require photo for completion. Example: true
     * @bodyParam attachments_upload_mode string required Upload mode for attachments. Example: "immediate"
     * @bodyParam display_order_priorities_on_the_mobile_app boolean required Display order priorities. Example: true
     * @bodyParam enable_order_item_checkbox boolean required Enable order item checkbox. Example: true
     * @bodyParam enable_check_all_for_order_items boolean required Enable check all for items. Example: true
     * @bodyParam items_default_state_checked boolean required Default state for items. Example: true
     * @bodyParam display_price_info_on_the_mobile_app boolean required Display price info. Example: true
     * @bodyParam area_radius_for_track_trace_control_meters integer required Area radius in meters. Example: 1000
     * @bodyParam send_order_details_by_timer boolean required Send order details by timer. Example: true
     * @bodyParam send_order_details_at string required Time to send order details. Example: "09:00"
     * 
     * @response {
     *   "executionPreference": {
     *     "id": 1,
     *     "use_driver_manifest_template": true,
     *     "prevent_order_completion_before_collecting_a_signature": true,
     *     "prevent_order_completion_before_attaching_photo": true,
     *     "attachments_upload_mode": "immediate",
     *     "display_order_priorities_on_the_mobile_app": true,
     *     "enable_order_item_checkbox": true,
     *     "enable_check_all_for_order_items": true,
     *     "items_default_state_checked": true,
     *     "display_price_info_on_the_mobile_app": true,
     *     "area_radius_for_track_trace_control_meters": 1000,
     *     "send_order_details_by_timer": true,
     *     "send_order_details_at": "09:00",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     */
    public function createOrUpdateExecutionPrefernces(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'use_driver_manifest_template' => 'required|boolean',
            'prevent_order_completion_before_collecting_a_signature' => 'required|boolean',
            'prevent_order_completion_before_attaching_photo' => 'required|boolean',
            'attachments_upload_mode' => ['required', 'string'],
            'display_order_priorities_on_the_mobile_app' => 'required|boolean',
            'enable_order_item_checkbox' => 'required|boolean',
            'enable_check_all_for_order_items' => 'required|boolean',
            'items_default_state_checked' => 'required|boolean',
            'display_price_info_on_the_mobile_app' => 'required|boolean',
            'area_radius_for_track_trace_control_meters' => 'required|integer|min:1',
            'send_order_details_by_timer' => 'required|boolean',
            'send_order_details_at' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $executionPreference = ExecutionPreference::first();
            if (!$executionPreference) {
                $executionPreference = new ExecutionPreference();
            }

            $executionPreference->fill($request->all());
            $executionPreference->save();



            return response()->json([
                'executionPreference' => $executionPreference,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            Log::error('Failed to save execution preferences: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save execution preferences',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
