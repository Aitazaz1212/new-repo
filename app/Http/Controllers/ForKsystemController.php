<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\{
    Order,
    Vehicle,
    Driver,
    DispRoute,
};
use Carbon\Carbon;

class ForKsystemController extends Controller
{

    /**
     * Get filtered routes with pagination and formatting.
     */
    public function getRoutes(Request $request)
    {

        try {
            // Validate incoming request parameters
            $validated = $request->validate([
                'assignedDrivers' => 'nullable|string',
                'assignedVehicles' => 'nullable|string',
                'consignmentReference' => 'nullable|string',
                'dcReference' => 'nullable|string',
                'expand' => 'nullable',
                'limit' => 'nullable|integer|min:1|max:200',
                'offset' => 'nullable|integer|min:0',
                'plannedArrivalFrom' => 'nullable',
                'plannedArrivalTo' => 'nullable',
                'shiftDateFrom' => 'nullable',
                'shiftDateTo' => 'nullable',
                'statuses' => 'nullable|string',
                'territoryReference' => 'nullable|string',
                'timeWindowFrom' => 'nullable',
                'timeWindowTo' => 'nullable',
            ]);

            // Fetch orders based on filters
            $queryResult = $this->getOrders($validated);

            // Format the result for response
            $response = $this->formatResult($queryResult);

            // merge it only on staging
            return ($response);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }


    /**
     * Fetch orders based on validated filters.
     */
    private function getOrders($validated)
    {
        $result = Order::withoutGlobalScope('withExecutionRelations') // Disable specific global scope
            ->without(['communicationLogs', 'orderLogs', 'orderAttachments', 'OrderRejectionDetails'])

        // Exclude those orders whose  Ref_no  or  deliverBy_datetime  is null
        ->whereNotNull('ref_no')
        ->whereNotNull('deliverBy_datetime')

        //get the orders by date in Ascending order.
       ->orderBy('deliverBy_datetime'  ,'asc');

        // Filter by reference no
        if (!empty($validated['consignmentReference'])) {
            $result->where('ref_no', $validated['consignmentReference']);
        }

        // Filter by statuses (comma-separated string to array)
        if (!empty($validated['statuses'])) {
            // Convert string to array
            // $statusArray = explode(', ', $validated['statuses']);
            if ($validated['statuses'] == 'FAILED') {
                $status = 'Cancelled';
            } else {
                $status = $validated['statuses'];
            }
            $result->where('orderStatus', $status);
        }

        // Filter by shiftDateFrom  date range
        if (!empty($validated['shiftDateFrom'])) {
            $date = Carbon::parse($validated['shiftDateFrom'])->startOfDay();
            $result->whereDate('deliverBy_datetime', '>=', $date); // Compare only the date
        }

        if (!empty($validated['shiftDateTo'])) {
            $date = Carbon::parse($validated['shiftDateTo'])->startOfDay();
            $result->whereDate('deliverBy_datetime', '<=', $date); // Compare only the date
        }

        if (!empty($validated['timeWindowFrom'])) {
            $date = Carbon::parse($validated['timeWindowFrom'])->format('Y-m-d 00:00:00');
            $result->whereDate('deliverBy_datetime', '>=', $date); // Compare only the date
        }

        // Filter by assigned drivers
        if (!empty($validated['assignedDrivers'])) {
            $result->whereHas('disp', function ($query) use ($validated) {
                $query->whereHas('vehicleforpd', function ($query) use ($validated) {
                    $query->whereHas('driver', function ($query) use ($validated) {
                        $query->where('name', $validated['assignedDrivers']);
                    });
                });
            });
        }

        // Filter by assigned vehicles
        if (!empty($validated['assignedVehicles'])) {
            $result->whereHas('disp.vehicleforpd', function ($query) use ($validated) {
                $query->where('name', $validated['assignedVehicles']);
            });
        }

        // Filter by distribution center reference
        if (!empty($validated['dcReference'])) {
            $result->whereHas('disp.vehicleforpd.warehouse', function ($query) use ($validated) {
                $query->where('ref_number', $validated['dcReference']);
            });
        }

        // Filter by both DC reference and territory reference
        if (!empty($validated['dcReference']) && !empty($validated['territoryReference'])) {
            $result->whereHas('disp.vehicleforpd.warehouse.territories', function ($query) use ($validated) {
                $query->where('ref_no', $validated['territoryReference']);
            });
        }

        // Pagination logic
        $perPage = $validated['limit'] ?? 200;
        $page = 1;

        if (isset($validated['offset'])) {
            $page = (int)($validated['offset'] / $perPage) + 1;
        }

        // Return paginated results
        return $result->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Format query results for API response.
     */
    private function formatResult($queryResult)
    {
        // Convert items to a collection and map over them
        $formattedData = collect($queryResult->items())->map(function ($order) {
            return [
                "id" => $order->id,
                "referenceNumber" => $order->ref_no,
                "consignmentReference" => '',
                "distributionCentreReference" => $order->disp->vehicleforpd->warehouse->ref_number ?? null,
                "distributionCentreName" => $order->disp->vehicleforpd->warehouse->name_of_warehouse ?? null,
                "task" => $order->orderType,
                "priority" => $order->priority ?? "NORMAL",
                "clientName" => $order->customer->businessName,
                "contactPerson" => $order->customer->mob,
                "customerLocation" => [
                    "referenceNumber" => null,
                    "name" => $order->customer->businessName ?? null,
                    "address" => $order->customer->street ?? null,
                    "latitude" => $order->lat ?? 0,
                    "longitude" => $order->lng ?? 0,
                    "w3wAddress" => $order->customer->web ?? null,
                ],
                "capacity1" => $order->weight ?? 0,
                "capacity2" => $order->volume ?? 0,
                "operationDuration" => $order->orderOperationTimeWindow ?? 0,
                "customFields" => ['pdServicelevel' => $order->orderType, 'webRef' => $order->web ?? null],
                "deliverByDate" => $order->deliverBy_datetime ?? null,
                "status" =>  $this->orderStatus($order->orderStatus, $order->locked, $order->disp->id_route  ?? null),
                "statusLastUpdated" => $order->updated_at->toIso8601String(),
                "widgetTrackingDetails" => [
                    "trackingId" => $order->id ?? null,
                    "externalTrackingUri" => "https://piloting.premierdeliveries.co.uk/track/" . $order->id ?? null,
                ],
            ];
        });

        // Generate links for pagination
        $prevOffset = $queryResult->previousPageUrl();
        $nextOffset = $queryResult->nextPageUrl();

        return [
            "data" => $formattedData,
            "offset" => $queryResult->firstItem() - 1 ?? 0,
            "_links" => [
                "prev" => $prevOffset,
                "next" => $nextOffset,
            ],
        ];
    }

    // Return the order status
    private function orderStatus($orderStatus, $orderLocked, $routeId)
    {
        if ($orderStatus == 'Routed') {
            if ($orderLocked == 0) {
                return 'LOCKED';
            } elseif ($orderLocked == 1) {
                return 'Routed';
            } else {
                // Fetch route status
                $route = DispRoute::find($routeId);

                if ($route  && $route->status == 'In-Progress') {
                    return 'ACCEPTED';
                } else {
                    return 'Sent';
                }
            }
        } elseif ($orderStatus == 'Cancelled') {
            return 'FAILED';
        }
        if ($orderStatus == 'Allocated') {
            return 'UNALLOCATED';
        } else {
            return $orderStatus;
        }
    }

    public function getDistribution(Request $request, $dcReference, $shiftDate)
    {
        try {
            // Validate incoming request parameters from the query or body
            $validated = $request->validate([
                'driverReference' => 'nullable|string',
                'vehicleReference' => 'nullable|string',
            ]);

            // Logic to handle when `driverReference` is not empty
            if (!empty($validated['driverReference'])) {
                // If `driverReference` is provided, `dcReference` is not mandatory
                if (empty($shiftDate)) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => [
                            'shiftDate' => 'shiftDate is required when driverReference is provided.',
                        ],
                        'success' => false,
                    ], 422);
                }
            } else {
                // If `driverReference` is not provided, `dcReference` and `shiftDate` are mandatory
                if (empty($dcReference) || empty($shiftDate)) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => [
                            'dcReference' => 'dcReference is required when driverReference is not provided.',
                            'shiftDate' => 'shiftDate is required when driverReference is not provided.',
                        ],
                        'success' => false,
                    ], 422);
                }
            }

            $queryResponse = $this->buildQuery($validated, $dcReference, $shiftDate);

            $formatResponse = $this->formatDistributionData($queryResponse, $shiftDate);

            // merge it only on  the staging for now...
            return (['driverShifts' => $formatResponse]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to distribution centre ',
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    private function buildQuery($validated, $dcReference, $shiftDate)
    {
        $formattedDate = Carbon::createFromFormat('Y-m-d', $shiftDate)->format('d-M-Y');

        $result = Driver::whereHas('driverRoutes', function ($query) use ($formattedDate) {
            $query->where('date', '=', $formattedDate);
        })->with(['driverRoutes' => function ($query) use ($formattedDate) {
            $query->where('date', '=', $formattedDate);
        }]);

        if (!empty($validated['driverReference'])) {
            $result->where('name', '%like%', $validated['driverReference']);
        }

        if (!empty($validated['vehicleReference'])) {
            $vehicleReference  = $validated['vehicleReference'];
            $result->whereHas('vehicle', function ($query) use ($vehicleReference) {
                $query->where('name', '%like%', $vehicleReference);
            });
        }
        if (!empty($dcReference)) {
            $result->whereHas('warehouse', function ($query) use ($dcReference) {
                $query->where('ref_number', $dcReference);
            });
        }
        return $result->get();
    }

    private function formatDistributionData($queryResponse, $shiftDate)
    {
        $day = date('l', strtotime($shiftDate));

        $driverShifts = $queryResponse->map(function ($data, $index) use ($shiftDate, $day) {
            $totalDistance = 0;
            $totalWorkingTime = 0;

            return [
                "id" => $data->id,
                "driverName" => $data->name,
                "driverReference" => $data->external_id,
                "vehicleName" => $data->getvehicle->name ?? null,
                "vehicleReference" => $data->id,
                "shiftDate" => $shiftDate ?? null,
                "shiftStartTime" => $data->driverTime
                    ->filter(fn($drivertime) => $drivertime->day === $day)[0]['start_time'] ?? null,
                "shiftEndTime" => $data->driverTime
                    ->filter(fn($drivertime) => $drivertime->day === $day)[0]['end_time'] ?? null,
                "totalDistance" => &$totalDistance, // Reference to update dynamically

                "runs" => $data->driverRoutes->map(function ($route) use (&$totalDistance, &$totalWorkingTime, $shiftDate) {
                    $plannedLoadingStartTime = 0;
                    $plannedDepartureTime = 0;
                    $plannedReturnStartTime = 0;
                    $plannedCompletionTime = 0;
                    $weight = 0;

                    $allocations = $route->disp->map(function ($disp) use (
                        &$totalDistance,
                        &$totalWorkingTime,
                        &$plannedLoadingStartTime,
                        &$plannedDepartureTime,
                        &$plannedReturnStartTime,
                        &$plannedCompletionTime,
                        &$weight,
                        $shiftDate
                    ) {

                        // Check if ref_no or deliverBy_datetime is null, exclude those orders
                        if (empty($disp->order->ref_no) || empty($disp->order->deliverBy_datetime)) {
                            return null; // Exclude the order
                        }

                        $totalDistance += $disp->order->total_distance ?? 0;
                        $totalWorkingTime += $disp->order->total_duration ?? 0;

                        $weight = is_numeric($weight) ? (float)$weight : 0;
                        $orderWeight = is_numeric($disp->order?->weight ?? 0) ? (float)($disp->order?->weight ?? 0) : 0;
                        $weight += $orderWeight;

                        if (empty($disp->order->max_parent_order_id)) {
                            $plannedLoadingStartTime = $disp->order->loadingTime ?? null;
                            $plannedDepartureTime = $disp->order->startTime ?? null;
                            $plannedReturnStartTime = $disp->order->returnStartTime ?? null;
                            $plannedCompletionTime = $disp->order->returnWareHouseTime ?? null;
                        }

                        return [
                            'id' => $disp->order->id ?? null,
                            'orderReference' => $disp->order->ref_no ?? null,
                            'customerLocationName' => $disp->order->customer->businessName ?? null,
                            'customerLocationAddress' => $disp->order->customer->street ?? null,
                            'latitude' => $disp->order->lat ?? null,
                            'longitude' => $disp->order->lng ?? null,
                            'plannedDrivingStartTime' => $this->convertToUtcTimestamp($shiftDate, $disp->order->startTime),
                            'plannedArrivalTime' => $this->convertToUtcTimestamp($shiftDate, $disp->order->arriveTime),
                            'plannedCompletionTime' => $this->convertToUtcTimestamp($shiftDate, $disp->order->total_duration),
                            'status' => $disp->order->orderStatus ?? null,
                            'sequenceNumber' => $disp->num ?? null,
                            'task' => $disp->order->orderType ?? null,
                        ];
                    })->filter()->values() // Remove null allocations and reset keys
                    ->sortBy('sequenceNumber')->values(); // Sort by sequenceNumber in ascending order
                    if ($allocations->isEmpty()) {
                        return null; // Skip this run if allocations are empty
                    }

                    return [
                        "runNumber" => $route->id,
                        "plannedLoadingStartTime" => &$plannedLoadingStartTime,
                        "plannedDepartureTime" => &$plannedDepartureTime,
                        "plannedReturnStartTime" => &$plannedReturnStartTime,
                        "plannedCompletionTime" => &$plannedCompletionTime,
                        "totalOrders" => $route->disp->count(),
                        "totalDuration" => $totalWorkingTime,
                        "totalDistance" => $totalDistance,
                        "totalCapacity1" => &$weight,
                        "totalCapacity2" => 0,
                        "isLocked" => true,
                        "reference" => null,
                        "totalDeliveries" => $route->disp->filter(fn($disp) => $disp->order->orderType == "Delivery")->count(),
                        "totalCollections" => $route->disp->filter(fn($disp) => $disp->order->orderType == "Collection")->count(),
                        "allocations" => $allocations,
                    ];
                })->filter()->values(), // Remove null runs and reset keys

                "totalWorkingTime" => $this->convertToUtcTimestamp($shiftDate, $totalWorkingTime),
                "totalDrivingTime" => $this->convertToUtcTimestamp($shiftDate, $totalWorkingTime),
            ];
        });

        return $driverShifts->filter(fn($shift) => $shift['runs']->isNotEmpty())->values();
    }


    private function convertToUtcTimestamp(string $date, int $seconds  = null): string
    {
        return Carbon::parse($date)
            ->addSeconds($seconds)
            ->utc()
            ->format('Y-m-d\TH:i:s\Z');
    }


    // get the order tracking
    public function getOrderTracking($orderReference)
    {
        try {
            $order = Order::where('ref_no', $orderReference)
                ->join('disp', 'disp.oid', '=', 'orders.id')
                ->join('warehouses', 'warehouses.id', '=', 'orders.warehouse_id')
                ->leftjoin('driver_locations', 'driver_locations.route_id', '=', 'disp.id_route')
                ->select([
                    'orders.ref_no',
                    'orders.warehouse_id',
                    'orders.orderStatus',
                    'driver_locations.lat',
                    'driver_locations.lng',
                    'disp.id_route',
                    'warehouses.latitude',
                    'warehouses.longitude'
                ])
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found.'
                ], 404);
            }

            $response = [
                'data' => [
                    'orderReferenceNumber' => $order->ref_no,
                    'currentPosition' => [
                        'latitude' => $order->lat ?? $order->latitude,
                        'longitude' => $order->lng  ?? $order->longitude,
                    ],
                    'status' => $order->orderStatus
                ]
            ];

            // merege it only on th staging for now
            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching order tracking details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // get the order pod
    public function getOrderPod($orderReference)
    {
        try {
            $order = Order::where('ref_no', $orderReference)
                ->leftjoin('disp', 'disp.oid', '=', 'orders.id')
                ->leftjoin('order_images', 'order_images.order_id', '=', 'orders.id')
                ->leftjoin('driver_locations', 'driver_locations.route_id', '=', 'disp.id_route')
                ->leftjoin('drivers', 'driver_locations.driver_id', '=', 'drivers.id')
                ->where('order_images.type', 'order_signature')
                ->select([
                    'orders.id',
                    'orders.ref_no',
                    'order_images.created_at',
                    'order_images.path',
                    'order_images.order_id',
                    'drivers.name',
                ])
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found.'
                ], 404);
            }
            $response = [
                'data' => [
                    'orderReferenceNumber' => $order->ref_no,
                    'signatoryName' => $order->name,
                    'signatureTime' =>  Carbon::parse($order->created_at)->format('Y-m-d\TH:i:s\Z'),
                    'signatureImage' =>  asset('/' . $order->path),
                ]
            ];
            // marge it only on staging
            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching order pod details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // get the order widget
    public function getOrderWidget($orderReference)
    {
        try {
            $order = Order::where('ref_no', $orderReference)
                ->leftjoin('disp', 'disp.oid', '=', 'orders.id')
                ->select([
                    'orders.id',
                    'orders.ref_no',
                    'disp.oid',
                ])
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found.'
                ], 404);
            }
            $response = [
                'data' => [
                    "trackingId" => $order->id ?? null,
                    "externalTrackingUri" => "https://piloting.premierdeliveries.co.uk/track/" . $order->id ?? null,
                ]
            ];

            // merge it on staging it for now
            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching order widget.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // get the order attachments
    public function getOrderAttachments($orderReference)
    {
        try {
            $order = Order::where('orders.ref_no', $orderReference)
                ->leftJoin('order_rejection_details', 'orders.id', '=', 'order_rejection_details.order_id')
                ->select(['orders.*', 'order_rejection_details.order_id', 'order_rejection_details.comments']) // Ensures you get an Eloquent model instance
                ->with([
                    'orderAttachments' => function ($query) {
                        $query->where('type', '!=', 'order_signature');
                    }
                ]) // Eager loading relationships with filtering
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found.'
                ], 404);
            }

            $attachments = [];
            foreach ($order->orderAttachments as $attachment) {
                // Handling multiple images separated by commas
                $paths = explode(',', $attachment->path);

                foreach ($paths as $path) {
                    $attachments[] = [
                        'attachmentReferenceNumber' => 'ATT-' . $attachment->id, // Custom reference
                        'orderReferenceNumber'      => $order->ref_no,
                        'comment'                   => $order->comments,
                        'type'                   => $attachment->type,
                        'imageSmall'                => asset('/' . $path), // Small image URL
                        'imageFull'                 => asset('/' . $path)  // Full image URL
                    ];
                }
            }

            $response = [
                'data' => [
                    'orderReferenceNumber' => $order->ref_no,
                    'attachments'          => $attachments
                ]
            ];

            // merge it on stagin..
            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching order attachments.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
