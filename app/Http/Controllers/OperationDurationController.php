<?php

namespace App\Http\Controllers;

use App\Models\OperationDuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @tags Operation Duration Management
 */
class OperationDurationController extends Controller
{
    /**
     * Get Operation Duration
     * 
     * Retrieve the operation duration configuration.
     * 
     * @group Operation Duration Management
     * 
     * @response {
     *   "opertionDurations": {
     *     "id": 1,
     *     "customer_id": 1,
     *     "fixed_loading_duration": "30",
     *     "variable_loading_duration_per_unit": "5",
     *     "fixed_time_per_address": {"value": "15"},
     *     "fixed_time_per_order": {"value": "10"},
     *     "variable_time_per_capacity_delivery": {"value": "2"},
     *     "variable_time_per_capacity_collection": {"value": "2"},
     *     "fixed_un_loading_duration": "20",
     *     "variable_un_loading_duration_per_unit": "3",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "opertionDurations": "Operation duration not found",
     *   "success": false
     * }
     */
    public function getOpertionDuration(): JsonResponse
    {
        try {
            $opertionDurations = OperationDuration::first();

            if (!$opertionDurations) {
                return response()->json([
                    'status' => 'Operation duration not found',
                    'success' => false
                ], 200);
            }

            return response()->json([
                'opertionDurations' => $opertionDurations,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch operation duration',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update Operation Duration
     * 
     * Create or update operation duration configuration.
     * 
     * @group Operation Duration Management
     * 
     * @bodyParam id integer nullable The ID of the operation duration. Example: 1
     * @bodyParam customer_id integer nullable The ID of the customer. Example: 1
     * @bodyParam fixed_loading_duration string nullable Fixed duration for loading. Example: "30"
     * @bodyParam variable_loading_duration_per_unit string nullable Variable loading duration per unit. Example: "5"
     * @bodyParam fixed_time_per_address object nullable Fixed time per address. Example: {"value": "15"}
     * @bodyParam fixed_time_per_order object nullable Fixed time per order. Example: {"value": "10"}
     * @bodyParam variable_time_per_capacity_delivery object nullable Variable time per capacity for delivery. Example: {"value": "2"}
     * @bodyParam variable_time_per_capacity_collection object nullable Variable time per capacity for collection. Example: {"value": "2"}
     * @bodyParam fixed_un_loading_duration string nullable Fixed duration for unloading. Example: "20"
     * @bodyParam variable_un_loading_duration_per_unit string nullable Variable unloading duration per unit. Example: "3"
     * 
     * @response {
     *   "opertionDurations": {
     *     "id": 1,
     *     "customer_id": 1,
     *     "fixed_loading_duration": "30",
     *     "variable_loading_duration_per_unit": "5",
     *     "fixed_time_per_address": {"value": "15"},
     *     "fixed_time_per_order": {"value": "10"},
     *     "variable_time_per_capacity_delivery": {"value": "2"},
     *     "variable_time_per_capacity_collection": {"value": "2"},
     *     "fixed_un_loading_duration": "20",
     *     "variable_un_loading_duration_per_unit": "3",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "customer_id": ["The customer id field must be a valid customer ID."]
     *   },
     *   "success": false
     * }
     */
    public function createOrUpdateOpertionDuration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer|exists:operation_durations,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'fixed_loading_duration' => 'nullable',
            'variable_loading_duration_per_unit' => 'nullable',
            'fixed_time_per_address' => 'nullable',
            'fixed_time_per_order' => 'nullable',
            'variable_time_per_capacity_delivery' => 'nullable',
            'variable_time_per_capacity_collection' => 'nullable',
            'fixed_un_loading_duration' => 'nullable',
            'variable_un_loading_duration_per_unit' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $opertionDurations = OperationDuration::updateOrCreate(
                ['id' => $request->id],
                [
                    'customer_id' => $request->customer_id,
                    'fixed_loading_duration' => (string) $request->fixed_loading_duration,
                    'variable_loading_duration_per_unit' => (string) $request->variable_loading_duration_per_unit,
                    'fixed_time_per_address' => (string) $request->fixed_time_per_address,
                    'fixed_time_per_order' => (string) $request->fixed_time_per_order,
                    'variable_time_per_capacity_delivery' => (string) $request->variable_time_per_capacity_delivery,
                    'variable_time_per_capacity_collection' => (string) $request->variable_time_per_capacity_collection,
                    'fixed_un_loading_duration' => (string) $request->fixed_un_loading_duration,
                    'variable_un_loading_duration_per_unit' => (string) $request->variable_un_loading_duration_per_unit
                ]
            );



            return response()->json([
                'opertionDurations' => $opertionDurations,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            Log::error('Failed to save operation duration: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save operation duration',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
