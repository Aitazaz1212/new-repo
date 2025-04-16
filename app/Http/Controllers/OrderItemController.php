<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Validator};

/**
 * @tags Order Items
 */
class OrderItemController extends Controller
{
    /**
     * Order Items Report
     * 
     * Get a detailed report of order items within a date range, filtered by warehouse and status.
     * 
     * @group Order Items
     * 
     * @bodyParam fromDate string required Start date in d/m/Y format. Example: 01/01/2024
     * @bodyParam toDate string required End date in d/m/Y format. Example: 31/01/2024
     * @bodyParam distributionId integer optional Warehouse ID to filter by. Example: 1
     * @bodyParam status array optional Array of item statuses to filter by. Example: ["pending", "delivered"]
     * 
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "oid": 1,
     *     "itemid": "PROD001",
     *     "itemStatus": "pending",
     *     "order": {
     *       "id": 1,
     *       "deliverBy_datetime": "2024-01-01 14:00:00",
     *       "warehouse_id": 1,
     *       "customer": {
     *         "id": 1,
     *         "name": "Customer Name"
     *       },
     *       "dispvehicle": {
     *         "id": 1,
     *         "name": "Vehicle 1"
     *       }
     *     }
     *   }],
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Date field cannot be empty",
     *   "success": false
     * }
     */
    public function orderItemsReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fromDate' => 'required|date_format:d/m/Y',
            'toDate' => [
                'required',
                'date_format:d/m/Y',
                'after_or_equal:fromDate'
            ],
            'distributionId' => 'nullable|integer|exists:warehouses,id',
            'status' => 'nullable|array',
            'status.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Date field cannot be empty',
                'success' => false
            ], 422);
        }

        try {
            $fromDate = Carbon::createFromFormat('d/m/Y', $request->fromDate)
                ->startOfDay()
                ->format('Y-m-d');

            $toDate = Carbon::createFromFormat('d/m/Y', $request->toDate)
                ->endOfDay()
                ->format('Y-m-d');

            $query = OrderItem::with([
                'order',
                'order.dispvehicle',
                'order.customer',
                'order.orderItems',
            ])->whereHas('order', function ($q) use ($fromDate, $toDate) {
                $q->whereBetween(DB::raw('DATE(orders.deliverBy_datetime)'), [$fromDate, $toDate]);
            });

            if ($request->filled('distributionId')) {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('warehouse_id', $request->distributionId);
                });
            }

            if ($request->filled('status')) {
                $query->whereIn('itemStatus', $request->status);
            }

            $result = $query->get();

            return response()->json([
                'data' => $result,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Overall Delivery Accuracy Report
     * 
     * Get a detailed report of delivery accuracy within a date range for a specific warehouse.
     * 
     * @group Order Items
     * 
     * @bodyParam fromDate string required Start date in d/m/Y format. Example: 01/01/2024
     * @bodyParam toDate string required End date in d/m/Y format. Example: 31/01/2024
     * @bodyParam warehouseId integer optional Warehouse ID to filter by. Example: 1
     * 
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "oid": 1,
     *     "itemid": "PROD001",
     *     "itemStatus": "delivered",
     *     "order": {
     *       "id": 1,
     *       "deliverBy_datetime": "2024-01-01 14:00:00",
     *       "warehouse_id": 1,
     *       "customer": {
     *         "id": 1,
     *         "name": "Customer Name"
     *       },
     *       "orderItems": [],
     *       "orderLogs": []
     *     }
     *   }],
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Date field cannot be empty",
     *   "success": false
     * }
     */
    public function overallDeliveryAccuracyReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fromDate' => 'required|date_format:d/m/Y',
            'toDate' => [
                'required',
                'date_format:d/m/Y',
                'after_or_equal:fromDate'
            ],
            'warehouseId' => 'nullable|integer|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Date field cannot be empty',
                'success' => false
            ], 422);
        }

        try {
            $fromDate = Carbon::createFromFormat('d/m/Y', $request->fromDate)
                ->startOfDay()
                ->format('Y-m-d');

            $toDate = Carbon::createFromFormat('d/m/Y', $request->toDate)
                ->endOfDay()
                ->format('Y-m-d');

            $query = OrderItem::with([
                'order',
                'order.customer',
                'order.orderItems',
            ])->whereHas('order', function ($q) use ($fromDate, $toDate) {
                $q->whereBetween(DB::raw('DATE(orders.deliverBy_datetime)'), [$fromDate, $toDate]);
            });

            if ($request->filled('warehouseId')) {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('warehouse_id', $request->warehouseId);
                });
            }

            $result = $query->get();

            return response()->json([
                'data' => $result,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
