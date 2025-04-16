<?php

namespace App\Http\Controllers;

use App\Models\{
    Disp,
    DispRoute,
    Order,
    User,
    History,
    OperationDuration,
    Driver
};

use App\Helpers\MapsHelper;
use App\Helpers\FcmFunctionHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\Territory;
use Illuminate\Support\Facades\Cache;
use App\Helpers\OrderLogger;
use App\Enums\OrderAction;

class PlanningToolController extends Controller
{
    /**
     * Get Planning Tool Data
     *
     * Retrieves planning data including orders, vehicles, and territories for a specific date and distribution center.
     *
     * @authenticated
     *
     * @queryParam date string required The date in d/m/Y format. Example: 25/12/2023
     * @queryParam distribution_id integer required The ID of the distribution center. Example: 1
     *
     * @response {
     *   "orders": [
     *     {
     *       "id": 1,
     *       "ref_no": "ORD123",
     *       "warehouse_id": 1,
     *       "priority": "high",
     *       // ... other order fields
     *     }
     *   ],
     *   "vehicles": [...],
     *   "warehouse": {...},
     *   "Territory": [...],
     *   "success": true
     * }
     */
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:d/m/Y',
            'distribution_id' => 'required|exists:warehouses,id',
        ]);

        $dateRange = $this->getDateRange($request->date);
        // $orders = $this->getOrders($dateRange, $request->distribution_id);
        $warehouse = $this->getWarehouse($request->distribution_id);
        $vehicles = $this->getVehicles($dateRange, $request->distribution_id);

        // Process vehicle dispatches
        $this->processVehicleDispatches($vehicles);

        return response()->json([
            // 'orders' => $orders,
            'vehicles' => $vehicles,
            'warehouse' => $warehouse,
            'Territory' => $this->getTerritories($request->distribution_id),
            'dispatcher_settings' => [
                'operation_duration' => $this->getOperationDuration(),
            ],
            'success' => true
        ], 200);
    }

    /**
     * Get route polylines for vehicles
     *
     * @authenticated
     *
     * @queryParam date string required The date in d/m/Y format. Example: 25/12/2023
     * @queryParam distribution_id integer required The ID of the distribution center. Example: 1
     *
     * @response {
     *   "route_polylines": [...],
     *   "success": true
     * }
     */
    public function getVehicleRoutePolylines(Request $request)
    {
        // If vehicle_id exists in the request, it means load polylines in the track for a specific vehicle.
        $request->validate([
            'date' => 'required|date_format:d/m/Y',
            'distribution_id' => 'required|exists:warehouses,id',
            'vehicle_id' => 'nullable',
            'route_id' => 'nullable',
        ]);

        // Retrieve the vehicle ID from the request
        $vehicleId = $request->vehicle_id;

        $routeId = $request->route_id;

        $dateRange = $this->getDateRange($request->date);
        $warehouse = $this->getWarehouse($request->distribution_id);

        /*
        * If the request contains a 'vehicle_id', it indicates that the request
        * is coming from the Track and Trace module. In this case, we need to
        * fetch vehicle data by calling the getVehicles function.
        *
        * If 'vehicle_id' is null or not present in the request,
        * we set $vehicles to null.
        */
        $vehicles = $this->getVehicles($dateRange, $request->distribution_id, $vehicleId, $routeId);
        $routePolylines = $this->getRoutePolylines($vehicles, $warehouse);

        return response()->json([
            'route_polylines' => $routePolylines,
            'vehicles' => $vehicleId ? $vehicles : [],
            'success' => true
        ], 200);

    }

    /**
     * Get Operation Duration
     *
     * Retrieves the operation duration for a specific customer.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getOperationDuration()
    {
        $opertionDurations = OperationDuration::first();
        return $opertionDurations;
    }

    /**
     * Get Warehouse
     *
     * Retrieves the warehouse for a specific distribution center.
     *
     * @param int $distributionId The ID of the distribution center.
     * @return \Illuminate\Http\JsonResponse
     */
    private function getWarehouse(int $distributionId)
    {
        return Warehouse::find($distributionId);
    }

    private function getTerritories(int $distributionId)
    {
        $cacheKey = "territories_{$distributionId}";

        // return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($distributionId) {
        return Territory::where('warehouse_id', $distributionId)->get();
        // });
    }

    private function getDateRange(string $date): array
    {
        $formattedDate = Carbon::createFromFormat('d/m/Y', $date)->toDateString();
        return [
            'start' => Carbon::parse($formattedDate)->startOfDay(),
            'end' => Carbon::parse($formattedDate)->endOfDay(),
            'dayOfWeek' => Carbon::createFromFormat('d/m/Y', $date)->format('l')
        ];
    }

    private function getOrders(array $dateRange, int $distributionId)
    {
        $cacheKey = "orders_{$distributionId}_{$dateRange['start']}_{$dateRange['end']}";

        // return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($dateRange, $distributionId) {
        return Order::with($this->getOrderRelations())
            ->join('customers', 'orders.cid', '=', 'customers.id')
            ->where('deliverBy_datetime', '>=', $dateRange['start'])
            ->where('deliverBy_datetime', '<=', $dateRange['end'])
            ->where('warehouse_id', $distributionId)
            ->select($this->getOrderSelectFields())
            ->orderByRaw('businessName IS NULL OR businessName = "", businessName ASC')
            ->get();
        // });
    }

    private function getVehicles($dateRange, $distributionId, $vehicleId = null, $routeId =  null)
    {
        $queryResult = Vehicle::with([
            'vehicleSetting',
            'vehicleTerritory',
            'disp' => function ($query) use ($dateRange) {
                $query
                    ->select('van', 'oid', 'num')
                    ->with(['order' => function ($query) use ($dateRange) {
                        $query->leftJoin('disp', 'disp.oid', 'orders.id')
                            ->with([
                                'childOrders' => function ($query) {
                                    $query->childDisp()->orderBy('num', 'ASC');
                                    $query->select([
                                        // Add all the fields from original query
                                        "id",
                                        "ref_no",
                                        "warehouse_id",
                                        "priority",
                                        "vehicle_requirement_id",
                                        "description",
                                        "orderType",
                                        "speed_zone_id",
                                        "territory_id",
                                        "weight",
                                        "volume",
                                        "allowNotifications",
                                        "stop_sequence",
                                        "collection",
                                        "deliverBy_datetime",
                                        "cid",
                                        "operation_duration",
                                        "orderStatus",
                                        "lat",
                                        "lng",
                                        "error",
                                        "return_duration",
                                        "return_distance",
                                        "locked",
                                        "total_distance",
                                        "total_duration",
                                        "max_parent_order_id",
                                        // Time-related fields
                                        "orders.loadingTime",
                                        "orders.startTime",
                                        "orders.waitingTime",
                                        "orders.workStartTime",
                                        "orders.returnStartTime",
                                        "orders.returnWareHouseTime",
                                        "orders.arriveTime",
                                        "orders.operationEndTime",
                                        "orders.runDuration",
                                        "orders.runTotalDrivingTime",
                                        "orders.timeViolation",
                                        "orders.weightExceeded",
                                        "orders.howMuchWeightOver",
                                        "orders.volumeExceeded",
                                        "orders.howMuchVolumeOver",
                                        // Taken fields
                                        "orders.takenloadingTime",
                                        "orders.takenwaitingTime",
                                        "orders.takenworkStartTime",
                                        "orders.takenreturnStartTime",
                                        "orders.takenreturnWareHouseTime",
                                        "orders.takenarriveTime",
                                        "orders.takenoperationEndTime",
                                        "orders.takenrunDuration",
                                        "orders.takenrunTotalDrivingTime",
                                        "orders.takentimeViolation",
                                        "orders.takenweightExceeded",
                                        "orders.takenhowMuchWeightOver",
                                        "orders.takenvolumeExceeded",
                                        "orders.takenhowMuchVolumeOver",
                                        "parent_order_id",
                                        "takenstartTime"
                                    ]);
                                },
                                'childOrders.orderOperationTimeWindow',
                                'childOrders.customer',
                                'childOrders.warehouse',
                                'childOrders.orderItems',
                                'orderOperationTimeWindow',
                                'orderItems',
                                'customer',
                                'warehouse',
                                'orderLogs',
                                'childOrders.orderLogs',
                            ])
                            ->where('deliverBy_datetime', '>=', $dateRange['start'])
                            ->where('deliverBy_datetime', '<', $dateRange['end'])
                            ->where('max_parent_order_id', null)
                            ->select([
                                "id",
                                "ref_no",
                                "warehouse_id",
                                "priority",
                                "vehicle_requirement_id",
                                "description",
                                "orderType",
                                "speed_zone_id",
                                "territory_id",
                                "weight",
                                "volume",
                                "allowNotifications",
                                "stop_sequence",
                                "collection",
                                "deliverBy_datetime",
                                "cid",
                                "operation_duration",
                                "orderStatus",
                                "lat",
                                "lng",
                                "error",
                                "return_duration",
                                "return_distance",
                                "locked",
                                "total_distance",
                                "total_duration",
                                "max_parent_order_id",
                                // Time-related fields
                                "orders.loadingTime",
                                "orders.startTime",
                                "orders.waitingTime",
                                "orders.workStartTime",
                                "orders.returnStartTime",
                                "orders.returnWareHouseTime",
                                "orders.arriveTime",
                                "orders.operationEndTime",
                                "orders.runDuration",
                                "orders.runTotalDrivingTime",
                                "orders.timeViolation",
                                "orders.weightExceeded",
                                "orders.howMuchWeightOver",
                                "orders.volumeExceeded",
                                "orders.howMuchVolumeOver",
                                // Taken fields
                                "orders.takenloadingTime",
                                "orders.takenwaitingTime",
                                "orders.takenworkStartTime",
                                "orders.takenreturnStartTime",
                                "orders.takenreturnWareHouseTime",
                                "orders.takenarriveTime",
                                "orders.takenoperationEndTime",
                                "orders.takenrunDuration",
                                "orders.takenrunTotalDrivingTime",
                                "orders.takentimeViolation",
                                "orders.takenweightExceeded",
                                "orders.takenhowMuchWeightOver",
                                "orders.takenvolumeExceeded",
                                "orders.takenhowMuchVolumeOver",
                                "parent_order_id",
                                "takenstartTime"
                            ]);
                    }]);
            },
            'driver.driverTime' => function ($query) use ($dateRange) {
                $query->where('day', $dateRange['dayOfWeek']);
            },
            'driver.driverTerritory',
            'vehicleCost',
            'warehouse_name',
            'driver.driverLocation' => function ($query) use ($routeId) {
                if ($routeId !== null) {
                    $query->where('route_id', $routeId);
                }
            },
        ])
            ->where('distribution_centre_id', $distributionId);
        if ($vehicleId !== null) {
            $queryResult->where('id', $vehicleId);
        }
        return $queryResult->get();
    }

    private function processVehicleDispatches($vehicles)
    {
        $vehicles->each(function ($vehicle) {
            $vehicle->disp->transform(function ($dispatch) {
                if (!is_null($dispatch->order)) {
                    return array_merge($dispatch->order->toArray());
                }
            });
        });
    }

    /*
    * Change the scope of the `getOrderSelectFields` function from private to public.
    * By doing this, I can access this function from other classes where I need it.
    */
    public function getOrderSelectFields(): array
    {
        return [
            // Basic Order Fields
            "orders.id",
            "orders.ref_no",
            "orders.warehouse_id",
            "orders.priority",
            "orders.vehicle_requirement_id",
            "orders.description",
            "orders.orderType",
            "orders.speed_zone_id",
            "orders.territory_id",
            "orders.weight",
            "orders.volume",
            "orders.allowNotifications",
            "orders.stop_sequence",
            "orders.collection",
            "orders.deliverBy_datetime",
            "orders.operation_duration",
            "orders.lat",
            "orders.lng",
            "orders.locked",
            "orders.orderStatus",
            "orders.error",
            "orders.cid",
            "orders.parent_order_id",
            "orders.total_distance",
            "orders.total_duration",

            // Customer Info
            DB::raw('COALESCE(customers.businessName, "") as businessName')
        ];
    }

    private function getOrderRelations(): array
    {
        return [
            'orderOperationTimeWindow',
            'vehicleRequirement',
            'childOrders',
            'orderLogs',
            'customer' => function ($query) {
                $query->select(['id', 'businessName', 'mob', 'number', 'email1', 'web', 'fax', 'street', 'postcode']);
            },
            'warehouse',
            'territory',
            'orderItems' => function ($query) {
                $query->select([
                    'id',
                    'oid',
                    'itemid',
                    'bedsName',
                    'notes',
                    'price',
                    'Qty',
                    'barcode',
                    'itemStatus',
                    'item_actual_quantity'
                ]);
            },
            'orderItems.stock.stockDetail',
        ];
    }

    private function buildDispatchQuery($query, array $dateRange)
    {
        $query->select('van', 'oid', 'num')
            ->with(['order' => function ($query) use ($dateRange) {
                $this->buildOrderQuery($query, $dateRange);
            }]);
    }

    private function buildOrderQuery($query, array $dateRange)
    {
        $query->leftJoin('disp', 'disp.oid', 'orders.id')
            ->leftJoin('orderItems', 'orders.id', '=', 'orderItems.oid')
            ->leftJoin('stock_items', 'orderItems.itemid', '=', 'stock_items.id')
            ->leftJoin('customers', 'orders.cid', '=', 'customers.id')
            ->with($this->getOrderRelations())
            ->where('deliverBy_datetime', '>=', $dateRange['start'])
            ->where('deliverBy_datetime', '<', $dateRange['end'])
            ->where('max_parent_order_id', null)
            ->select($this->getOrderSelectFields());
    }

    /**
     * Toggle Order Status
     *
     * Toggles the status of an order between allocated and routed states, handling parent/child relationships.
     *
     * @authenticated
     *
     * @bodyParam order_id integer required The ID of the order to toggle. Example: 1
     * @bodyParam parent boolean optional Whether to include child orders in the toggle. Example: true
     *
     * @response 200 {
     *     "message": "order unallocated successfully"
     * }
     *
     * @response 404 {
     *     "message": "order not found"
     * }
     */
    public function toggleOrderStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'parent' => 'boolean'
        ]);

        try {


            $order_id = $request->order_id;
            $isDispatched = Disp::where('oid', $order_id)->first();

            if (!$isDispatched) {
                return response()->json(['message' => "order not found", 'success' => false], 200);
            }

            $givenOrder = Order::findOrFail($order_id);

            $requestUser = $this->getRequestUser($request);

            if ($request->parent) {
                $this->handleParentOrderToggle($givenOrder, $requestUser);
            } else {
                $this->handleSingleOrderToggle($givenOrder, $requestUser);
            }


            return response()->json(['message' => "order unallocated successfully"], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function getRequestUser(Request $request): User
    {
        return auth()->user();
    }

    private function handleParentOrderToggle(Order $givenOrder, User $requestUser): void
    {
        // Get vehicle route info before unallocating children
        $parentDisp = Disp::where('oid', $givenOrder->id)->first();

        $childOrders = Order::where('max_parent_order_id', $givenOrder->id)->get();
        $childOrders->each(fn($order) => $this->unallocateOrder($order, $requestUser));

        // Unallocate parent after children to maintain vehicle sequence
        $this->unallocateOrder($givenOrder, $requestUser);

        // Resequence entire vehicle route if parent was part of one
        if ($parentDisp) {
            $this->resequenceVehicleRoute($parentDisp->van, $parentDisp->id_route);
        }
    }

    private function handleSingleOrderToggle(Order $givenOrder, User $requestUser)
    {

        $distanceHelper = new MapsHelper();

        if ($givenOrder->max_parent_order_id === null) {
            $this->handleParentOrderUnallocation($givenOrder, $distanceHelper);
        } else {
            $this->handleChildOrderUnallocation($givenOrder, $distanceHelper);
        }
        // send notification to the driver  Order is Unassigned
        if ($givenOrder->locked == 2 || $givenOrder->max_parent_order_id !==  null) {
            $value = 2;
            FcmFunctionHelper::sendDetailsToDriverOrderUnassigned($givenOrder->id, $value);
        }
        $this->unallocateOrder($givenOrder, $requestUser);
    }

    private function handleParentOrderUnallocation(Order $givenOrder, MapsHelper $distanceHelper): void
    {
        $childOrders = Order::with(['childOrders' => function ($query) {
            $query->select('orders.*')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('orders.parent_order_id', '!=', null)
                ->orderBy('disp.num', 'ASC')
                ->first();;
        }])->find($givenOrder->id);

        if ($childOrders->childOrders()->count() > 0) {
            $firstChildOrder = $childOrders->childOrders()->first();
            $this->reassignChildOrders($firstChildOrder, $givenOrder->id);
            $this->updateDispatchSequence($firstChildOrder);
            $distanceHelper->calculateDistanceAndTime($firstChildOrder->id);
        }
    }

    private function handleChildOrderUnallocation(Order $givenOrder, MapsHelper $distanceHelper): void
    {
        $parent_order = $givenOrder->max_parent_order_id;
        $distanceHelper->calculateDistanceAndTime($parent_order);
    }

    private function reassignChildOrders(Order $firstChildOrder, int $parentOrderId): void
    {
        $firstChildOrder->max_parent_order_id = null;
        $firstChildOrder->save();

        Order::where('max_parent_order_id', $parentOrderId)
            ->where('id', '!=', $firstChildOrder->id)
            ->update(['max_parent_order_id' => $firstChildOrder->id]);
    }

    private function updateDispatchSequence(Order $order): void
    {
        DB::table('disp')
            ->where('oid', $order->id)
            ->update(['num' => 0]);
    }

    private function unallocateOrder(Order $order, User $requestUser): void
    {
        $dispEntry = Disp::where('oid', $order->id)->first();
        if (!$dispEntry) return;

        $parentOrderId = $order->max_parent_order_id;
        $deletedPosition = $dispEntry->num;
        $van = $dispEntry->van;
        $routeId = $dispEntry->id_route;

        Disp::where('oid', $order->id)->delete();

        $order->update([
            'orderStatus' => 'Allocated',
            'max_parent_order_id' => null,
            'locked' => 1
        ]);

        // Resequence based on context
        if ($parentOrderId) {
            // Child order - resequence parent group
            DB::table('disp')
                ->join('orders', 'disp.oid', '=', 'orders.id')
                ->where('orders.max_parent_order_id', $parentOrderId)
                ->where('disp.num', '>', $deletedPosition)
                ->decrement('disp.num');
        } else {
            // Parent order - resequence entire vehicle route
            $this->resequenceVehicleRoute($van, $routeId);
        }

        OrderLogger::log(
            orderId: $order->id,
            action: OrderAction::STATUS_CHANGED->value,
            status: 'Allocated',
            eta: null
        );
    }

    private function resequenceVehicleRoute($van, $routeId): void
    {
        $dispatches = Disp::where('van', $van)
            ->where('id_route', $routeId)
            ->orderBy('num')
            ->get();

        $sequenceNumber = 0;
        foreach ($dispatches as $dispatch) {
            $dispatch->update(['num' => $sequenceNumber++]);
        }
    }

    /**
     * Undo Last Action
     *
     * Retrieves and processes the last action for undo operation.
     *
     * @authenticated
     *
     * @response 200 {
     *     "response": {
     *         "orders": [],
     *         "timeline": []
     *     },
     *     "undoCount": true,
     *     "redoCount": true
     * }
     *
     * @response 200 {
     *     "undoCount": false,
     *     "redoCount": true
     * }
     */
    public function undo(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);

        $actionCounts = $this->getActionCounts($user->id);

        $undo = History::where([
            'action' => 0,
            'user_id' => $user->id
        ])->latest()->first();

        if ($undo) {
            $response = $this->OrderListAndTimeline($undo);
            return response()->json([
                'response' => $response,
                'undoCount' => $actionCounts['undoGreaterThanZero'],
                'redoCount' => $actionCounts['redoGreaterThanZero']
            ]);
        }

        return response()->json([
            'undoCount' => false,
            'redoCount' => true
        ]);
    }

    private function getActionCounts(int $userId): array
    {
        $undoCount = History::where([
            'action' => 0,
            'user_id' => $userId
        ])->count();

        $redoCount = History::where([
            'action' => 1,
            'user_id' => $userId
        ])->count();

        return [
            'undoGreaterThanZero' => $undoCount > 0,
            'redoGreaterThanZero' => $redoCount > 0
        ];
    }

    /**
     * Redo Last Action
     *
     * Retrieves and processes the last undone action for redo operation.
     *
     * @authenticated
     *
     * @response 200 {
     *     "response": {
     *         "orders": [],
     *         "timeline": []
     *     },
     *     "undoCount": true,
     *     "redoCount": true
     * }
     *
     * @response 200 {
     *     "undoCount": true,
     *     "redoCount": false
     * }
     */
    public function redo(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);

        $actionCounts = $this->getActionCounts($user->id);

        $redo = History::where([
            'action' => 1,
            'user_id' => $user->id
        ])->first();

        if ($redo) {
            $response = $this->OrderListAndTimeline($redo);
            return response()->json([
                'response' => $response,
                'undoCount' => $actionCounts['undoGreaterThanZero'],
                'redoCount' => $actionCounts['redoGreaterThanZero']
            ]);
        }

        return response()->json([
            'undoCount' => true,
            'redoCount' => false
        ]);
    }

    private function OrderListAndTimeline(History $undoAction): JsonResponse
    {
        if ($undoAction->from === 'Allocated' && $undoAction->to === 'Routed') {
            return $this->handleAllocatedToRouted($undoAction);
        }

        if ($undoAction->from === 'Routed' && $undoAction->to === 'Allocated') {
            return $this->handleRoutedToAllocated($undoAction);
        }

        if ($undoAction->from === 'Routed' && $undoAction->to === 'Routed') {
            return $this->handleRoutedToRouted($undoAction);
        }

        return response()->json('No matching action found');
    }

    private function handleAllocatedToRouted(History $undoAction): JsonResponse
    {
        Disp::where('oid', $undoAction->oid)->delete();

        $order = Order::find($undoAction->oid);
        $order->update([
            'orderStatus' => 'Allocated'
        ]);

        // Log undo action
        OrderLogger::log(
            orderId: $undoAction->oid,
            action: OrderAction::STATUS_CHANGED->value,
            status: 'Allocated (Undo)',
            eta: null
        );

        $this->toggleAction($undoAction, 'Routed', 'Allocated');

        return response()->json('case 1');
    }

    private function handleRoutedToAllocated(History $undoAction): JsonResponse
    {
        Disp::create([
            'oid' => $undoAction->oid,
            'van' => $undoAction->vehicle_id_current,
            'id_route' => $undoAction->id_disp_route,
        ]);

        $order = Order::find($undoAction->oid);
        $order->update([
            'orderStatus' => 'Routed'
        ]);

        // Log undo action
        OrderLogger::log(
            orderId: $undoAction->oid,
            action: OrderAction::STATUS_CHANGED->value,
            status: 'Routed (Undo)',
            eta: null
        );

        $this->toggleAction($undoAction, 'Allocated', 'Routed');

        return response()->json('case 2');
    }

    private function handleRoutedToRouted(History $undoAction): JsonResponse
    {
        $dispatch = Disp::where('oid', $undoAction->oid)->first();

        if ($dispatch) {
            $vehicleId = $undoAction->action === 1
                ? $undoAction->vehicle_id_current
                : $undoAction->vehicle_id_previous;

            $dispatch->van = $vehicleId;
            $dispatch->save();

            // Log vehicle change
            OrderLogger::log(
                orderId: $undoAction->oid,
                action: OrderAction::UPDATED->value,
                status: 'Vehicle Changed (Undo)',
                eta: null
            );

            $this->toggleAction($undoAction);
        }

        return response()->json('case 3');
    }

    private function toggleAction(History $undoAction, ?string $from = null, ?string $to = null): void
    {
        $updates = ['action' => $undoAction->action ? 0 : 1];

        if ($from && $to) {
            $updates['from'] = $from;
            $updates['to'] = $to;
        }

        $undoAction->update($updates);
    }

    /**
     * Search Orders, Vehicles, and Drivers
     *
     * Search for orders, vehicles, and drivers based on search criteria.
     *
     * @group Planning Tool Management
     *
     * @queryParam searchInput string required Search term for filtering. Example: "ABC123"
     * @queryParam warehouse_id integer required Warehouse ID to filter by. Example: 1
     *
     * @response {
     *   "orders": [{
     *     "id": 1,
     *     "ref_no": "ABC123",
     *     "description": "Test order",
     *     "warehouse_id": 1,
     *     "orderItems": [{
     *       "id": 1,
     *       "order_id": 1,
     *       "quantity": 5
     *     }]
     *   }],
     *   "drivers": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "active": 1,
     *     "distribution_centre": 1
     *   }],
     *   "vehicles": [{
     *     "id": 1,
     *     "name": "Truck 1",
     *     "reg": "ABC123",
     *     "distribution_centre_id": 1,
     *     "driver": {
     *       "id": 1,
     *       "name": "John Doe"
     *     }
     *   }],
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "warehouse_id": ["The warehouse id field is required."]
     *   },
     *   "success": false
     * }
     */
    public function searchOrderVehiclesDrivers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'searchInput' => 'required|string|max:255',
            'warehouse_id' => 'required|integer|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $search = $request->searchInput;
            $warehouseId = $request->warehouse_id;

            $orders = Order::with('orderItems')
                ->where('warehouse_id', $warehouseId)
                ->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', '%' . $search . '%')
                        ->orWhere('ref_no', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                })
                ->get();

            $drivers = Driver::where('active', '1')
                ->where('distribution_centre', $warehouseId)
                ->where('name', 'LIKE', '%' . $search . '%')
                ->get();

            $vehicles = Vehicle::with('driver')
                ->where('distribution_centre_id', $warehouseId)
                ->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('reg', 'LIKE', '%' . $search . '%');
                })
                ->get();

            return response()->json([
                'orders' => $orders,
                'drivers' => $drivers,
                'vehicles' => $vehicles,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            Log::error('Search failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to perform search',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Optimize Vehicle Routes
     *
     * Optimizes routes for multiple vehicles by processing and updating order assignments,
     * distances, and durations.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function optimize(Request $request): JsonResponse
    {
        try {


            $validator = Validator::make($request->all(), [
                'allVehicles' => 'required|array',
                'allVehicles.*.vehicleId' => 'required|exists:vehicles,id',
                'allVehicles.*.driverId' => 'required|exists:drivers,id',
                'allVehicles.*.orders' => 'required|array',
                'allVehicles.*.orders.*.id' => 'required|exists:orders,id',
                'allVehicles.*.orders.*.distance' => 'required|numeric',
                'allVehicles.*.orders.*.duration' => 'required|numeric',
                'allVehicles.*.orders.*.returnDistance' => 'required|numeric',
                'allVehicles.*.orders.*.returnDuration' => 'required|numeric',
                'selectedDate' => 'required|date_format:d/m/Y',
                'selectedDistCenterId' => 'required|exists:warehouses,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $formattedDate = Carbon::createFromFormat('d/m/Y', $request->selectedDate)
                ->format('d-M-Y');

            // Calculate total orders for all vehicles
            $totalOrderCount = 0;
            foreach ($request->allVehicles as $route) {
                $totalOrderCount +=  $this->processVehicleRoute($route, $formattedDate);
            }


            return response()->json([
                'totalPlannedOrders'  => empty($request->allVehicles) ? 0  : $totalOrderCount ,
                'success' => true,
                'message' => 'Routes optimized successfully'
            ]);
        } catch (\Exception $e) {

            Log::error('Error in optimize function', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize routes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process individual vehicle route
     */
    private function processVehicleRoute(array $route, string $formattedDate): int
    {
        $vehicleId = $route['vehicleId'];
        $orders = $route['orders'];
        $firstOrderId = $orders[0]['id'] ?? null;
        $totalOrders = count($orders);

        // Create or update dispatch route
        $dispatchRoute = $this->createDispatchRoute($vehicleId, $formattedDate, $route['driverId']);

        foreach ($orders as $index => $order) {
            $this->processOrderAssignment(
                $order,
                $vehicleId,
                $formattedDate,
                $dispatchRoute->id,
                $index,
                $firstOrderId
            );
        }

        // Log route optimization for first order
        if ($firstOrderId) {
            OrderLogger::log(
                orderId: $firstOrderId,
                action: OrderAction::STATUS_CHANGED->value,
                status: 'Route Optimized',
                eta: null
            );
        }

        return $totalOrders;
    }

    /**
     * Create or update dispatch route
     */
    private function createDispatchRoute(int $vehicleId, string $formattedDate, int $driverId): DispRoute
    {
        DispRoute::where([
            'vehicle' => $vehicleId,
            'date' => $formattedDate
        ])->delete();

        return DispRoute::create([
            'vehicle' => $vehicleId,
            'date' => $formattedDate,
            'driver_id' => $driverId,
            'status' => 'New'
        ]);
    }

    /**
     * Process individual order assignment
     */
    private function processOrderAssignment(
        array $orderData,
        int $vehicleId,
        string $formattedDate,
        int $routeId,
        int $index,
        ?int $firstOrderId
    ): void {
        // Delete existing dispatch if exists
        Disp::where([
            'oid' => $orderData['id'],
            'van' => $vehicleId
        ])->delete();

        // Create new dispatch
        Disp::create([
            'oid' => $orderData['id'],
            'van' => $vehicleId,
            'date' => $formattedDate,
            'num' => $index,
            'id_route' => $routeId
        ]);

        $order = Order::find($orderData['id']);
        $oldStatus = $order->orderStatus;
        $oldParentId = $order->max_parent_order_id;

        // Update order details
        $order->update([
            'max_parent_order_id' => $index === 0 ? null : $firstOrderId,
            'orderStatus' => 'Routed',
            'total_distance' => $orderData['distance'],
            'total_duration' => $orderData['duration'],
            'return_distance' => $orderData['returnDistance'],
            'return_duration' => $orderData['returnDuration']
        ]);

        // Log status change if status changed
        if ($oldStatus !== 'Routed') {
            OrderLogger::log(
                orderId: $orderData['id'],
                action: OrderAction::STATUS_CHANGED->value,
                status: 'Routed',
                eta: null
            );
        }

        // Log parent order change if changed
        if ($oldParentId !== ($index === 0 ? null : $firstOrderId)) {
            OrderLogger::log(
                orderId: $orderData['id'],
                action: OrderAction::UPDATED->value,
                status: 'Parent Order Changed',
                eta: null
            );
        }
    }

    /**
     * Get All Cached Planning Tool Routes
     *
     * Retrieves all cached routes from the planning tool cache.
     *
     * @authenticated
     *
     * @response {
     *   "success": true,
     *   "data": [
     *     {
     *       "route_id": "string",
     *       "origin_lat": "string",
     *       "origin_lng": "string",
     *       "destination_lat": "string",
     *       "destination_lng": "string",
     *       "points": "json_string",
     *       "origin": [float, float],
     *       "destination": [float, float]
     *     }
     *   ]
     * }
     */
    public function getAllPlanningToolRoutesCache()
    {
        $points = DB::table('planning_tool_routes_cache')->get();

        foreach ($points as $point) {
            $point->origin = [
                (float)$point->origin_lat,
                (float)$point->origin_lng
            ];
            $point->destination = [
                (float)$point->destination_lat,
                (float)$point->destination_lng
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $points
        ], 200);
    }


    /**
     * Get Specific Cached Planning Tool Route
     *
     * Retrieves a specific cached route based on origin and destination coordinates.
     *
     * @authenticated
     *
     * @bodyParam destination_lat string required The latitude of destination point. Example: "51.5074"
     * @bodyParam destination_lng string required The longitude of destination point. Example: "-0.1278"
     * @bodyParam origin_lat string required The latitude of origin point. Example: "51.5074"
     * @bodyParam origin_lng string required The longitude of origin point. Example: "-0.1278"
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "route_id": "string",
     *     "origin_lat": "string",
     *     "origin_lng": "string",
     *     "destination_lat": "string",
     *     "destination_lng": "string",
     *     "points": "json_string",
     *     "origin": [float, float],
     *     "destination": [float, float]
     *   }
     * }
     *
     * @response 200 {
     *   "success": false,
     *   "message": "No points found"
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "destination_lat": ["The destination lat field is required."],
     *     "destination_lng": ["The destination lng field is required."],
     *     "origin_lat": ["The origin lat field is required."],
     *     "origin_lng": ["The origin lng field is required."]
     *   }
     * }
     */
    public function getPlanningToolRoutesCache(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'destination_lat' => 'required|string|max:255',
            'destination_lng' => 'required|string|max:255',
            'origin_lat' => 'required|string|max:255',
            'origin_lng' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }


        $points = DB::table('planning_tool_routes_cache')
            ->where('destination_lat', $request->destination_lat)
            ->where('destination_lng', $request->destination_lng)
            ->where('origin_lat', $request->origin_lat)
            ->where('origin_lng', $request->origin_lng)
            ->first();

        if (!$points) {
            return response()->json([
                'success' => false,
                'message' => 'No points found'
            ], 200);
        }

        $points->origin = [
            (float)$points->origin_lat,
            (float)$points->origin_lng
        ];
        $points->destination = [
            (float)$points->destination_lat,
            (float)$points->destination_lng
        ];

        return response()->json([
            'success' => true,
            'data' => $points
        ], 200);
    }

    /**
     * Create Planning Tool Route Cache
     *
     * Creates a new cached route entry with origin, destination and route points.
     *
     * @authenticated
     *
     * @bodyParam route_id string required The unique identifier for the route. Example: "route_123"
     * @bodyParam origin_lat string required The latitude of origin point. Example: "51.5074"
     * @bodyParam origin_lng string required The longitude of origin point. Example: "-0.1278"
     * @bodyParam destination_lat string required The latitude of destination point. Example: "51.5074"
     * @bodyParam destination_lng string required The longitude of destination point. Example: "-0.1278"
     * @bodyParam points array required Array of route points. Example: [[51.5074, -0.1278], [51.5075, -0.1279]]
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Route cache created successfully"
     * }
     *
     * @response 200 {
     *   "success": false,
     *   "message": "Route already exists"
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "route_id": ["The route id field is required."],
     *     "origin_lat": ["The origin lat field is required."],
     *     "origin_lng": ["The origin lng field is required."],
     *     "destination_lat": ["The destination lat field is required."],
     *     "destination_lng": ["The destination lng field is required."],
     *     "points": ["The points field is required."]
     *   }
     * }
     */
    public function createPlanningToolRoutesCache(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|string|max:255',
            'origin_lat' => 'required|string|max:255',
            'origin_lng' => 'required|string|max:255',
            'destination_lat' => 'required|string|max:255',
            'destination_lng' => 'required|string|max:255',
            'points' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $routes = DB::table('planning_tool_routes_cache')
            ->where('destination_lat', $request->destination_lat)
            ->where('destination_lng', $request->destination_lng)
            ->where('origin_lat', $request->origin_lat)
            ->where('origin_lng', $request->origin_lng)
            ->first();

        if ($routes) {
            return response()->json([
                'success' => false,
                'message' => 'Route already exists'
            ], 200);
        }


        DB::table('planning_tool_routes_cache')->insert([
            'route_id' => $request->route_id,
            'origin_lat' => $request->origin_lat,
            'origin_lng' => $request->origin_lng,
            'destination_lat' => $request->destination_lat,
            'destination_lng' => $request->destination_lng,
            'points' => json_encode($request->points)
        ]);

        Cache::forget('planning_tool_points');

        return response()->json([
            'success' => true,
            'message' => 'Route cache created successfully'
        ], 200);
    }

    public function getPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'routes' => 'required|array',
            'routes.*.origin' => 'required|array',
            'routes.*.origin.lat' => 'required|numeric',
            'routes.*.origin.lng' => 'required|numeric',
            'routes.*.destination' => 'required|array',
            'routes.*.destination.lat' => 'required|numeric',
            'routes.*.destination.lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $routes = $request->routes;
        $results = [];

        foreach ($routes as $route) {
            $originLat = (string)$route['origin']['lat'];
            $originLng = (string)$route['origin']['lng'];
            $destLat = (string)$route['destination']['lat'];
            $destLng = (string)$route['destination']['lng'];

            // Try to get points from cache first
            $cacheKey = "route_{$originLat}_{$originLng}_{$destLat}_{$destLng}";

            $points = Cache::remember($cacheKey, 300, function () use ($originLat, $originLng, $destLat, $destLng) {
                return DB::table('planning_tool_routes_cache')
                    ->where('origin_lat', $originLat)
                    ->where('origin_lng', $originLng)
                    ->where('destination_lat', $destLat)
                    ->where('destination_lng', $destLng)
                    ->first();
            });

            if ($points) {
                $points->origin = [
                    (float)$points->origin_lat,
                    (float)$points->origin_lng
                ];
                $points->destination = [
                    (float)$points->destination_lat,
                    (float)$points->destination_lng
                ];

                // Decode the JSON points string into an array
                $points->points = json_decode($points->points, true);

                $results[] = $points;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ], 200);
    }

    private function getRoutePolylines($vehicles, $warehouse): array
    {
        $mapsHelper = new MapsHelper();
        $routes = [];
        $warehousePosition = [
            'lat' => (float)$warehouse->latitude,
            'lng' => (float)$warehouse->longitude
        ];

        foreach ($vehicles as $vehicle) {
            // Skip if vehicle has no dispatches
            if ($vehicle->disp->isEmpty()) {
                continue;
            }

            foreach ($vehicle->disp as $dispatch) {
                $order = $dispatch->order;

                // Skip if order doesn't exist or doesn't have coordinates
                if (!$order || !$order->lat || !$order->lng) {
                    continue;
                }

                // Get warehouse to order route
                $routes[] = $this->getOrCreateRoutePolyline(
                    $warehousePosition,
                    [
                        'lat' => (float)$order->lat,
                        'lng' => (float)$order->lng
                    ],
                    $mapsHelper
                );

                // Get routes between order and its child orders
                if ($order->childOrders && $order->childOrders->count() > 0) {
                    $previousOrder = $order;
                    foreach ($order->childOrders as $childOrder) {
                        if (!$childOrder->lat || !$childOrder->lng) {
                            continue;
                        }

                        $routes[] = $this->getOrCreateRoutePolyline(
                            [
                                'lat' => (float)$previousOrder->lat,
                                'lng' => (float)$previousOrder->lng
                            ],
                            [
                                'lat' => (float)$childOrder->lat,
                                'lng' => (float)$childOrder->lng
                            ],
                            $mapsHelper
                        );

                        $previousOrder = $childOrder;
                    }

                    // Add route from last child order back to warehouse
                    $lastOrder = $order->childOrders->last();
                    if ($lastOrder && $lastOrder->lat && $lastOrder->lng) {
                        $routes[] = $this->getOrCreateRoutePolyline(
                            [
                                'lat' => (float)$lastOrder->lat,
                                'lng' => (float)$lastOrder->lng
                            ],
                            $warehousePosition,
                            $mapsHelper
                        );
                    }
                } else {
                    // Add return route to warehouse for orders without children
                    $routes[] = $this->getOrCreateRoutePolyline(
                        [
                            'lat' => (float)$order->lat,
                            'lng' => (float)$order->lng
                        ],
                        $warehousePosition,
                        $mapsHelper
                    );
                }
            }
        }

        return array_filter($routes); // Remove null values
    }

    private function getOrCreateRoutePolyline(array $origin, array $destination, MapsHelper $mapsHelper): ?array
    {
        // Check cache first
        $cachedRoute = DB::table('planning_tool_routes_cache')
            ->where('origin_lat', (string)$origin['lat'])
            ->where('origin_lng', (string)$origin['lng'])
            ->where('destination_lat', (string)$destination['lat'])
            ->where('destination_lng', (string)$destination['lng'])
            ->first();

        if ($cachedRoute) {
            return [
                'route_id' => $cachedRoute->route_id,
                'origin' => [
                    'lat' => (float)$cachedRoute->origin_lat,
                    'lng' => (float)$cachedRoute->origin_lng
                ],
                'destination' => [
                    'lat' => (float)$cachedRoute->destination_lat,
                    'lng' => (float)$cachedRoute->destination_lng
                ],
                'points' => json_decode($cachedRoute->points, true)
            ];
        }

        // Get route from OSRM if not cached
        $osrmRoute = $mapsHelper->getOSRMPolyline($origin, $destination);
        if ($osrmRoute) {
            $routeId = uniqid('route_', true);

            // Cache the route
            DB::table('planning_tool_routes_cache')->insert([
                'route_id' => $routeId,
                'origin_lat' => (string)$origin['lat'],
                'origin_lng' => (string)$origin['lng'],
                'destination_lat' => (string)$destination['lat'],
                'destination_lng' => (string)$destination['lng'],
                'points' => json_encode($osrmRoute['coordinates']),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return [
                'route_id' => $routeId,
                'origin' => $origin,
                'destination' => $destination,
                'points' => $osrmRoute['coordinates']
            ];
        }

        return null;
    }
}
