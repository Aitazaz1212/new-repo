<?php

namespace App\Http\Controllers;

use App\Models\OrderTrackingWidget;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * @tags Order Tracking Widget Management
 */
class OrderTrackingWidgetController extends Controller
{
    /**
     * Get Order Tracking Widget
     * 
     * Retrieve order tracking widget settings.
     * 
     * @group Order Tracking Widget Management
     * 
     * @response {
     *   "orderTrackingWidget": {
     *     "id": 1,
     *     "widget_domain": "example.com",
     *     "widget_location": "/tracking",
     *     "rate_delivery": false,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "orderTrackingWidget": "not found",
     *   "success": false
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch order tracking widget",
     *   "error": "Database error",
     *   "success": false
     * }
     */
    public function getOrderTrackingWidget(): JsonResponse
    {
        try {
            $orderTrackingWidget = OrderTrackingWidget::first();

            if (!$orderTrackingWidget) {
                return response()->json([
                    'message' => 'Order tracking widget not found',
                    'success' => false,
                    'success' => false
                ], 200);
            }

            return response()->json([
                'orderTrackingWidget' => $orderTrackingWidget,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch order tracking widget',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update Order Tracking Widget
     * 
     * Create or update order tracking widget settings.
     * 
     * @group Order Tracking Widget Management
     * 
     * @bodyParam widget_domain string required The domain for the widget. Max length: 22 characters. Example: "example.com"
     * @bodyParam widget_location string required The location path for the widget. Max length: 800 characters. Example: "/tracking"
     * @bodyParam rate_delivery boolean required Enable/disable delivery rating. Example: true
     * 
     * @response {
     *   "orderTrackingWidget": {
     *     "id": 1,
     *     "widget_domain": "example.com",
     *     "widget_location": "/tracking",
     *     "rate_delivery": true,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "widget_domain": ["The widget domain field is required."]
     *   },
     *   "success": false
     * }
     */
    public function createOrUpdateOrderTrackingWidget(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'widget_domain' => 'required|string|max:22',
                'widget_location' => 'required|string|max:800',
                'rate_delivery' => 'required|boolean'
            ]);



            $orderTrackingWidget = OrderTrackingWidget::find(1);
            if (!$orderTrackingWidget) {
                $orderTrackingWidget = new OrderTrackingWidget();
            }

            $orderTrackingWidget->fill($validated);
            $orderTrackingWidget->save();



            return response()->json([
                'orderTrackingWidget' => $orderTrackingWidget,
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
                'message' => 'Failed to create/update order tracking widget',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
