<?php

namespace App\Http\Controllers;

use App\Models\OrderItemCancellationReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Order Item Cancellation Reasons Management
 */
class OrderItemCancellationReasonController extends Controller
{
    /**
     * Get All Item Cancellation Reasons
     * 
     * Retrieve all available order item cancellation reasons.
     * 
     * @group Order Item Cancellation Reasons Management
     * 
     * @response {
     *   "orderItemCancellationReasons": [{
     *     "id": 1,
     *     "name": "Item Not Available",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   }],
     *   "success": true
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch item cancellation reasons",
     *   "error": "Database connection error",
     *   "success": false
     * }
     */
    public function getAllitems(): JsonResponse
    {
        try {
            $orderItemCancellationReasons = OrderItemCancellationReason::orderBy('name')
                ->get();

            return response()->json([
                'orderItemCancellationReasons' => $orderItemCancellationReasons,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch item cancellation reasons',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create Order Item Cancellation Reason
     * 
     * Create a new order item cancellation reason.
     * 
     * @group Order Item Cancellation Reasons Management
     * 
     * @bodyParam name string required The name of the cancellation reason. Example: "Item Not Available"
     * 
     * @response {
     *   "orderItemCancellationReason": {
     *     "id": 1,
     *     "name": "Item Not Available",
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
    public function saveOrderItemCancelationReason(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:65535'
            ]);



            $orderItemCancellationReason = new OrderItemCancellationReason();
            $orderItemCancellationReason->fill($validated);
            $orderItemCancellationReason->save();



            return response()->json([
                'orderItemCancellationReason' => $orderItemCancellationReason,
                'success' => true
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to save order item cancellation reason',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update Order Item Cancellation Reason
     * 
     * Update an existing order item cancellation reason.
     * 
     * @group Order Item Cancellation Reasons Management
     * 
     * @bodyParam id integer required The ID of the cancellation reason. Example: 1
     * @bodyParam name string required The name of the cancellation reason. Example: "Item Not Available"
     * 
     * @response {
     *   "orderItemCancellationReason": {
     *     "id": 1,
     *     "name": "Item Not Available",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Order item cancellation reason not found",
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
    public function updateItem(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:order_item_cancellation_reasons,id',
                'name' => 'required|string|max:65535'
            ]);



            $orderItemCancellationReason = OrderItemCancellationReason::find($validated['id']);

            if (!$orderItemCancellationReason) {
                return response()->json([
                    'message' => 'Order item cancellation reason not found',
                    'success' => false,
                    'success' => false
                ], 200);
            }

            $orderItemCancellationReason->name = $validated['name'];
            $orderItemCancellationReason->save();



            return response()->json([
                'orderItemCancellationReason' => $orderItemCancellationReason,
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
                'message' => 'Failed to update order item cancellation reason',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Delete Order Item Cancellation Reason
     * 
     * Delete one or multiple order item cancellation reasons.
     * 
     * @group Order Item Cancellation Reasons Management
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
    public function deleteItem(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|array',
                'id.*' => 'required|integer|exists:order_item_cancellation_reasons,id'
            ]);



            $deletedCount = OrderItemCancellationReason::whereIn('id', $validated['id'])->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'message' => 'Reasons not found',
                    'success' => false,
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
                'message' => 'Failed to delete order item cancellation reasons',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
