<?php

namespace App\Http\Controllers;

use App\Models\{
    Order,
    Vehicle
};
use App\Exports\{
    ScheduledOrders,
    UnPlannedOrders,
    OrderExport
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportsController extends Controller
{
    /**
     * Export Scheduled Orders to CSV
     * 
     * Exports scheduled or unplanned orders to CSV format based on date and distribution centre.
     * 
     * @authenticated
     * 
     * @bodyParam date string required The date in d/m/Y format. Example: 25/12/2023
     * @bodyParam distribution_centre integer required The ID of the distribution centre. Example: 1
     * @bodyParam unplannedOrders boolean Whether to export unplanned orders (1) or planned orders (0). Example: 1
     * 
     * @response 200 binary CSV file download
     * 
     * @response 422 {
     *     "message": "The date field is required."
     * }
     */
    public function sheduledOrdersCsv(Request $request): BinaryFileResponse
    {
        $request->validate([
            'date' => 'required|date_format:d/m/Y',
            'distribution_centre' => 'required|exists:warehouses,id',
            'unplannedOrders' => 'boolean'
        ]);

        $carbonDate = Carbon::createFromFormat('d/m/Y', $request->date);
        $formattedDate = $carbonDate->format('d-M-Y');

        return $request->unplannedOrders
            ? $this->exportUnplannedOrders($request->distribution_centre, $formattedDate)
            : $this->exportPlannedOrders($request->distribution_centre);
    }

    private function exportUnplannedOrders(int $distributionCentre, string $formattedDate): BinaryFileResponse
    {
        $orders = Order::with(['orderItems', 'customer', 'orderOperationTimeWindow'])
            ->where('warehouse_id', $distributionCentre)
            ->where('startDate', $formattedDate)
            ->where(function ($query) use ($formattedDate) {
                $query->where('orderStatus', 'Allocated')
                    ->orWhereNull('orderStatus')
                    ->where('startDate', $formattedDate);
            })
            ->limit(100)
            ->get();

        return Excel::download(new UnplannedOrders($orders), 'unplanned.csv');
    }

    private function exportPlannedOrders(int $distributionCentre): BinaryFileResponse
    {
        $vehicles = Vehicle::with([
            'driver',
            'disp.order'
        ])
            ->where('distribution_centre_id', $distributionCentre)
            ->get();

        $this->processVehicleOrders($vehicles);

        return Excel::download(new ScheduledOrders($vehicles), 'planned.csv');
    }

    private function processVehicleOrders($vehicles): void
    {
        $vehicles->each(function ($vehicle) {
            $vehicle->disp->transform(function ($dispatch) {
                if (!is_null($dispatch->order)) {
                    return array_merge($dispatch->order->toArray());
                }
                return $dispatch;
            });
        });

        $vehicles->each(function ($vehicle) {
            if (!is_null($vehicle->driver)) {
                $vehicle->mergedData = array_merge(
                    $vehicle->toArray(),
                    $vehicle->driver->toArray()
                );
            }
        });
    }

    /**
     * Export Orders to CSV
     * 
     * Export filtered orders to CSV format with related data.
     * 
     * @group Export Management
     * 
     * @queryParam per_page integer Number of records per page. Example: 100
     * @queryParam searchInput string Search term for filtering orders. Example: "ORD123"
     * @queryParam distribution_centre array Distribution centre IDs to filter by. Example: [1,2]
     * @queryParam creation_time_from string Start date for order creation filter (d/m/Y). Example: "01/03/2024"
     * @queryParam creation_time_to string End date for order creation filter (d/m/Y). Example: "31/03/2024"
     * @queryParam priority string Priority level filter. Example: "High"
     * @queryParam orderStatus array Order status filter. Example: ["Allocated", "Completed"]
     * @queryParam vehicleReq array Vehicle requirement IDs filter. Example: [1,2]
     * @queryParam clientName string Client business name filter. Example: "ACME Corp"
     * 
     * @response BinaryFileResponse CSV file download
     * 
     * @response 422 {
     *   "message": "Invalid filter parameters",
     *   "errors": {
     *     "creation_time_from": ["The creation time from must be a valid date."]
     *   },
     *   "success": false
     * }
     */
    public function exportCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1',
            'searchInput' => 'nullable|string|max:255',
            'distribution_centre' => 'nullable|array',
            'distribution_centre.*' => 'integer|exists:warehouses,id',
            'creation_time_from' => 'nullable|date_format:d/m/Y',
            'creation_time_to' => 'nullable|date_format:d/m/Y',
            'priority' => 'nullable|string|in:Low,Medium,High',
            'orderStatus' => 'nullable|array',
            'orderStatus.*' => 'string|in:Allocated,Completed,In Progress,Cancelled',
            'vehicleReq' => 'nullable|array',
            'vehicleReq.*' => 'integer|exists:vehicle_requirements,id',
            'clientName' => 'nullable|string|max:255|exists:customers,businessName'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid filter parameters',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {

            $request->merge(['filter' => 'true']);
            $response = $this->getOrders($request);

            if (!$response->original['success']) {
                throw new \Exception('Failed to fetch orders');
            }

            $data = $response->original['orders'];

            return Excel::download(
                new OrderExport($data),
                'orders-' . now()->format('Y-m-d') . '.csv'
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export orders',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    private function getOrders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filter' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:10000',
            'page' => 'nullable|integer|min:1',
            'searchInput' => 'nullable|string|max:255',
            'distribution_centre' => 'nullable|array',
            'distribution_centre.*' => 'integer|exists:warehouses,id',
            'creation_time_from' => 'nullable|date_format:d/m/Y',
            'creation_time_to' => 'nullable|date_format:d/m/Y',
            'priority' => 'nullable|string|in:Low,Medium,High',
            'orderStatus' => 'nullable|array',
            'orderStatus.*' => 'string|in:Allocated,Completed,In Progress,Cancelled',
            'vehicleReq' => 'nullable|array',
            'vehicleReq.*' => 'integer|exists:vehicle_requirements,id',
            'clientName' => 'nullable|string|max:255|exists:customers,businessName',
            'drop_time_from' => 'nullable|date_format:H:i',
            'drop_time_to' => 'nullable|date_format:H:i|after:drop_time_from',
            'completionStatus' => 'nullable|array',
            'completionStatus.*' => 'string|in:Fully delivered,Not delivered,Partially delivered'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid filter parameters',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $perPage = $request->filter == 'true' ? 10000 : ($request->per_page ?? 100);
            $page = $request->filter == 'true' ? 1 : ($request->page ?? 1);

            $query = Order::with([
                'warehouse',
                'orderOperationTimeWindow',
                'customer',
            ])
                ->join('customers', 'customers.id', '=', 'orders.cid')
                ->select('orders.*');

            $this->applyFilters($query, $request);

            $orders = $query->paginate($perPage, ['*'], 'page', $page);

            $orders->getCollection()->transform(function ($order) {
                $order->order_items = $order->orderItems->transform(function ($item) {
                    return $item;
                });
                unset($order->orderItems);
                return $order;
            });

            return response()->json([
                'orders' => $orders,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Apply filters to the order query
     */
    private function applyFilters($query, Request $request): void
    {
        $query->where(function ($query) use ($request) {
            if ($request->searchInput) {
                $query->where('orders.id', 'like', $request->searchInput)
                    ->orWhere('orders.ref_no', 'like', $request->searchInput)
                    ->orWhere('orders.warehouse_id', 'like', $request->searchInput)
                    ->orWhere('customers.businessName', 'like', $request->searchInput);
            }

            if ($request->distribution_centre) {
                $query->whereIn('orders.warehouse_id', $request->distribution_centre);
            }

            if ($request->orderStatus) {
                $query->whereIn('orders.orderStatus', $request->orderStatus);
            }

            if ($request->clientName) {
                $query->where('customers.businessName', $request->clientName);
            }

            if ($request->priority) {
                $query->where('orders.priority', $request->priority);
            }

            if ($request->vehicleReq) {
                $query->where(function ($q) use ($request) {
                    foreach ($request->vehicleReq as $reqId) {
                        $q->orWhereRaw(
                            "FIND_IN_SET(?, REPLACE(REPLACE(orders.vehicle_requirement_id, '[', ''), ']', ''))",
                            [$reqId]
                        );
                    }
                });
            }

            $this->applyDateFilters($query, $request);
            $this->applyDeliveryStatusFilters($query, $request);
        });
    }

    /**
     * Apply date filters to the query
     */
    private function applyDateFilters($query, Request $request): void
    {
        if ($request->creation_time_from) {
            $fromDate = Carbon::createFromFormat('d/m/Y', $request->creation_time_from)->toDateString();
            $query->whereDate('orders.time_stamp', '>=', $fromDate);
        }

        if ($request->creation_time_to) {
            $toDate = Carbon::createFromFormat('d/m/Y', $request->creation_time_to)->toDateString();
            $query->whereDate('orders.time_stamp', '<=', $toDate);
        } else {
            $query->whereDate('orders.time_stamp', '<=', Carbon::today()->toDateString());
        }
    }

    /**
     * Apply delivery status filters to the query
     */
    private function applyDeliveryStatusFilters($query, Request $request): void
    {
        if ($request->completionStatus) {
            $query->whereHas('orderItems');

            $query->where(function ($q) use ($request) {
                if (in_array('Fully delivered', $request->completionStatus)) {
                    $q->whereDoesntHave('orderItems', function ($q) {
                        $q->where('itemStatus', '!=', 'Checked');
                    });
                }
                if (in_array('Not delivered', $request->completionStatus)) {
                    $q->whereDoesntHave('orderItems', function ($q) {
                        $q->where('itemStatus', '=', 'Checked');
                    });
                }
                if (in_array('Partially delivered', $request->completionStatus)) {
                    $q->whereHas('orderItems', function ($q) {
                        $q->where('itemStatus', '=', 'Checked');
                    })->whereHas('orderItems', function ($q) {
                        $q->where('itemStatus', '!=', 'Checked');
                    });
                }
            });
        }
    }
}
