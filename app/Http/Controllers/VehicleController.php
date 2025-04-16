<?php

namespace App\Http\Controllers;

use App\Models\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\DispRoute;
use App\Models\Order;
use App\Models\User;
use App\Helpers\MapsHelper;
use App\Models\Disp;
use App\Models\VehicleSetting;
use App\Models\DriverDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\TerritoryVehicle;
use Illuminate\Support\Facades\Log;
use App\Helpers\OrderLogger;
use App\Enums\OrderAction;
use App\Helpers\FcmFunctionHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\OrderMergeService;

class VehicleController extends Controller
{
    /**
     * Get Vehicle Orders
     *
     * Retrieves the 10 most recent orders assigned to a specific vehicle.
     *
     * @authenticated
     *
     * @queryParam vehicle_id integer required The ID of the vehicle to get orders for. Example: 1
     *
     * @response 200 {
     *     "data": [{
     *         "id_disp": 1,
     *         "van": 1,
     *         "oid": 1,
     *         "order": {
     *             "id": 1,
     *             "ref_no": "ORD123",
     *             "customer": {
     *                 "id": 1,
     *                 "businessName": "ACME Corp"
     *             },
     *             "orderOpertionTimeWindow": [{
     *                 "id": 1,
     *                 "start_time": "09:00:00",
     *                 "end_time": "17:00:00"
     *             }],
     *             "warehouse": {
     *                 "id": 1,
     *                 "name": "Main Warehouse"
     *             }
     *         }
     *     }]
     * }
     *
     * @response 422 {
     *     "message": "The vehicle id field is required.",
     *     "errors": {
     *         "vehicle_id": ["The vehicle id field is required."]
     *     }
     * }
     */
    public function getOrderOfVehicle(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id'
        ]);

        $dispatched = Disp::with(['order' => function ($query) {
            $query->with([
                'customer',
                'orderOperationTimeWindow',
            ]);
        }])
            ->where('van', $request->vehicle_id)
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($dispatched);
    }

    /**
     * Assign Order to Vehicle
     *
     * Assigns an order to a specific vehicle, handling parent/child order relationships
     * and recalculating distances.
     *
     * @authenticated
     *
     * @bodyParam vehicle_id integer required The ID of the vehicle to assign the order to. Example: 1
     * @bodyParam orders object required The order information.
     * @bodyParam orders.id integer required The ID of the order to assign. Example: 1
     * @bodyParam Parent boolean optional Whether to include child orders in the assignment. Example: true
     *
     * @response 200 {
     *     "message": "order assigned successfully"
     * }
     *
     * @response 404 {
     *     "errors": "Order not found"
     * }
     *
     * @response 500 {
     *     "error": "Already assigned to this vehicle, please manage index to change the position"
     * }
     */
    public function assignOrderToVehicle(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'orders' => 'required|array',
            'orders.*' => 'required|exists:orders,id',
            'Parent' => 'boolean',
            'date' => 'required|date_format:d/m/Y',
            'driver_id' => 'required|exists:drivers,id',
            'orderTimelineSequencing' => 'sometimes|boolean'
        ]);

        try {


            // New parameter handling
            $orderTimelineSequencing = $request->input('orderTimelineSequencing', false);
            $sequencingResult = null;

            // Get the first order and remaining orders
            $firstOrderId = $request->orders[0];
            $remainingOrders = array_slice($request->orders, 1);

            $formattedDate = Carbon::createFromFormat('d/m/Y', $request->date)->format('d-M-Y');
            $vehicle = $request->vehicle_id;
            $driverId = $request->driver_id;
            $parent = $request->Parent ?? false;

            // Process first order
            $givenOrder = Order::with(['childOrders' => function ($query) {
                $query->join('disp', 'id', '=', 'disp.oid')
                    ->orderBy('num', 'ASC');
            }])->findOrFail($firstOrderId);

            // Log order status change
            OrderLogger::log(
                orderId: $firstOrderId,
                action: OrderAction::STATUS_CHANGED->value,
                status: 'Routed',
                eta: null
            );

            $distanceHelper = new MapsHelper();

            // Check existing assignments for first order
            $alreadyAssignedToSameVehicle = Disp::where(['oid' => $firstOrderId, 'van' => $vehicle])->first();
            $alreadyAssignedToDifferentVehicle = Disp::where('oid', $firstOrderId)->first();

            // Handle first order assignment
            if ($alreadyAssignedToSameVehicle) {
                $result = $this->handleSameVehicleAssignment($givenOrder, $parent, $distanceHelper, $orderTimelineSequencing);
                if (!$result) {

                    return response()->json(['errors' => "Failed to handle same vehicle assignment"], 500);
                }
            } elseif ($alreadyAssignedToDifferentVehicle) {
                $result = $this->handleDifferentVehicleAssignment($givenOrder, $vehicle, $parent, $distanceHelper, $alreadyAssignedToDifferentVehicle, $driverId, $formattedDate, $orderTimelineSequencing);
                if (!$result) {

                    return response()->json(['errors' => "Failed to handle different vehicle assignment"], 500);
                }
            } else {
                $result = $this->handleNewAssignment($givenOrder, $vehicle, $formattedDate, $distanceHelper, $request, $orderTimelineSequencing);
                if (!$result) {

                    return response()->json(['errors' => "Failed to handle new assignment"], 500);
                }
            }

            // Process remaining orders if any
            if (!empty($remainingOrders)) {
                // Create a new request object with remaining orders
                $mergeRequest = new \Illuminate\Http\Request();
                $mergeRequest->merge([
                    'orders' => $remainingOrders
                ]);

                $orderMergeService = new OrderMergeService();
                $mergeResults = $orderMergeService->processDispatchedOrders($mergeRequest, $firstOrderId);

                if ($mergeResults['status'] === 'failed') {

                    return response()->json(['errors' => $mergeResults['message']], 500);
                }
            }

            $sequencingResult = null;
            // New sequencing logic
            if ($orderTimelineSequencing) {
                $allOrderIds = array_merge([$firstOrderId], $remainingOrders);
                $ordersForSequencing = Order::with('warehouse')
                    ->whereIn('id', $allOrderIds)
                    ->get(['id', 'lat', 'lng', 'warehouse_id']);

                if ($ordersForSequencing->isEmpty()) {
                    throw new \Exception('No valid orders found for sequencing');
                }

                $sequencingRequest = new \Illuminate\Http\Request([
                    'all_order' => $ordersForSequencing->map(fn($order) => [
                        'id' => $order->id,
                        'position' => [(float)$order->lat, (float)$order->lng]
                    ])->toArray(),
                    'warehouse_id' => $ordersForSequencing->first()->warehouse_id
                ]);

                // Call existing sequencing functionality
                $sequencingResponse = app()->make(\App\Http\Controllers\OrderController::class)
                    ->reSequenceTimeline($sequencingRequest);

                if ($sequencingResponse->getStatusCode() !== 200) {
                    throw new \Exception('Order sequencing failed: ' . $sequencingResponse->getContent());
                }

                $sequencingResult = $sequencingResponse->getData();
            }


            return response()->json([
                'message' => 'Orders assigned successfully',
                'first_order_result' => $result,
                'merge_results' => $mergeResults ?? null,
                'sequencing_result' => $sequencingResult
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'errors' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }

    private function handleSameVehicleAssignment(Order $givenOrder, bool $isParent, MapsHelper $distanceHelper, bool $orderTimelineSequencing): ?JsonResponse
    {
        if ($isParent) {
            return response()->json(['error' => 'Already assigned to this vehicle, please manage index to change the position'], 500);
        }

        if ($givenOrder->max_parent_order_id === null) {
            return $this->handleParentOrderReassignment($givenOrder, $distanceHelper, $orderTimelineSequencing);
        } else {
            return $this->handleChildOrderReassignment($givenOrder, $distanceHelper, $orderTimelineSequencing);
        }
    }

    private function handleDifferentVehicleAssignment(
        Order $givenOrder,
        int $vehicle,
        bool $isParent,
        MapsHelper $distanceHelper,
        Disp $dispatch,
        int $driverId,
        string $formattedDate,
        bool $orderTimelineSequencing
    ): ?JsonResponse {

        // Reassign the entire run to another vehicle
        if ($isParent) {
            return $this->reassiginEntireToOtherVehicle($givenOrder, $vehicle, $dispatch, $driverId, $formattedDate, $orderTimelineSequencing);
        }

        if ($givenOrder->max_parent_order_id === null) {

            $this->handleParentOrderVehicleChange($givenOrder, $vehicle, $dispatch, $driverId, $formattedDate, $orderTimelineSequencing);

            return $this->handleParentOrderNewVehicle($givenOrder, $vehicle, $dispatch, $distanceHelper, $driverId, $formattedDate, $orderTimelineSequencing);
        } else {
            return $this->handleChildOrderNewVehicle($givenOrder, $vehicle, $dispatch, $distanceHelper, $driverId, $formattedDate, $orderTimelineSequencing);
        }
    }

    private function handleNewAssignment(
        Order $givenOrder,
        int $vehicle,
        string $formattedDate,
        MapsHelper $distanceHelper,
        Request $request,
        bool $orderTimelineSequencing
    ): JsonResponse {
        try {
            // Create dispatch route with driver
            $dispatchRoute = DispRoute::create([
                'vehicle' => $vehicle,
                'date' => $formattedDate,
                'driver_id' => $request->driver_id,
                'status' => 'New'
            ]);

            // Create initial dispatch for the order
            $dispatched = new Disp();
            $dispatched->oid = $givenOrder->id;
            $dispatched->van = $vehicle;
            $dispatched->date = $formattedDate;
            $dispatched->id_route = $dispatchRoute->id;
            $dispatched->num = 0;
            $dispatched->save();

            // Handle parent order assignment
            if ($request->Parent == true) {
                // Get all child orders
                $childOrders = Order::where('max_parent_order_id', $givenOrder->id)
                    ->orderBy('id', 'ASC')
                    ->get();

                $orderNum = 1; // Start child order numbering from 1

                foreach ($childOrders as $childOrder) {
                    // Create dispatch for child order
                    Disp::create([
                        'oid' => $childOrder->id,
                        'van' => $vehicle,
                        'date' => $formattedDate,
                        'id_route' => $dispatchRoute->id,
                        'num' => $orderNum++
                    ]);

                    // Update child order status
                    $childOrder->update([
                        'orderStatus' => 'Routed',
                        'locked' => 1,
                        'max_parent_order_id' => $givenOrder->id // Ensure parent relationship is maintained
                    ]);
                }
            }

            // Update parent order status
            $givenOrder->update([
                'orderStatus' => 'Routed',
                'max_parent_order_id' => null,
                'locked' => 1
            ]);

            // Find the driver by ID from the request
            $driver = Driver::find($request->driver_id);
            if ($driver) {
                // Ensure the vehicle_id is available in the request
                $driver->vehicle = $request->vehicle_id;
                $driver->save(); // Save the updated driver record
            }

            // Calculate distances
            if (!$orderTimelineSequencing) {
                $distanceHelper->calculateDistanceAndTime($givenOrder->id);
            }

            return response()->json(['message' => "order assigned successfully"], 200);
        } catch (\Exception $e) {
            \Log::error('Error in handleNewAssignment: ' . $e->getMessage());
            throw $e;
        }
    }

    private function handleParentOrderReassignment(Order $givenOrder, MapsHelper $distanceHelper, bool $orderTimelineSequencing): JsonResponse
    {
        // Get all child orders
        $childOrders = Order::where('max_parent_order_id', $givenOrder->id)
            ->whereHas('disp')
            ->get();

        if ($childOrders->isEmpty()) {
            return response()->json(['error' => 'No child orders found to reassign', 'success' => false], 200);
        }

        // Update child orders
        foreach ($childOrders as $childOrder) {
            $childOrder->update([
                'max_parent_order_id' => null,
                'orderStatus' => 'Routed'
            ]);

            // Recalculate distances for each child order
            if (!$orderTimelineSequencing) {
                $distanceHelper->calculateDistanceAndTime($childOrder->id);
            }
        }

        return response()->json(['message' => 'Parent order and children reassigned successfully'], 200);
    }

    private function handleChildOrderReassignment(Order $givenOrder, MapsHelper $distanceHelper, bool $orderTimelineSequencing): JsonResponse
    {
        // Get the parent order
        $parentOrder = Order::findOrFail($givenOrder->max_parent_order_id);

        // Remove the child order from parent
        $givenOrder->update([
            'max_parent_order_id' => null,
            'orderStatus' => 'Routed'
        ]);

        // Recalculate distances for both orders
        if (!$orderTimelineSequencing) {
            $distanceHelper->calculateDistanceAndTime($parentOrder->id);
            $distanceHelper->calculateDistanceAndTime($givenOrder->id);
        }

        return response()->json(['message' => 'Child order reassigned successfully'], 200);
    }

    /**
     * Swap Vehicle Runs
     *
     * Swaps all orders between two vehicles for the current date.
     *
     * @authenticated
     *
     * @bodyParam data object required The swap data.
     * @bodyParam data.from integer required The ID of the first vehicle. Example: 1
     * @bodyParam data.to integer required The ID of the second vehicle. Example: 2
     *
     * @response 200 {
     *     "from": [
     *         {
     *             "id": 1,
     *             "oid": 1,
     *             "van": 2,
     *             "date": "04-Jul-2024"
     *         }
     *     ],
     *     "to": [
     *         {
     *             "id": 2,
     *             "oid": 2,
     *             "van": 1,
     *             "date": "04-Jul-2024"
     *         }
     *     ]
     * }
     *
     * @response 404 {
     *     "message": "Vehicles does not exist."
     * }
     */
    public function swapVehicleRuns(Request $request): JsonResponse
    {
        $request->validate([
            'data.from' => 'required|exists:vehicles,id',
            'data.to' => 'required|exists:vehicles,id|different:data.from'
        ]);

        try {


            $formattedDate = Carbon::now()->format('d-M-Y');
            $vehicles = $this->getVehicles($request->data);

            if (!$this->validateVehicles($vehicles)) {
                return response()->json(['message' => 'Vehicles does not exist.', 'success' => false], 200);
            }

            $swappedOrders = $this->performSwap($vehicles, $formattedDate);


            return response()->json($swappedOrders, 200);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getVehicles(array $data): array
    {
        return [
            'from' => Vehicle::find($data['from']),
            'to' => Vehicle::find($data['to'])
        ];
    }

    private function validateVehicles(array $vehicles): bool
    {
        return !is_null($vehicles['from']) && !is_null($vehicles['to']);
    }

    private function performSwap(array $vehicles, string $formattedDate): array
    {
        $fromVehicleOrders = Disp::where([
            'van' => $vehicles['from']->id,
            'date' => $formattedDate
        ])->get();

        $toVehicleOrders = Disp::where([
            'van' => $vehicles['to']->id,
            'date' => $formattedDate
        ])->get();

        $swappedFrom = $this->updateVehicleOrders($fromVehicleOrders, $vehicles['to']->id);
        $swappedTo = $this->updateVehicleOrders($toVehicleOrders, $vehicles['from']->id);

        return [
            'from' => $swappedFrom,
            'to' => $swappedTo
        ];
    }

    private function updateVehicleOrders($orders, int $newVehicleId): array
    {
        $swappedOrders = [];
        foreach ($orders as $order) {
            $order->van = $newVehicleId;
            $order->save();
            $swappedOrders[] = $order;
        }
        return $swappedOrders;
    }

    /**
     * Allocate Order to Vehicle
     *
     * Allocates an order to a vehicle or merges it with an existing order.
     *
     * @authenticated
     *
     * @bodyParam data object required The allocation data.
     * @bodyParam data.selected_order integer required The ID of the order to allocate. Example: 1
     * @bodyParam data.vehicle_id integer required The ID of the vehicle. Example: 2
     * @bodyParam data.order_id integer|boolean The ID of the order to merge with, or false for new allocation. Example: 3
     *
     * @response 200 {
     *     "message": "Order allocated to vehicle successfully"
     * }
     *
     * @response 501 {
     *     "message": "Order already allocated"
     * }
     */
    public function allocateOrderToVehicle(Request $request): JsonResponse
    {
        $request->validate([
            'data.selected_order' => 'required|exists:orders,id',
            'data.vehicle_id' => 'required|exists:vehicles,id',
            'data.order_id' => 'required'
        ]);

        try {


            $data = $request->data;
            $currentDate = Carbon::now()->format('d-M-Y');

            if (!$data['order_id']) {
                $result = $this->handleNewAllocation($data, $currentDate);
            } else {
                $result = $this->handleOrderMerge($data, $currentDate);
            }


            return $result;
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function handleNewAllocation(array $data, string $currentDate): JsonResponse
    {
        $isAlreadyDispatched = Disp::where('oid', $data['selected_order'])->exists();

        if ($isAlreadyDispatched) {
            return response()->json(['message' => 'Order already allocated'], 501);
        }

        Disp::create([
            'van' => $data['vehicle_id'],
            'oid' => $data['selected_order'],
            'date' => $currentDate,
            'num' => 11,
        ]);

        $this->updateOrderStatus($data['selected_order'], 'Routed');

        return response()->json(['message' => 'Order allocated to vehicle successfully'], 200);
    }

    private function handleOrderMerge(array $data, string $currentDate): JsonResponse
    {
        $orderA = Order::find($data['order_id']);
        $orderB = Order::find($data['selected_order']);

        if (!$orderA) {
            return response()->json(['message' => 'Parent order not found', 'success' => false], 200);
        }

        $dispatch = Disp::where('oid', $orderA->id)->first();

        $this->updateOrderRelationships($orderA, $orderB);
        $this->createDispatchForMergedOrder($dispatch, $data['selected_order'], $currentDate);

        return response()->json(['message' => 'Order merged successfully'], 200);
    }

    private function updateOrderStatus(int $orderId, string $status): void
    {
        Order::where('id', $orderId)->update(['orderStatus' => $status]);
    }

    private function updateOrderRelationships(Order $orderA, Order $orderB): void
    {
        $orderA->max_parent_order_id = null;
        $orderA->save();

        $orderB->update([
            'orderStatus' => 'Routed',
            'max_parent_order_id' => $orderA->id
        ]);
    }

    private function createDispatchForMergedOrder(Disp $dispatch, int $orderId, string $date): void
    {
        Disp::create([
            'van' => $dispatch->van,
            'oid' => $orderId,
            'date' => $date,
        ]);
    }

    /**
     * Bulk Change Vehicle Settings
     *
     * Updates vehicle settings and driver availability for multiple vehicles in bulk.
     *
     * @authenticated
     *
     * @bodyParam data object required The bulk update data.
     * @bodyParam data.distribution_center_id integer required The ID of the distribution center. Example: 1
     * @bodyParam data.supported_vehicle_requirements array required Vehicle requirements. Example: ["requirement1", "requirement2"]
     * @bodyParam data.vehicle_type string required Type of vehicle. Example: "van"
     * @bodyParam data.max_speed integer required Maximum speed limit. Example: 60
     * @bodyParam data.driving_time_correction_factor float required Driving time correction. Example: 1.2
     * @bodyParam data.cost_per_mile float required Cost per mile. Example: 0.5
     * @bodyParam data.vehicle_activation_cost float required Vehicle activation cost. Example: 100
     * @bodyParam data.cost_per_order float required Cost per order. Example: 10
     * @bodyParam data.capacity_weight integer required Capacity weight. Example: 1000
     * @bodyParam data.driving_limit_start_time string required Driving limit start time. Example: "09:00"
     * @bodyParam data.run_duration_limit_start_time string required Run duration limit. Example: "08:00"
     * @bodyParam data.run_distance_limit integer required Run distance limit. Example: 100
     * @bodyParam data.cost_per_hour float required Cost per hour. Example: 20
     * @bodyParam data.date string required Date for settings. Example: "2024-07-04"
     * @bodyParam data.duty_time_limit_start_time string required Duty time limit. Example: "17:00"
     * @bodyParam data.break_automatically boolean required Automatic break shift. Example: true
     * @bodyParam data.availability_hours boolean required Has availability hours. Example: true
     * @bodyParam data.available_day boolean required Is available on day. Example: true
     * @bodyParam data.day string required Day of week. Example: "Monday"
     * @bodyParam data.start_day string required Start of day. Example: "09:00"
     * @bodyParam data.end_day string required End of day. Example: "17:00"
     * @bodyParam data.available_day_start_time string required Available start time. Example: "09:00"
     * @bodyParam data.available_day_end_time string required Available end time. Example: "17:00"
     * @bodyParam data.start_driving_exactly_from_the_shift_start boolean required Start driving from shift. Example: true
     *
     * @response 200 {
     *     "settings": [
     *         {
     *             "id": 1,
     *             "vehicle_id": 1,
     *             "supported_vehicle_requirements": ["requirement1"],
     *             "vehicle_type": "van",
     *             "max_speed": 60
     *         }
     *     ]
     * }
     */
    public function bulkChange(Request $request): JsonResponse
    {
        $request->validate([
            'data.distribution_center_id' => 'required|exists:distribution_centers,id',
            'data.supported_vehicle_requirements' => 'required|array',
            'data.vehicle_type' => 'required|string',
            'data.max_speed' => 'required|integer',
            'data.date' => 'required|date'
        ]);

        try {


            $data = $request->data;
            $formattedDate = $this->formatDate($data['date']);
            $vehicles = $this->getVehiclesByDistributionCenterId($data['distribution_center_id']);

            $settings = $this->createVehicleSettings($vehicles, $data, $formattedDate);

            if ($this->shouldCreateDriverDays($data)) {
                $this->createDriverDays($vehicles, $data, $formattedDate);
            }


            return response()->json($settings, 200);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('Y-m-d');
    }

    private function getVehiclesByDistributionCenterId(int $distributionCenterId): Collection
    {
        return Vehicle::where('distribution_centre_id', $distributionCenterId)->get();
    }

    private function createVehicleSettings(Collection $vehicles, array $data, string $formattedDate): array
    {
        $settings = [];
        $vehicleRequirements = json_encode($data['supported_vehicle_requirements']);

        foreach ($vehicles as $vehicle) {
            $settings[] = VehicleSetting::create([
                'vehicle_id' => $vehicle->id,
                'supported_vehicle_requirements' => $vehicleRequirements,
                'vehicle_type' => $data['vehicle_type'],
                'max_speed' => $data['max_speed'],
                'driving_time_correction_factor' => $data['driving_time_correction_factor'],
                'cost_per_mile' => $data['cost_per_mile'],
                'vehicle_activation_cost' => $data['vehicle_activation_cost'],
                'cost_per_order' => $data['cost_per_order'],
                'capacityWeight' => $data['capacity_weight'],
                'driving_limit' => $data['driving_limit_start_time'],
                'run_duration_limit' => $data['run_duration_limit_start_time'],
                'run_distance_limit' => $data['run_distance_limit'],
                'cost_per_hour' => $data['cost_per_hour'],
                'forDate' => $formattedDate,
                'time' => 11,
                'duty_time_limit' => $data['duty_time_limit_start_time'],
                'automatic_break_shift' => $data['break_automatically'],
            ]);
        }

        return $settings;
    }

    private function shouldCreateDriverDays(array $data): bool
    {
        return $data['availability_hours'] == 1 && $data['available_day'] == 1;
    }

    private function createDriverDays(Collection $vehicles, array $data, string $formattedDate): void
    {
        foreach ($vehicles as $vehicle) {
            DriverDay::create([
                'day' => $data['day'],
                'date' => $formattedDate,
                'driver_id' => $vehicle->driver_id,
                'start_day' => $data['start_day'],
                'end_day' => $data['end_day'],
                'start_time' => $data['available_day_start_time'],
                'end_time' => $data['available_day_end_time'],
                'start_driving_exactly_from_the_shift_start' => $data['start_driving_exactly_from_the_shift_start']
            ]);
        }
    }

    /**
     * Get Vehicle Details
     *
     * Get detailed information about vehicles including their drivers, warehouses, and requirements.
     *
     * @group Vehicle Management
     *
     * @queryParam supported_vehicle_requirements string optional Vehicle requirements filter. Example: "requirement1"
     * @queryParam distributionCentre integer optional Distribution centre ID to filter by. Example: 1
     *
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "driver_id": 1,
     *     "distribution_centre_id": 1,
     *     "driver_name": "John Doe",
     *     "name": "Vehicle 1",
     *     "volume": "100",
     *     "assigned_device": "device1",
     *     "archived": false,
     *     "max_speed": 60,
     *     "external_id": "EXT001",
     *     "driving_time_correction_factor": 1.2,
     *     "supported_vehicle": "type1",
     *     "reg": "REG001",
     *     "height": "2.5",
     *     "width": "2.0",
     *     "depth": "4.0",
     *     "weight": "1000",
     *     "vehicle_type": "van",
     *     "cost_per_mile": "1.5",
     *     "cost_per_order": "10",
     *     "vehicle_activation_cost": "100",
     *     "capacity_weight": "2000",
     *     "run_distance_limit": "500",
     *     "territories": "area1",
     *     "color": "#FF0000",
     *     "stand_down": false,
     *     "vin": "VIN001",
     *     "distribution_centre_name": "Warehouse 1",
     *     "supported_vehicle_requirements_id": 1,
     *     "supported_vehicle_requirements_name": "Requirement 1",
     *     "vehicle_type_name": "Van",
     *     "vehicle_type_id": 1,
     *     "tcp_source": "source1"
     *   }],
     *   "success": true
     * }
     */
    public function getVehiclesDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supported_vehicle_requirements' => 'nullable|exists:vehicle_requirements,id',
            'distributionCentre' => 'nullable|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input parameters',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $query = Vehicle::with('vehicleTerritory')
                ->leftJoin('drivers', 'drivers.id', '=', 'vehicles.driver_id')
                ->leftJoin('warehouses', 'warehouses.id', 'vehicles.distribution_centre_id')
                ->leftJoin('vehicle_requirements', 'vehicle_requirements.id', 'vehicles.supported_vehicle')
                ->leftJoin('vehicle_types', 'vehicle_types.id', 'vehicles.vehicle_type')
                ->when($request->filled('distributionCentre'), function ($query) use ($request) {
                    $query->where('distribution_centre_id', $request->distributionCentre);
                })
                ->when($request->filled('supported_vehicle_requirements'), function ($query) use ($request) {
                    $query->where('supported_vehicle', $request->supported_vehicle_requirements);
                })
                ->limit(500)
                ->select([
                    'vehicles.id',
                    'drivers.id as driver_id',
                    'vehicles.distribution_centre_id',
                    'drivers.name as driver_name',
                    'vehicles.name',
                    'vehicles.volume',
                    'vehicles.assigned_device',
                    'vehicles.archived',
                    'vehicles.max_speed',
                    'vehicles.external_id',
                    'vehicles.driving_time_correction_factor',
                    'vehicles.supported_vehicle',
                    'vehicles.reg',
                    'vehicles.height',
                    'vehicles.width',
                    'vehicles.depth',
                    'vehicles.weight',
                    'vehicles.vehicle_type',
                    'vehicles.cost_per_mile',
                    'vehicles.cost_per_order',
                    'vehicles.vehicle_activation_cost',
                    'vehicles.capacity_weight',
                    'vehicles.run_distance_limit',
                    'vehicles.territories',
                    'vehicles.driver_id',
                    'vehicles.color',
                    'vehicles.stand_down',
                    'vehicles.vin',
                    'vehicles.manufacturer_info',
                    'warehouses.name_of_warehouse as distribution_centre_name',
                    'vehicle_requirements.id as supported_vehicle_requirements_id',
                    'vehicle_requirements.name as supported_vehicle_requirements_name',
                    'vehicle_types.name as vehicle_type_name',
                    'vehicle_types.id as vehicle_type_id',
                    'tcp_source'
                ]);

            $vehicles = $query->get();

            return response()->json([
                'data' => $vehicles,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching vehicle details',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    //Create driver for vehicle
    private function createDriver($name, $warehouseId): Driver
    {
        // Generate a random password
        $randomPassword = Str::random(10); // Generates a random string of 10 characters

        // Hash the password
        $hashedPassword = Hash::make($randomPassword);

        // Generates a random 5-digit number
        $fiveDig =  mt_rand(10000, 99999);

        $driver = new Driver;
        $driver->fill([
            'name' => 'driver of ' . $name,
            'pwd' => $hashedPassword,
            'distribution_centre' => $warehouseId,
            'uName' => 'driver.' . $fiveDig,
        ]);
        $driver->save();

        return $driver;
    }

    /**
     * Save Vehicle
     *
     * Create or update a vehicle with its associated driver and requirements.
     *
     * @group Vehicle Management
     *
     * @bodyParam id integer required Vehicle ID for update. Example: 1
     * @bodyParam name string required Vehicle name. Example: "Van 1"
     * @bodyParam vehicle_type integer required Vehicle type ID. Example: 1
     * @bodyParam assigned_device string optional Device assigned to vehicle. Example: "device1"
     * @bodyParam tcp_source string optional TCP source. Example: "source1"
     * @bodyParam supported_vehicle integer required Vehicle requirement ID. Example: 1
     * @bodyParam max_speed integer required Maximum speed limit. Example: 60
     * @bodyParam driving_time_correction_factor float required Driving time correction. Example: 1.2
     * @bodyParam cost_per_mile decimal required Cost per mile. Example: 1.50
     * @bodyParam vehicle_activation_cost decimal required Activation cost. Example: 100.00
     * @bodyParam cost_per_order decimal required Cost per order. Example: 10.00
     * @bodyParam capacity_weight integer required Weight capacity. Example: 1000
     * @bodyParam run_distance_limit integer required Distance limit. Example: 500
     * @bodyParam distribution_centre_id integer required Warehouse ID. Example: 1
     * @bodyParam driver_id integer optional Driver ID. Example: 1
     * @bodyParam external_id string optional External reference. Example: "EXT001"
     * @bodyParam territories array required Territory IDs. Example: [1, 2]
     * @bodyParam comment string optional Vehicle comment. Example: "New vehicle"
     * @bodyParam manufacturer_info string optional Manufacturer details. Example: "Model XYZ"
     * @bodyParam vin string optional Vehicle VIN. Example: "1HGCM82633A123456"
     * @bodyParam stand_down boolean required Vehicle stand down status. Example: false
     * @bodyParam archived boolean required Vehicle archive status. Example: false
     * @bodyParam color string optional Vehicle color. Example: "#FF0000"
     * @bodyParam reg string optional Vehicle registration. Example: "ABC123"
     * @bodyParam capacity_volume decimal optional Volume capacity. Example: 100.00
     *
     * @response {
     *   "vehicle": {
     *     "id": 1,
     *     "name": "Van 1",
     *     "driver_id": 1
     *   },
     *   "success": true
     * }
     */
    public function saveVehicle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'vehicle_type' => 'nullable|exists:vehicle_types,id',
            'assigned_device' => 'nullable|string|max:255',
            'tcp_source' => 'nullable|string|max:255',
            'supported_vehicle' => 'nullable|exists:vehicle_requirements,id',
            'max_speed' => 'nullable|integer|min:1',
            'driving_time_correction_factor' => 'nullable|numeric|min:0',
            'cost_per_mile' => 'required|decimal:0,2|min:0',
            'vehicle_activation_cost' => 'nullable|decimal:0,2|min:0',
            'cost_per_order' => 'nullable|decimal:0,2|min:0',
            'capacity_weight' => 'required|integer|min:0',
            'run_distance_limit' => 'nullable|integer|min:0',
            'distribution_centre_id' => 'required|exists:warehouses,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'external_id' => 'required|string|max:255',
            'territories' => 'nullable|array',
            'territories.*' => 'integer|exists:territories,id',
            'comment' => 'nullable|string',
            'manufacturer_info' => 'nullable|string',
            'vin' => 'nullable|string|max:255',
            'stand_down' => 'nullable|boolean',
            'archived' => 'nullable|boolean',
            'color' => 'nullable|string|max:7',
            'reg' => 'nullable|string|max:255',
            'capacity_volume' => 'required|decimal:0,2|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            // Update driver assignments
            if ($request->filled('driver_id')) {
                Vehicle::where('driver_id', $request->driver_id)
                    ->where('id', '!=', $request->id)
                    ->update(['driver_id' => null]);
            }

            //create driver for the vehicle if create_driver_for_vehicle is true
            if ($request->create_driver_for_vehicle == true) {
                $driver = $this->createDriver($request->name, $request->distribution_centre_id);
                $request->driver_id  = $driver->id;
            }

            // Create or update vehicle
            $vehicle = Vehicle::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
                    'vehicle_type' => $request->vehicle_type,
                    'assigned_device' => $request->assigned_device,
                    'tcp_source' => $request->tcp_source,
                    'supported_vehicle' => $request->supported_vehicle,
                    'max_speed' => $request->max_speed,
                    'driving_time_correction_factor' => $request->driving_time_correction_factor,
                    'cost_per_mile' => $request->cost_per_mile,
                    'vehicle_activation_cost' => $request->vehicle_activation_cost,
                    'cost_per_order' => $request->cost_per_order,
                    'capacity_weight' => $request->capacity_weight,
                    'run_distance_limit' => $request->run_distance_limit,
                    'distribution_centre_id' => $request->distribution_centre_id,
                    'driver_id' => $request->driver_id,
                    'external_id' => $request->external_id,
                    'territories' => json_encode($request->territories),
                    'comment' => $request->comment,
                    'manufacturer_info' => $request->manufacturer_info,
                    'vin' => $request->vin,
                    'stand_down' => $request->stand_down,
                    'archived' => $request->archived,
                    'color' => $request->color,
                    'reg' => $request->reg,
                    'volume' => $request->capacity_volume
                ]
            );

            // Update territory relationships
            if ($request->filled('territories')) {
                foreach ($request->territories as $territory) {
                    DB::table('territory_vehicle')->insert([
                        'territory_id' => $territory,
                        'vehicle_id' => $vehicle->id
                    ]);
                }
            }

            return response()->json([
                'vehicle' => $vehicle,
                'success' => true
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to save vehicle',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Edit Vehicle
     *
     * Get vehicle details for editing by ID.
     *
     * @group Vehicle Management
     *
     * @bodyParam id integer required The ID of the vehicle. Example: 1
     *
     * @response {
     *   "vehicle": {
     *     "id": 1,
     *     "name": "Van 1",
     *     "vehicle_type": 1,
     *     "assigned_device": "device1",
     *     "tcp_source": "source1",
     *     "supported_vehicle": 1,
     *     "max_speed": 60,
     *     "driving_time_correction_factor": 1.2,
     *     "cost_per_mile": "1.50",
     *     "vehicle_activation_cost": "100.00",
     *     "cost_per_order": "10.00",
     *     "capacity_weight": 1000,
     *     "run_distance_limit": 500,
     *     "distribution_centre_id": 1,
     *     "driver_id": 1,
     *     "territories": [1, 2],
     *     "color": "#FF0000",
     *     "stand_down": false,
     *     "archived": false
     *   },
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "Vehicle does not exist.",
     *   "success": false
     * }
     */
    public function editVehicle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:vehicles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid vehicle ID',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $vehicle = Vehicle::findOrFail($request->id);

            return response()->json([
                'vehicle' => $vehicle,
                'success' => true
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Vehicle does not exist.',
                'success' => false,
                'success' => false
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching vehicle details',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update Vehicle
     *
     * Update an existing vehicle with its associated driver and territories.
     *
     * @deprecated Using saveVehicle() in VehicleController instead
     *
     * @group Vehicle Management
     *
     * @bodyParam id integer required The ID of the vehicle. Example: 1
     * @bodyParam name string required Vehicle name. Example: "Van 1"
     * ... (other parameters same as saveVehicle)
     *
     * @response {
     *   "vehicle": {
     *     "id": 1,
     *     "name": "Van 1",
     *     "driver_id": 1
     *   },
     *   "success": true
     * }
     */
    public function updateVehicle(Request $request): JsonResponse
    {
        // Method updateVehicle is deprecated. Use saveVehicle instead.

        // Forward the request to saveVehicle
        return $this->saveVehicle($request);
    }

    /**
     * Delete Vehicle
     *
     * Delete a vehicle by ID and remove associated relationships.
     *
     * @group Vehicle Management
     *
     * @bodyParam id integer required The ID of the vehicle to delete. Example: 1
     *
     * @response {
     *   "message": "Vehicle deleted.",
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "Vehicle does not exist.",
     *   "success": false
     * }
     *
     * @response 422 {
     *   "message": "Invalid vehicle ID",
     *   "errors": {
     *     "id": ["The id field is required."]
     *   },
     *   "success": false
     * }
     */
    public function deleteVehicle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:vehicles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid vehicle ID',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $vehicle = Vehicle::findOrFail($request->id);

            // Remove driver association if exists
            if ($vehicle->driver_id) {
                Driver::where('id', $vehicle->driver_id)
                    ->update(['vehicle' => null]);
            }

            // Delete the vehicle
            $vehicle->delete();



            return response()->json([
                'message' => 'Vehicle deleted.',
                'success' => true
            ], 200);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => 'Vehicle does not exist.',
                'success' => false,
                'success' => false
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to delete vehicle',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Filter Vehicles
     *
     * Get a filtered list of vehicles based on supported vehicle requirements and distribution centre.
     *
     * @group Vehicle Management
     *
     * @queryParam supported_vehicle integer optional Vehicle requirement ID to filter by. Example: 1
     * @queryParam distributionCentre integer optional Distribution centre ID to filter by. Example: 1
     *
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "driver_id": 1,
     *     "distribution_centre_id": 1,
     *     "driver_name": "John Doe",
     *     "name": "Vehicle 1",
     *     "assigned_device": "device1",
     *     "archived": false,
     *     "max_speed": 60,
     *     "external_id": "EXT001",
     *     "driving_time_correction_factor": 1.2,
     *     "supported_vehicle": 1
     *   }],
     *   "success": true
     * }
     */
    public function filterVehicle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supported_vehicle' => 'nullable|exists:vehicle_requirements,id',
            'distributionCentre' => 'nullable|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid filter parameters',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $query = Vehicle::leftJoin('drivers', 'drivers.id', '=', 'vehicles.driver_id')
                ->when($request->distributionCentre, function ($query) use ($request) {
                    $query->where('vehicles.distribution_centre_id', $request->distributionCentre);
                })
                ->when($request->supported_vehicle, function ($query) use ($request) {
                    $query->where('vehicles.supported_vehicle', $request->supported_vehicle);
                })
                ->select([
                    'vehicles.id',
                    'drivers.id as driver_id',
                    'vehicles.distribution_centre_id',
                    'drivers.name as driver_name',
                    'vehicles.name',
                    'vehicles.assigned_device',
                    'vehicles.archived',
                    'vehicles.max_speed',
                    'vehicles.external_id',
                    'vehicles.driving_time_correction_factor',
                    'vehicles.supported_vehicle'
                ]);

            $data = $query->get();

            return response()->json([
                'data' => $data,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to filter vehicles',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Search Vehicles
     *
     * Search vehicles by registration, name, external ID, or driver name.
     *
     * @group Vehicle Management
     *
     * @queryParam vehicleName string required Search term for vehicle or driver. Example: "Van1"
     *
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "driver_id": 1,
     *     "distribution_centre_id": 1,
     *     "driver_name": "John Doe",
     *     "name": "Van 1",
     *     "assigned_device": "device1",
     *     "archived": false,
     *     "max_speed": 60,
     *     "external_id": "EXT001",
     *     "driving_time_correction_factor": 1.2,
     *     "supported_vehicle": 1
     *   }],
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "The search term is required",
     *   "errors": {
     *     "vehicleName": ["The vehicle name field is required."]
     *   },
     *   "success": false
     * }
     */
    public function searchVehicle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicleName' => 'required|string|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The search term is required',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $data = Vehicle::leftJoin('drivers', 'drivers.id', '=', 'vehicles.driver_id')
                ->where(function ($query) use ($request) {
                    $query->where('vehicles.reg', 'like', "%{$request->vehicleName}%")
                        ->orWhere('vehicles.name', 'like', "%{$request->vehicleName}%")
                        ->orWhere('vehicles.external_id', 'like', "%{$request->vehicleName}%")
                        ->orWhere('drivers.name', 'like', "%{$request->vehicleName}%");
                })
                ->select([
                    'vehicles.id',
                    'drivers.id as driver_id',
                    'vehicles.distribution_centre_id',
                    'drivers.name as driver_name',
                    'vehicles.name',
                    'vehicles.assigned_device',
                    'vehicles.archived',
                    'vehicles.max_speed',
                    'vehicles.external_id',
                    'vehicles.driving_time_correction_factor',
                    'vehicles.supported_vehicle'
                ])
                ->get();

            return response()->json([
                'data' => $data,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to search vehicles',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Handle Driver Change
     *
     * Handle driver reassignment between vehicles, supporting both single assignment and driver swaps.
     *
     * @group Vehicle Management
     *
     * @bodyParam data.swap boolean required Whether this is a swap operation between two drivers. Example: false
     * @bodyParam data.vehicle_id integer required when swap is false The ID of the vehicle to assign driver to. Example: 1
     * @bodyParam data.driver_id integer required when swap is false The ID of the driver to assign. Example: 1
     * @bodyParam data.driver_A integer required when swap is true First driver ID for swap. Example: 1
     * @bodyParam data.driver_B integer required when swap is true Second driver ID for swap. Example: 2
     *
     * @response {
     *   "id": 1,
     *   "driver_id": null,
     *   "name": "Vehicle 1"
     * }
     *
     * @response scenario="swap" {
     *   "vehicleA": {
     *     "id": 1,
     *     "driver_id": 2,
     *     "name": "Vehicle A"
     *   },
     *   "vehicleB": {
     *     "id": 2,
     *     "driver_id": 1,
     *     "name": "Vehicle B"
     *   }
     * }
     */
    public function handleDriverChange(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data.swap' => 'required|boolean',
            'data.vehicle_id' => 'required_if:data.swap,false|exists:vehicles,id',
            'data.driver_id' => 'required_if:data.swap,false|exists:drivers,id',
            // 'data.driver_A' => 'required_if:data.swap,true|exists:drivers,id',
            'data.driver_B' => 'required_if:data.swap,true|exists:drivers,id|different:data.driver_A'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input parameters',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $data = $request->data;

            if (!$data['swap']) {
                // Single driver assignment
                $vehicleToAllocate = Vehicle::findOrFail($data['vehicle_id']);
                $vehicleToUnallocate = Vehicle::where('driver_id', $data['driver_id'])->first();

                if ($vehicleToUnallocate) {
                    $vehicleToUnallocate->driver_id = null;
                    $vehicleToUnallocate->save();
                }

                $vehicleToAllocate->driver_id = $data['driver_id'];
                $vehicleToAllocate->save();


                return response()->json($vehicleToUnallocate);
            } else {
                // Swap drivers between vehicles
                $vehicleA = Vehicle::where('driver_id', $data['driver_A'])->firstOrFail();
                $vehicleB = Vehicle::where('driver_id', $data['driver_B'])->firstOrFail();

                // Swap driver IDs
                $tempDriverId = $vehicleA->driver_id;
                $vehicleA->driver_id = $vehicleB->driver_id;
                $vehicleB->driver_id = $tempDriverId;

                $vehicleA->save();
                $vehicleB->save();

                /***
                 * Also update the driver routes ...
                 * when the driver is swap...
                */
                $this->updateRoutes($vehicleA , $vehicleB);

                return response()->json([
                    'vehicleA' => $vehicleA,
                    'vehicleB' => $vehicleB,
                ]);
            }
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => 'Vehicle or driver not found',
                'error' => $e->getMessage(),
                'success' => false,
                'success' => false
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to update driver assignments',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    // Update vehicles routes .
    private function updateRoutes($vehicleA, $vehicleB)
    {
        // Update vehicle A
      $vA =   DispRoute::where('vehicle', $vehicleA->id)
            ->whereIn('status', ['New', 'In-Progress'])
            ->update(['driver_id' => $vehicleA->driver_id]);

        // Update vehicle B
        $vB =   DispRoute::where('vehicle', $vehicleB->id)
            ->whereIn('status', ['New', 'In-Progress'])
            ->update(['driver_id' => $vehicleB->driver_id]);

    }


    /**
     * Deactivate Vehicle
     *
     * Toggle vehicle's archived status and handle related orders.
     *
     * @group Vehicle Management
     *
     * @bodyParam id integer required The ID of the vehicle to deactivate. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "archived": "1",
     *     "orders": [{
     *       "id": 1,
     *       "orderStatus": "Allocated",
     *       "max_parent_order_id": null,
     *       "locked": 1
     *     }]
     *   },
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "Vehicle not found",
     *   "success": false
     * }
     */
    public function deactivateVehicle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:vehicles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid vehicle ID',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $vehicle = Vehicle::findOrFail($request->id);

            $carbonDate = Carbon::now();
            $formattedDate = $carbonDate->format('d-M-Y');

            // Get dispatched orders for the vehicle
            $dispatchedOrders = Disp::where([
                'van' => $vehicle->id,
                'date' => $formattedDate
            ])->get();

            $updatedOrders = [];
            foreach ($dispatchedOrders as $dispatchedOrder) {
                $order = Order::find($dispatchedOrder->oid);
                if ($order) {
                    // Update child orders
                    Order::where('max_parent_order_id', $order->id)
                        ->update([
                            'max_parent_order_id' => null,
                            'orderStatus' => 'Allocated',
                            'locked' => 1
                        ]);

                    // Update parent order
                    $order->update([
                        'orderStatus' => 'Allocated',
                        'max_parent_order_id' => null,
                        'locked' => 1
                    ]);

                    $updatedOrders[] = $order;
                    $dispatchedOrder->delete();
                }
            }

            // if (count($updatedOrders) > 0) {
            $vehicle->archived = $vehicle->archived == 0 ? '1' : '0';
            $vehicle->save();
            // }



            if (count($updatedOrders) > 0) {
                return response()->json([
                    'data' => [
                        'id' => $vehicle->id,
                        'archived' => $vehicle->archived,
                        'orders' => $updatedOrders ?? []
                    ],
                    'success' => true
                ], 200);
            }

            return response()->json([
                'message' => 'Unable to deactivate vehicle',
                'success' => false
            ], 200);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => 'Vehicle not found',
                'success' => false,
                'success' => false
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to deactivate vehicle',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    private function handleParentOrderNewVehicle(
        Order $givenOrder,
        int $vehicle,
        Disp $dispatch,
        MapsHelper $distanceHelper,
        int $driverId,
        string $formattedDate,
        bool $orderTimelineSequencing
    ): JsonResponse {
        // Delete old dispatch
        $dispatch->delete();

        // Create dispatch route with driver
        $dispatchRoute = DispRoute::create([
            'vehicle' => $vehicle,
            'date' => $formattedDate,
            'driver_id' => $driverId,
            'status' => 'New'
        ]);

        // Create new dispatch for the order
        $dispatched = new Disp();
        $dispatched->oid = $givenOrder->id;
        $dispatched->van = $vehicle;
        $dispatched->date = Carbon::now()->format('d-M-Y');
        $dispatched->num = 0;
        $dispatched->id_route = $dispatchRoute->id;
        $dispatched->save();

        // Update order status
        $givenOrder->update([
            'orderStatus' => 'Routed',
            'max_parent_order_id' => null,
            'locked' => 1
        ]);

        // Recalculate distances
        if (!$orderTimelineSequencing) {
            $distanceHelper->calculateDistanceAndTime($givenOrder->id);
        }

        return response()->json(['message' => 'Order reassigned to new vehicle successfully'], 200);
    }

    private function handleChildOrderNewVehicle(
        Order $givenOrder,
        int $vehicle,
        Disp $dispatch,
        MapsHelper $distanceHelper,
        int $driverId,
        string $formattedDate,
        bool $orderTimelineSequencing
    ): JsonResponse {
        // Get parent order
        $parentOrder = Order::findOrFail($givenOrder->max_parent_order_id);

        // Delete old dispatch
        $dispatch->delete();

        // Create dispatch route with driver
        $dispatchRoute = DispRoute::create([
            'vehicle' => $vehicle,
            'date' => $formattedDate,
            'driver_id' => $driverId,
            'status' => 'New'
        ]);

        // Create new dispatch for the order
        $dispatched = new Disp();
        $dispatched->oid = $givenOrder->id;
        $dispatched->van = $vehicle;
        $dispatched->date = Carbon::now()->format('d-M-Y');
        $dispatched->num = 0;
        $dispatched->id_route = $dispatchRoute->id;
        $dispatched->save();

        // Update order status
        $givenOrder->update([
            'max_parent_order_id' => null,
            'orderStatus' => 'Routed'
        ]);

        // Recalculate distances for both orders
        if (!$orderTimelineSequencing) {
            $distanceHelper->calculateDistanceAndTime($parentOrder->id);
            $distanceHelper->calculateDistanceAndTime($givenOrder->id);
        }

        return response()->json(['message' => 'Child order reassigned to new vehicle successfully'], 200);
    }

    private function handleParentOrderVehicleChange(Order $givenOrder, $vehicle, Disp $dispatch, int $driverId, string $formattedDate, bool $orderTimelineSequencing): ?JsonResponse
    {

        try {
            // Step 1: Get child order IDs
            $childOrderIds = Order::where('max_parent_order_id', $givenOrder->id)->pluck('id');

            // Step 2: Get dispatched orders where `oid` matches `childOrderIds`
            $dispatchedOrders = Disp::whereIn('oid', $childOrderIds)->get();

            // Step 3: Sort dispatched orders by `num` in ascending order
            $orderOrdersInAscendingOrder = $dispatchedOrders->sortBy('num');

            // Step 4: Get the first order
            $getFirstOrder = $orderOrdersInAscendingOrder->first();

            // Exclude $getFirstOrder from $orderOrdersInAscendingOrder
            $remainingOrders = $orderOrdersInAscendingOrder->reject(function ($order) use ($getFirstOrder) {
                return $order->id_disp === $getFirstOrder->id_disp;
            });

            $remainingOrderOids = $remainingOrders->pluck('oid');
            $remainingOrderOidsArray = $remainingOrderOids->toArray();

            // Make first order is parent .
            Disp::where('oid', $getFirstOrder->oid)
                ->update(['num' => 0]);

            Order::where('id', $getFirstOrder->oid)
                ->update(['max_parent_order_id' => null]);

            //Update max_parent_order_id of all child orders except the first order.
            Order::whereIn('id', $remainingOrderOidsArray)
                ->update(['max_parent_order_id' => $getFirstOrder->oid]);

            // Update parent order's locked status
            $givenOrder->update(['locked' => 1]);

            return response()->json(['message' => 'Order assigned successfully'], 200);
        } catch (\Exception $e) {

            Log::error('Error in handleParentOrderVehicleChange: ' . $e->getMessage(), [
                'order_id' => $givenOrder->id,
                'vehicle' => $vehicle,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to change vehicle assignment',
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
    private function reassiginEntireToOtherVehicle(Order $givenOrder, $vehicle, Disp $dispatch, int $driverId, string $formattedDate, bool $orderTimelineSequencing): ?JsonResponse
    {
        try {
            // Step 1: Get child order IDs
            $childOrderIds = Order::where('max_parent_order_id', $givenOrder->id)->pluck('id')->toArray();

            // Step 2: Merge the parent order ID with the child order IDs array
            $orderIds = array_merge($childOrderIds, [$givenOrder->id]);

            // Step 3: Update the in routes table with driverId and vehicleId
            DispRoute::where('id', $dispatch->id_route)->update([
                'vehicle' => $vehicle,
                'date' => $formattedDate,
                'driver_id' => $driverId,
                'status' => 'New',
            ]);

            // Step 4: Update the vanId in disp table
            Disp::whereIn('oid', $orderIds)
                ->update(['van' => $vehicle]);



            //Step 6: Update the new run locked status  first check wether its locked (0) or unlocked (1)
            if ($givenOrder->locked !== 1) {

                Order::whereIn('id', $orderIds)
                    ->update(['locked' => 1]);
            }

            // Setp 5:  Send Notification  the previous driver , route its unassign
            if ($givenOrder->locked == 2) {
                $value = 2;
                FcmFunctionHelper::sendDetailsToDriverRouteUnassigned($givenOrder->id, $value);
            }

            return response()->json(['message' => 'Run assign to other vehicle successfully'], 200);
        } catch (\Exception $e) {

            Log::error('Error in reassiginEntireToOtherVehicle: ' . $e->getMessage(), [
                'order_id' => $givenOrder->id,
                'vehicle' => $vehicle,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to assign run to other vehicle',
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
