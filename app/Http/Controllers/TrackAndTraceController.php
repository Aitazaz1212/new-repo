<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\{Vehicle, Order};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Validator};
use Illuminate\Http\JsonResponse;

/**
 * @group Track and Trace Operations
 *
 * APIs for tracking vehicle operations and related order information
 */
class TrackAndTraceController extends Controller
{
    /**
     * Get Operation Environment
     *
     * Retrieves detailed vehicle operation information for a specific date including orders, drivers, warehouse details,
     * and all related timing and status information.
     *
     * @param Request $request
     *
     * @bodyParam date string required The date to fetch operations for (format: d/m/Y). Example: 25/03/2024
     * @bodyParam vehicleIds array optional Array of vehicle IDs to filter by. Example: [1, 2, 3]
     * @bodyParam warehouseIds array optional Array of warehouse IDs to filter by. Example: [1, 2]
     *
     * @response 200 {
     *   "data": [{
     *     "id": 1,
     *     "reg": "ABC123",
     *     "height": "2.5",
     *     "width": "2.0",
     *     "depth": "4.0",
     *     "weight": "3500.00",
     *     "description": "Delivery Van",
     *     "user_id": 1,
     *     "name": "Van 1",
     *     "vehicle_type": "delivery",
     *     "assigned_device": "device123",
     *     "tcp_source": "source1",
     *     "supported_vehicle": "yes",
     *     "max_speed": "70",
     *     "driving_time_correction_factor": "1.1",
     *     "cost_per_mile": "0.45",
     *     "vehicle_activation_cost": "10.00",
     *     "cost_per_order": "5.00",
     *     "capacity_weight": "3500",
     *     "run_distance_limit": "200",
     *     "distribution_centre_id": 1,
     *     "driver": {
     *       "id": 1,
     *       "name": "John Doe"
     *     },
     *     "warehouse": {
     *       "id": 1,
     *       "name": "Main Warehouse"
     *     },
     *     "disp": [{
     *       "id_disp": 1,
     *       "oid": 123,
     *       "date": "25-03-2024",
     *       "route": "ROUTE1",
     *       "num": 1,
     *       "van": 1,
     *       "id_route": 1,
     *       "height": 2,
     *       "order": {
     *         "id": 1,
     *         "ref_no": "ORD123",
     *         "warehouse_id": 1,
     *         "priority": "high",
     *         "vehicle_requirement_id": "1",
     *         "description": "Urgent delivery",
     *         "orderType": "delivery",
     *         "speed_zone_id": "1",
     *         "territory_id": "1",
     *         "weight": "100",
     *         "volume": "2.5",
     *         "allowNotifications": true,
     *         "stop_sequence": "1",
     *         "collection": [],
     *         "deliverBy_datetime": "2024-03-25 14:00:00",
     *         "operation_duration": "30",
     *         "orderStatus": "in_progress",
     *         "customer": {
     *           "id": 1,
     *           "businessName": "ACME Corp"
     *         },
     *         "orderLogs": [],
     *         "orderItems": [],
     *         "orderOperationTimeWindow": []
     *       }
     *     }],
     *     "disp_count": 1
     *   }]
     * }
     *
     * @response 422 {
     *   "status": false,
     *   "message": "Validation error",
     *   "error": "The date field is required."
     * }
     *
     * @response 500 {
     *   "error": "An error occurred while processing your request.",
     *   "message": "Error details"
     * }
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     * @return JsonResponse
     */
    public function operationEnvironment(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequest($request);
            if ($validated instanceof JsonResponse) {
                return $validated;
            }

            $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');


            try {
                $vehicles = $this->getVehiclesWithOperations(
                    $date,
                    $request->vehicleIds,
                    $request->warehouseIds
                );


                return response()->json(['data' => $vehicles]);
            } catch (\Exception $e) {

                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Operation Environment Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'An error occurred while processing your request.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate the incoming request
     *
     * @param Request $request
     * @return JsonResponse|true
     */
    private function validateRequest(Request $request): JsonResponse|bool
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:d/m/Y',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'error' => $validator->errors()->first(),
            ], 422);
        }

        return true;
    }

    /**
     * Get vehicles with their operations for the given date
     *
     * @param string $date
     * @param array|null $vehicleIds
     * @param array|null $warehouseIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getVehiclesWithOperations(
        string $date,
        ?array $vehicleIds = null,
        ?array $warehouseIds = null
    ) {
        $query = $this->buildVehicleQuery($date);

        $this->applyWarehouseFilter($query, $warehouseIds);
        $this->applyVehicleFilter($query, $vehicleIds);

        return $query->withCount(['disp' => function ($query) use ($date) {
            $this->addDispDateFilter($query, $date);
        }])->get();
    }

    /**
     * Build the base vehicle query with relationships
     *
     * @param string $date The date to filter by (Y-m-d format)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildVehicleQuery(string $date)
    {
        return Vehicle::whereHas('dispforpd', function ($query) use ($date) {
            $query->whereHas('order', function ($subquery) use ($date) {
                $subquery->whereDate('deliverBy_datetime', $date)
                    ->whereNull('max_parent_order_id');
            });
        })->with([
            'driver',
            'warehousefullobject',
            'dispforpd' => function ($query) use ($date) {
                $this->addDispWithRelations($query, $date);
            }
        ]);
    }

    /**
     * Add disp relationships and filters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return void
     */
    private function addDispWithRelations($query, string $date): void
    {
        $this->addDispDateFilter($query, $date);

        $query->with(['order' => function ($subquery) use ($date) {
            $subquery->with([
                // 'customer:id,businessName',
                'customer',
                'orderItems',
                'orderOperationTimeWindow',
                'orderLogs',
                'orderAttachments',
                'childOrders',
            ])
                ->whereNull('max_parent_order_id')
                ->whereDate('deliverBy_datetime', $date)
                ->select(Order::getOrderSelectFields());
        }]);
    }

    /**
     * Add common order relations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    private function addOrderRelations($query): void
    {
        $query->with([
            'customer',
            'orderItems',
            'orderOperationTimeWindow',
            'orderLogs',
            'orderAttachments',
        ]);
    }

    /**
     * Add date filter to disp query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return void
     */
    private function addDispDateFilter($query, string $date): void
    {
        $query->whereHas('order', function ($subquery) use ($date) {
            $subquery->whereDate('deliverBy_datetime', $date)
                ->whereNull('max_parent_order_id');
        });
    }

    /**
     * Apply warehouse filter if provided
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $warehouseIds
     * @return void
     */
    private function applyWarehouseFilter($query, ?array $warehouseIds): void
    {
        if (!empty($warehouseIds)) {
            $query->whereHas('warehouse', function ($query) use ($warehouseIds) {
                $query->whereIn('warehouses.id', $warehouseIds);
            });
        }
    }

    /**
     * Apply vehicle filter if provided
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $vehicleIds
     * @return void
     */
    private function applyVehicleFilter($query, ?array $vehicleIds): void
    {
        if (!empty($vehicleIds)) {
            $query->whereIn('vehicles.id', $vehicleIds);
        }
    }


    /**
     * Track Vehicle Operations
     *
     * Retrieves detailed tracking information for vehicles including their orders, routes, and operational status.
     *
     * @param Request $request
     *
     * @bodyParam date string required The date to track vehicles (format: d/m/Y). Example: 25/03/2024
     * @bodyParam vehicleIds array optional Array of vehicle IDs to filter. Example: [1, 2, 3]
     * @bodyParam warehouseIds array optional Array of warehouse IDs to filter. Example: [1, 2]
     * @bodyParam orderStatus array optional Array of order statuses to filter. Example: ["pending", "completed"]
     *
     * @response 200 {
     *   "status": true,
     *   "data": [{
     *     "id": 1,
     *     "reg": "ABC123",
     *     "name": "Vehicle 1",
     *     "driver": {
     *       "id": 1,
     *       "name": "John Doe"
     *     },
     *     "warehouse": {
     *       "id": 1,
     *       "name": "Main Warehouse"
     *     },
     *     "orders": [{
     *       "id": 1,
     *       "ref_no": "ORD001",
     *       "orderStatus": "pending",
     *       "customer": {
     *         "id": 1,
     *         "name": "Customer Name"
     *       }
     *     }]
     *   }]
     * }
     *
     * @response 422 {
     *   "status": false,
     *   "message": "Validation error",
     *   "error": "The date field is required."
     * }
     */
    public function vehicleTrack(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:d/m/Y',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
            $displayDate = Carbon::createFromFormat('d/m/Y', $request->date)->format('d-M-Y');

            $refineStatusArray = array();
            if (!empty($request->orderStatus)) {
                $statusMapping = [
                    'Fully delivered' => [
                        'Delivered',
                        'Departure from delivery location',
                        'Completed'
                    ],
                    'Partially delivered' => [
                        'Routed',
                        'Dispatched',
                        'Attempted Delivery',
                        'ARRIVED',
                        'Departure from delivery location',
                        'Completed',
                        'Cancelled'
                    ],
                    'Not delivered' => [
                        'Not Delivered',
                        'Routed',
                        'Cancelled',
                        'Suspended',
                        'ARRIVED',
                        'Delivery Started'
                    ]
                ];

                $refineStatusArray = [];

                foreach ($request->orderStatus as $singleStatus) {
                    if (isset($statusMapping[$singleStatus])) {
                        $refineStatusArray = array_merge($refineStatusArray, $statusMapping[$singleStatus]);
                    } else {
                        $refineStatusArray[] = $singleStatus;
                    }
                }
            }

            $query = Vehicle::query()
                ->with(['driver', 'warehousefullobject'])
                ->whereHas('dispforpd', function ($query) use ($date, $displayDate, $request, $refineStatusArray) {
                    $this->applyDispatchFilters($query, $date, $displayDate, $refineStatusArray);
                })
                ->with(['dispforpd' => function ($query) use ($date, $displayDate, $request, $refineStatusArray) {
                    $this->applyDispatchWithRelations($query, $date, $displayDate, $refineStatusArray);
                }]);

            $this->applyFilters($query, $request->vehicleIds, $request->warehouseIds);

            $vehicles = $query->get();

            return response()->json([
                'status' => true,
                'data' => $vehicles
            ]);
        } catch (\Exception $e) {
            Log::error('Vehicle tracking error: ' . $e->getMessage(), [
                'date' => $request->date,
                'vehicleIds' => $request->vehicleIds,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while tracking vehicles',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Apply dispatch filters to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @param string $displayDate
     * @param array|null $orderStatus
     * @return void
     */
    private function applyDispatchFilters($query, string $date, string $displayDate, ?array $orderStatus): void
    {
        $query->whereHas('order', function ($subquery) use ($date, $orderStatus) {
            $subquery->whereDate('deliverBy_datetime', $date)
                ->whereNull('max_parent_order_id');

            if (!empty($orderStatus)) {
                $subquery->whereIn('orderStatus', $orderStatus);
            }
        });
    }

    /**
     * Apply dispatch relations to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @param string $displayDate
     * @param array|null $orderStatus
     * @return void
     */
    private function applyDispatchWithRelations($query, string $date, string $displayDate, ?array $orderStatus): void
    {
        // $query->where('date', $displayDate)
        $query->whereHas('order', function ($subquery) use ($date, $orderStatus) {
            $subquery->whereDate('deliverBy_datetime', $date)
                ->whereNull('max_parent_order_id');

            if (!empty($orderStatus)) {
                $subquery->whereIn('orderStatus', $orderStatus);
            }
        })
            ->with(['order' => function ($subquery) use ($orderStatus) {
                $subquery->with(['customer', 'orderOperationTimeWindow', 'orderLogs', 'orderAttachments', 'orderItems'])
                    ->with(['childOrders' => function ($q) use ($orderStatus) {
                        $q->with(['customer', 'orderOperationTimeWindow', 'orderLogs', 'orderAttachments', 'orderItems']);
                        if (!empty($orderStatus)) {
                            $q->whereIn('orderStatus', $orderStatus);
                        }
                        $q->join('disp', 'orders.id', 'disp.oid');
                        $q->orderBy('disp.num', 'asc');
                        $q->select($this->getOrderSelectFieldsForTrack());
                    }])
                    ->select($this->getOrderSelectFieldsForTrack());
            }]);
    }

    /**
     * Apply general filters to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $vehicleIds
     * @param array|null $warehouseIds
     * @return void
     */
    private function applyFilters($query, ?array $vehicleIds, ?array $warehouseIds): void
    {
        if (!empty($vehicleIds)) {
            $query->whereIn('vehicles.id', $vehicleIds);
        }

        if (!empty($warehouseIds)) {
            $query->whereIn('vehicles.distribution_centre_id', $warehouseIds);
        }
    }

    /**
     * Get order select fields for tracking
     *
     * @return array<string>
     */
    private function getOrderSelectFieldsForTrack(): array
    {
        return [
            "id",
            "ref_no",
            "warehouse_id",
            "priority",
            "vehicle_requirement_id",
            "description",
            "orderType",
            "speed_zone_id",
            "territory_id",
            "lat",
            "lng",
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
            "parent_order_id",
            "startTime",
            "loadingTime",
            "waitingTime",
            "workStartTime",
            "returnStartTime",
            "returnWareHouseTime",
            "arriveTime",
            "operationEndTime",
            "runDuration",
            "runTotalDrivingTime",
            "timeViolation",
            "weightExceeded",
            "howMuchWeightOver",
            "volumeExceeded",
            "howMuchVolumeOver",
            "takenloadingTime",
            "takenstartTime",
            "takenwaitingTime",
            "takenworkStartTime",
            "takenreturnStartTime",
            "takenreturnWareHouseTime",
            "takenarriveTime   as ArrivalActual",
            "takenarriveTime  as ArrivalReported",
            "takenoperationEndTime as ActualJobDuration",
            "completion_status"
        ];
    }

    /**
     * Search Orders in Track and Trace
     *
     * Search for orders within a date range with optional filters
     *
     * @bodyParam fromDate string required Start date in d/m/Y format. Example: 25/03/2024
     * @bodyParam toDate string required End date in d/m/Y format. Example: 26/03/2024
     * @bodyParam warehouseId integer optional Warehouse ID to filter by. Example: 1
     * @bodyParam searchItem string optional Search term for order reference or customer details. Example: "ORD123"
     * @bodyParam exactSearch string optional Whether to perform exact matching (true) or partial matching (false). Example: "true"
     *
     * @response 200 {
     *   "data": [{
     *     "id": 1,
     *     "reg": "ABC123",
     *     "name": "Vehicle 1",
     *     "driver": {
     *       "name": "John Doe"
     *     },
     *     "warehouse": {
     *       "name": "Main Warehouse"
     *     },
     *     "dispatches": [{
     *       "date": "2024-03-25",
     *       "order": {
     *         "ref_no": "ORD123",
     *         "customer": {
     *           "name": "Customer Name"
     *         }
     *       }
     *     }]
     *   }]
     * }
     *
     * @response 400 {
     *   "message": "The date can't be empty"
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchOrderIntrackAndTrace(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'fromDate' => 'required|date_format:d/m/Y',
                'toDate' => 'required|date_format:d/m/Y',
                'warehouseId' => 'nullable',
                'searchItem' => 'nullable',
                'exactSearch' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 400);
            }

            // Parse dates
            $startDate = Carbon::createFromFormat('d/m/Y', $request->fromDate)->format('Y-m-d');
            $endDate = Carbon::createFromFormat('d/m/Y', $request->toDate)->format('Y-m-d');

            // Build query
            $query = $this->buildVehicleSearchQuery(
                $startDate,
                $endDate,
                $request->searchItem,
                $request->exactSearch === 'true'
            );

            // Apply warehouse filter if provided
            if ($request->warehouseId) {
                $query->whereIn('vehicles.distribution_centre_id', $request->warehouseId);
            }

            // Execute query with relationships
            $results = $query->with([
                'driver',
                'warehousefullobject',
                'dispforpd' => function ($query) use ($startDate, $endDate) {
                    $this->applyDispatchDateFilter($query, $startDate, $endDate);
                }
            ])->get();

            return response()->json(['data' => $results]);
        } catch (\Exception $e) {
            Log::error('Search order in track and trace failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'message' => 'An error occurred while searching orders',
            ], 500);
        }
    }

    /**
     * Build the base vehicle search query
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $search
     * @param bool $exactSearch
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildVehicleSearchQuery(
        string $startDate,
        string $endDate,
        ?string $search,
        bool $exactSearch
    ): \Illuminate\Database\Eloquent\Builder {
        return Vehicle::whereHas('disp', function ($query) use ($startDate, $endDate, $search, $exactSearch) {
            $query->whereHas('order', function ($subquery) use ($startDate, $endDate, $search, $exactSearch) {
                $subquery->whereBetween(DB::raw('DATE(deliverBy_datetime)'), [$startDate, $endDate])
                    ->whereNull('max_parent_order_id');

                if ($search) {
                    $this->applySearchFilters($subquery, $search, $exactSearch);
                }
            });
        });
    }

    /**
     * Apply search filters to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @param bool $exactSearch
     * @return void
     */
    private function applySearchFilters($query, string $search, bool $exactSearch): void
    {
        if ($exactSearch) {
            $query->where('ref_no', $search)
                ->orWhereHas('customer', function ($cQuery) use ($search) {
                    $this->addCustomerSearch($cQuery, $search, true);
                });
        } else {
            $query->where('ref_no', 'like', "%$search%")
                ->orWhereHas('customer', function ($cQuery) use ($search) {
                    $this->addCustomerSearch($cQuery, $search, false);
                });
        }
    }

    /**
     * Apply date filter to dispatch query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return void
     */
    private function applyDispatchDateFilter($query, string $startDate, string $endDate): void
    {
        $query->whereHas('orderforpd', function ($subquery) use ($startDate, $endDate) {
            $subquery->whereBetween(DB::raw('DATE(deliverBy_datetime)'), [$startDate, $endDate])
                ->whereNull('max_parent_order_id');
            $subquery->with(['customer', 'orderItems', 'orderOperationTimeWindow']);
        });
        $query->with(['orderforpd' => function ($subquery) {
            $subquery->with([
                // 'customer:id,businessName',
                'customer',
                'orderItems',
                'orderOperationTimeWindow',
                'orderLogs',
                'orderAttachments',
                'childOrders' => function ($q) {
                    $this->addOrderRelations($q);
                    $q->select($this->getOrderSelectFieldsForTrack());
                }
            ])->select($this->getOrderSelectFieldsForTrack());
        }]);
    }

    /**
     * Add customer search conditions
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @param bool $exact
     * @return void
     */
    private function addCustomerSearch($query, string $search, bool $exact): void
    {
        if ($exact) {
            $query->where('name', $search);
        } else {
            $query->where('name', 'like', "%$search%");
        }
    }
}
