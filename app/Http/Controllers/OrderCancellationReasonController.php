<?php

namespace App\Http\Controllers;

use App\Models\OrderCancellationReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Order Cancellation Reasons Management
 */
class OrderCancellationReasonController extends Controller
{
    /**
     * Get All Cancellation Reasons
     * 
     * Retrieve all available order cancellation reasons.
     * 
     * @group Order Cancellation Reasons Management
     * 
     * @response {
     *   "orderCancellationReasons": [{
     *     "id": 1,
     *     "name": "Item Not Arrived",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   }],
     *   "success": true
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch cancellation reasons",
     *   "error": "Database connection error",
     *   "success": false
     * }
     */
    public function getAllReasons(): JsonResponse
    {
        try {
            $orderCancellationReasons = OrderCancellationReason::orderBy('name')
                ->get();

            return response()->json([
                'orderCancellationReasons' => $orderCancellationReasons,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch cancellation reasons',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create Order Cancellation Reason
     * 
     * Create a new order cancellation reason.
     * 
     * @group Order Cancellation Reasons Management
     * 
     * @bodyParam name string required The name of the cancellation reason. Example: "Item Not Arrived"
     * 
     * @response {
     *   "orderCancellationReason": {
     *     "id": 1,
     *     "name": "Item Not Arrived",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   },
     *   "success": false
     * }
     */
    public function saveOrderCancelationReason(Request $request): JsonResponse
    {
        try {


            $orderCancellationReason = new OrderCancellationReason();
            $orderCancellationReason->fill($request->only(['name']));
            $orderCancellationReason->save();



            return response()->json([
                'orderCancellationReason' => $orderCancellationReason,
                'success' => true
            ], 201);
        } catch (\Exception $e) {

            Log::error('Failed to save order cancellation reason: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save order cancellation reason',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update Order Cancellation Reason
     * 
     * Update an existing order cancellation reason.
     * 
     * @group Order Cancellation Reasons Management
     * 
     * @bodyParam id integer required The ID of the cancellation reason. Example: 1
     * @bodyParam name string required The name of the cancellation reason. Example: "Item Not Arrived"
     * 
     * @response {
     *   "orderCancellationReason": {
     *     "id": 1,
     *     "name": "Item Not Arrived",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Order cancellation reason not found",
     *   "success": false
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   },
     *   "success": false
     * }
     */
    public function updateReason(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:order_cancellation_reasons,id',
            'name' => 'required|string|max:65535'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $orderCancellationReason = OrderCancellationReason::find($request->id);

            if (!$orderCancellationReason) {
                return response()->json([
                    'message' => 'Order cancellation reason not found',
                    'success' => false
                ], 200);
            }

            $orderCancellationReason->name = $request->name;
            $orderCancellationReason->save();



            return response()->json([
                'orderCancellationReason' => $orderCancellationReason,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to update order cancellation reason',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Delete Order Cancellation Reason
     * 
     * Delete one or multiple order cancellation reasons.
     * 
     * @group Order Cancellation Reasons Management
     * 
     * @bodyParam id array required Array of cancellation reason IDs to delete. Example: [1, 2]
     * 
     * @response {
     *   "message": "Reasons deleted successfully",
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Reasons not found",
     *   "success": false
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "id": ["The id field is required."]
     *   },
     *   "success": false
     * }
     */
    public function deleteReasons(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|array',
                'id.*' => [
                    'required',
                    'integer',
                    'exists:order_cancellation_reasons,id',
                ]
            ]);



            $deletedCount = OrderCancellationReason::whereIn('id', $validated['id'])->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'message' => 'Reasons not found',
                    'success' => false
                ], 200);
            }



            return response()->json([
                'message' => 'Reasons deleted successfully',
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
                'message' => 'Failed to delete order cancellation reasons',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
