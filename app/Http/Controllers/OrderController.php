<?php

namespace App\Http\Controllers;

use App\Helpers\MapsHelper;
use App\Helpers\FcmFunctionHelper;
use App\Helpers\OrderLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    DB,
    Log,
    Validator,
    Http
};

// Models
use App\Models\{
    Order,
    Customer,
    OrderItems,
    StockItem,
    StockDetail,
    User,
    Warehouse,
    Disp,
    Driver,
    OrderItem,
    OrderOperationTimeWindow,
    OrderRejectionDetail,
    DispRoute
};

use App\Services\OrderMergeService;
// Carbon
use Carbon\Carbon;

// Enums
use App\Enums\OrderAction;

//  Use protected function of getOrderSelectFields   of planning tool controller
use App\Http\Controllers\PlanningToolController;
/**
 * @group Order Management
 *
 * APIs for managing orders including imports and processing
 */
class OrderController extends Controller
{
    /**
     * Import Orders
     *
     * Import multiple orders from the provided data. This endpoint allows bulk import of orders
     * with options to filter by date and remove existing orders.
     *
     * @authenticated
     *
     * @bodyParam validData array required Array of order data to import. Example: [{"orderReference": "ORD123", "date": "25/12/2023"}]
     * @bodyParam validData.*.orderReference string required Unique reference number for the order. Example: ORD123
     * @bodyParam validData.*.date string required Order date in d/m/Y format. Example: 25/12/2023
     * @bodyParam validData.*.contactNumber string required Customer contact number. Example: +1234567890
     * @bodyParam validData.*.customerLocationAddress string required Delivery address. Example: 123 Main St
     * @bodyParam validData.*.customerLocationName string required Location name. Example: Office Building
     * @bodyParam validData.*.latitude string required Delivery location latitude. Example: 51.5074
     * @bodyParam validData.*.longitude string required Delivery location longitude. Example: -0.1278
     * @bodyParam validData.*.status string required Order status. Example: pending
     * @bodyParam validData.*.orderItems string optional Semicolon-separated order items. Example: [Item1]123|EXT456|0|2|Description|10|20|30|5|2
     * @bodyParam date string optional Filter orders by specific date (d/m/Y). Example: 25/12/2023
     * @bodyParam removeExsistingOrder boolean optional Whether to remove existing orders. Example: true
     * @bodyParam selectedDistCenter integer optional Default warehouse ID. Example: 1
     *
     * @response 200 {
     *     "message": "5 orders imported successfully",
     *     "successful_orders": [
     *         {
     *             "id": 1,
     *             "ref_no": "ORD123",
     *             "warehouse_id": 1,
     *             "orderStatus": "Allocated",
     *             "deliverBy_datetime": "2023-12-25 00:00:00"
     *         }
     *     ],
     *     "failed_orders": []
     * }
     *
     * @response 422 {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "validData.0.contactNumber": ["The contact number field is required."]
     *     }
     * }
     *
     * @response 500 {
     *     "message": "Server error occurred while processing orders"
     * }
     *
     * @throws \Exception When order processing fails
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        // Initialize collections and get request data
        $data = collect($request->validData);

        // Filter data by date if provided
        if (!is_null($request->date)) {
            $data = $this->filterDataByDate($request->date, $data);
        }

        // Remove existing orders if requested
        if ($request->has('removeExsistingOrder') && $request->removeExsistingOrder) {
            $this->removeExistingOrders($data);
        }

        // Process orders
        $processedOrders = $this->processOrders($data, $request);

        return response()->json([
            "message" => count($processedOrders['successful_orders']) . " orders imported successfully",
            "successful_orders" => $processedOrders['successful_orders'],
            "failed_orders" => $processedOrders['failed_orders']
        ], 200);
    }

    /**
     * Filter data collection by specific date
     *
     * @param string $requestDate
     * @param \Illuminate\Support\Collection $data
     * @return \Illuminate\Support\Collection
     */
    private function filterDataByDate($requestDate, $data)
    {
        $formattedDate = Carbon::createFromFormat('d/m/Y', $requestDate)->format('Y-m-d');
        return $data->filter(function ($dt) use ($formattedDate) {
            return isset($dt['date']) &&
                Carbon::createFromFormat('d/m/Y', $dt['date'])->format('Y-m-d') == $formattedDate;
        });
    }
    /**
     * Remove existing orders and their related data
     *
     * @param \Illuminate\Support\Collection $data
     * @return void
     */
    private function removeExistingOrders($data)
    {
        foreach ($data as $order) {
            $date = Carbon::createFromFormat('d/m/Y', $order['date'])->format('Y-m-d');
            $orderIds = $this->findExistingOrderIds($order, $date);

            if (empty($orderIds)) {
                continue;
            }

            try {
                $this->deleteOrderAndRelatedData($orderIds);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to delete order and related data',
                    'error' => $e->getMessage(),
                    'success' => false
                ], 500);
            }
        }
    }

    /**
     * Find existing order IDs based on reference and date
     *
     * @param array $order
     * @param string $date
     * @return array
     */
    private function findExistingOrderIds($order, $date)
    {
        return Order::where('ref_no', $order["orderReference"])
            ->where('orderStatus', 'Allocated')
            ->whereDate('deliverBy_datetime', $date)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Delete order and all related data
     *
     * @param array $orderIds
     * @return void
     */
    private function deleteOrderAndRelatedData($orderIds)
    {
        DB::transaction(function () use ($orderIds) {
            // Delete related records that don't have dependencies first
            OrderOperationTimeWindow::whereIn('order_id', $orderIds)->delete();

            // Get order items and their related stock IDs
            $orderItems = OrderItem::whereIn('oid', $orderIds)->get();
            if ($orderItems->isNotEmpty()) {
                $itemIds = $orderItems->pluck('itemid')->toArray();

                // Delete order items first (they reference stock_items)
                OrderItem::whereIn('oid', $orderIds)->delete();

                // Then delete stock details (they reference stock_items)
                StockDetail::whereIn('sid', $itemIds)->delete();

                // Finally delete stock items
                StockItem::whereIn('id', $itemIds)->delete();
            }

            // Delete the main orders
            Order::whereIn('id', $orderIds)->delete();
        });
    }

    /**
     * Process orders from the data collection
     *
     * @param \Illuminate\Support\Collection $data
     * @param Request $request
     * @return array
     */
    private function processOrders($data, $request)
    {
        $successful_orders = [];
        $failed_orders = [];

        foreach ($data as $dt) {
            if (!$this->validateOrderData($dt, $failed_orders)) {
                continue;
            }

            try {

                if ($dt['latitude'] == 0 || $dt['longitude'] == 0) {
                    throw new \Exception('Invalid location coordinates provided');
                }

                // Create customer and order
                $customer = $this->createCustomer($dt);
                $order = $this->createOrder($dt, $customer->id, $request);

                // Process order items if present
                if (!$order) throw new \Exception('Failed to create order');

                if (isset($dt['orderItems']) && $order) {
                    $this->processImportOrderItems($dt['orderItems'], $order->id);
                }

                // Create operation time window
                if ($order) {
                    $this->createOperationTimeWindow($dt['date'], $order->id);
                }


                $successful_orders[] = $order;
            } catch (\Exception $e) {

                $friendlyMessage = $this->getFriendlyErrorMessage($e->getMessage());
                $dt['failure_reason'] = $friendlyMessage;
                $failed_orders[] = $dt;
            }
        }

        return [
            'successful_orders' => $successful_orders,
            'failed_orders' => $failed_orders
        ];
    }

    /**
     * Validate order data
     *
     * @param array $data
     * @param array &$failed_orders Reference to failed orders array
     * @return bool
     */
    private function validateOrderData($data, &$failed_orders)
    {
        $validator = Validator::make($data, [
            'contactNumber' => 'required',
            'customerLocationAddress' => 'required',
            'customerLocationName' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            $data['failure_reason'] = 'Validation failed: ' . implode(', ', $validator->errors()->all());
            $failed_orders[] = $data;
            return false;
        }

        return true;
    }

    /**
     * Create a new customer record
     *
     * @param array $data
     * @return Customer
     */
    private function createCustomer($data)
    {
        $customer = new Customer;
        $customer->userID = $data['user_id'] ?? null;
        $customer->businessName = $data['clientName'] ?? null;
        $customer->mob = $data['contactNumber'] ?? null;
        $customer->number = $data['contactNumber'] ?? null;
        $customer->email1 = $data['contactEmail'] ?? null;
        $customer->web = $data['webref'] ?? null;
        $customer->street = $data['customerLocationName'] ?? null;
        $customer->postcode = $data['customerLocationAddress'] ?? null;
        $customer->save();

        return $customer;
    }

    /**
     * Create a new order record
     *
     * @param array $data
     * @param int $customerId
     * @param Request $request
     * @return Order
     */
    private function createOrder($data, $customerId, $request)
    {
        try {


            // Create main order
            $order = new Order;
            $order->ref_no = $data['orderReference'] ?? null;
            $order->warehouse_id = $this->determineWarehouseId($data, $request);
            $order->priority = $data['priority'] ?? 'Normal';
            $order->vehicle_requirement_id = $data['vehicleRequirements'] ?? null;
            $order->description = $data['additionalInstructions'] ?? null;
            $order->orderType = $data['task'] ?? null;
            $order->speed_zone_id = $data['speed_zone_id'] ?? null;
            $order->territory_id = $data['territory_id'] ?? null;
            $order->weight = $data['capacity'] ?? null;
            $order->volume = $data['volume'] ?? null;
            $order->collection = $this->determineCollectionStatus($data);
            $order->deliverBy_datetime = Carbon::createFromFormat('d/m/Y', $data['date'])->format('Y-m-d');
            $order->operation_duration = $data['dropDuration'] ?? null;
            $order->lat = $data['latitude'] ?? null;
            $order->lng = $data['longitude'] ?? null;
            $order->orderStatus = 'Allocated';
            $order->error = $data['newField'] ?? 0;
            $order->cid = $customerId;
            $order->return_duration = $data['return_duration'] ?? null;
            $order->return_distance = $data['return_distance'] ?? null;
            $order->total_distance = $data['total_distance'] ?? null;
            $order->total_duration = $data['total_duration'] ?? null;
            $order->runDuration = $data['runDuration'] ?? null;
            $order->runTotalDrivingTime = $data['runTotalDrivingTime'] ?? null;
            $order->loadingTime = $data['loadingTime'] ?? null;
            $order->startTime = $data['startTime'] ?? null;
            $order->waitingTime = $data['waitingTime'] ?? null;
            $order->workStartTime = $data['workStartTime'] ?? null;
            $order->returnStartTime = $data['returnStartTime'] ?? null;
            $order->returnWareHouseTime = $data['returnWareHouseTime'] ?? null;
            $order->arriveTime = $data['arriveTime'] ?? null;
            $order->operationEndTime = $data['operationEndTime'] ?? null;
            $order->timeViolation = $data['timeViolation'] ?? null;
            $order->weightExceeded = $data['weightExceeded'] ?? null;
            $order->howMuchWeightOver = $data['howMuchWeightOver'] ?? null;
            $order->volumeExceeded = $data['volumeExceeded'] ?? null;
            $order->howMuchVolumeOver = $data['howMuchVolumeOver'] ?? null;
            $order->save();


            return $order;
        } catch (\Exception $e) {

            throw $e;
        }
    }

    /**
     * Determine warehouse ID based on distribution centre name
     *
     * @param array $data
     * @param Request $request
     * @return int
     */
    private function determineWarehouseId($data, $request)
    {
        if (!empty($data['distributionCentreName'])) {
            $warehouse = Warehouse::where('name_of_warehouse', $data['distributionCentreName'])->first();
            return $warehouse ? $warehouse->id : ($request->selectedDistCenter ?? 1);
        }
        return $request->selectedDistCenter ?? 1;
    }

    /**
     * Determine collection status based on task
     *
     * @param array $data
     * @return int
     */
    private function determineCollectionStatus($data)
    {
        return isset($data['task']) && strtolower($data['task']) == "collection" ? 1 : 0;
    }

    /**
     * Process order items from string format (used in import)
     *
     * @param string $orderItemsString Semicolon-separated items string
     * @param int $orderId
     * @return void
     */
    private function processImportOrderItems(string $orderItemsString, int $orderId): void
    {
        // Split items by semicolon
        $items = explode(';', $orderItemsString);

        foreach ($items as $item) {
            if (empty(trim($item))) {
                continue; // Skip empty items
            }

            $itemData = $this->parseItemData($item);
            $this->createOrderItemRecords($itemData, $orderId);
        }
    }

    /**
     * Parse item data from string
     * Format: [ItemName]UniqueCode|ExternalId|Price|Quantity|Description|Width|Length|Height|Weight|Volume
     * Example: [D01-3FT-2DOS-NBLK]Yg9x8wm5ck001|82458|0|1|D01-3FT-2DOS-NBLK|30|90|190|23|0.5985
     *
     * @param string $item
     * @return array
     */
    private function parseItemData($item): array
    {
        $parts = explode('|', $item);

        // Handle the item name and unique code
        $itemNamePart = $parts[0];
        preg_match('/\[(.*?)\](.*?)$/', $itemNamePart, $matches);

        $itemName = $matches[1] ?? ''; // Content between []
        $uniqueCode = $matches[2] ?? ''; // Content after ]

        return [
            'order_name' => trim($itemName),
            'item_ex_id' => trim($parts[1] ?? ''),
            'barcode' => trim($uniqueCode ?? ''),         // External ID/barcode
            'price_per_unit' => (float)($parts[2] ?? 0), // Price
            'quantity' => (int)($parts[3] ?? 0),         // Quantity
            'description' => trim($parts[4] ?? ''),      // Description
            'width' => (float)($parts[5] ?? 0),         // Width
            'length' => (float)($parts[6] ?? 0),        // Length
            'height' => (float)($parts[7] ?? 0),        // Height
            'weight' => (float)($parts[8] ?? 0),        // Weight
            'volume' => (float)($parts[9] ?? 0)         // Volume
        ];
    }

    /**
     * Create stock item, stock detail and order item records
     *
     * @param array $itemData
     * @param int $orderId
     * @return void
     */
    private function createOrderItemRecords($itemData, $orderId): void
    {
        // Create stock item
        $stockItem = StockItem::create([
            'itemName' => $itemData['order_name'],
            'itemDescription' => $itemData['description'],
            'itemQty' => $itemData['quantity'],
        ]);

        // Create stock detail with dimensions
        StockDetail::create([
            'sid' => $stockItem->id,
            'height' => $itemData['height'],
            'length' => $itemData['length'],
            'width' => $itemData['width'],
            'weight' => $itemData['weight'],
        ]);

        // Create order item
        OrderItem::create([
            'bedsName' => $itemData['order_name'],
            'notes' => $itemData['description'],
            'price' => $itemData['price_per_unit'],
            'Qty' => $itemData['quantity'],
            'barcode' => $itemData['barcode'],
            'item_ex_id' => $itemData['item_ex_id'],
            'oid' => $orderId,
            'itemid' => $stockItem->id,
            'itemStatus' => 'Unchecked',
            'item_actual_quantity' => $itemData['quantity']
        ]);
    }

    /**
     * Create operation time window for order
     *
     * @param string $date
     * @param int $orderId
     * @return void
     */
    private function createOperationTimeWindow($date, $orderId)
    {

        $givenDate = Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');

        $newOpertionWindow = new OrderOperationTimeWindow;
        $newOpertionWindow->order_id = $orderId;
        $newOpertionWindow->date = $givenDate;
        $newOpertionWindow->start_time = "06:30";
        $newOpertionWindow->end_time = "20:00";
        $newOpertionWindow->save();
    }

    /**
     * Get Orders with Filtering
     *
     * Retrieves orders with various filter options including pagination, search, status, and delivery filters
     *
     * @authenticated
     *
     * @queryParam filter boolean Filter flag to determine pagination limit. Example: true
     * @queryParam per_page integer Number of items per page. Default: 100. Example: 100
     * @queryParam page integer Current page number. Example: 1
     * @queryParam searchInput string Search term for orders. Example: ORD123
     * @queryParam distribution_centre array Distribution center IDs to filter by. Example: [1,2,3]
     * @queryParam creation_time_from string Start date for order creation (d/m/Y). Example: 01/01/2024
     * @queryParam creation_time_to string End date for order creation (d/m/Y). Example: 31/01/2024
     * @queryParam priority string Priority level filter. Example: high
     * @queryParam orderStatus array[] Order status filter. Example: ["Allocated","Pending"]
     * @queryParam vehicleReq array Vehicle requirement IDs. Example: [1,2]
     * @queryParam clientName string Client business name filter. Example: ACME Corp
     * @queryParam completionStatus array Delivery status filter ["Fully delivered","Not delivered","Partially delivered"]. Example: ["Fully delivered"]
     *
     * @response 200 {
     *     "orders": {
     *         "current_page": 1,
     *         "data": [
     *             {
     *                 "id": 1,
     *                 "ref_no": "ORD123",
     *                 "warehouse_id": 1,
     *                 "orderStatus": "Allocated",
     *                 "deliverBy_datetime": "2023-12-25 00:00:00"
     *             }
     *         ],
     *         "total": 100,
     *         "per_page": 100
     *     },
     *     "success": true
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrders(Request $request)
    {
        // Set pagination parameters
        $perPage = $this->determinePaginationLimit($request);
        $page = $request->filter == 'true' ? 1 : $request->query('page', 1);

        $query = $this->buildOrderQuery($request);

        /*
        * If `$request->disable_pagination` is true, it means the request is coming from the planning tool.
        * In this case, the response should be the same as the planning tool order response.
        */
        if ($request->disable_pagination) {

            // Instantiate the controller
            $planningToolController = app(PlanningToolController::class);

            $query->select($planningToolController->getOrderSelectFields());
            $orders = $query->get();
        } else {
            $query->select('orders.*');
            $orders = $query->paginate($perPage, ['*'], 'page', $page);
        }

        // Transform the collection
        $this->transformOrderCollection($orders);

        return response()->json([
            'orders' => $orders,
            'success' => true
        ], 200);
    }

    /**
     * Determine pagination limit based on filter
     */
    private function determinePaginationLimit(Request $request): int
    {
        return $request->filter == 'true'
            ? $request->query('per_page', 10000)
            : $request->query('per_page', 100);
    }

    /**
     * Build the main order query with all filters
     */
    private function buildOrderQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {


        return Order::withoutGlobalScope('withExecutionRelations') // Disable specific global scope
              ->without(['childOrders', 'disp'])
              ->with(['warehouse', 'orderOperationTimeWindow', 'orderItems', 'customer', 'orderItems.stock.stockDetail', 'orderAttachments'])
              ->join('customers', 'customers.id', '=', 'orders.cid')
              ->where(function ($query) use ($request) {
                $this->applySearchFilter($query, $request->searchInput);
                $this->applyDistributionCenterFilter($query, json_decode($request->distribution_centre));
                $this->applyStatusFilter($query, $request->orderStatus);
                $this->applyClientFilter($query, $request->clientName);
                $this->applyPriorityFilter($query, $request->priority);
                $this->applyVehicleRequirementFilter($query, $request->vehicleReq);
                $this->applyCreationTimeFilter($query, $request);
                $this->applyDeliveryStatusFilter($query, $request->completionStatus);
                $this->applyTimewindDropFilter($query, $request->dropDateFrom, $request->dropDateTo);
            });
    }

    /**
     * Transform order collection to desired format
     */
    private function transformOrderCollection($orders): void
    {
        $orders->transform(function ($order) {
            $order->order_items = $order->orderItems->transform(function ($item) {
                return $item;
            });
            unset($order->orderItems);
            return $order;
        });
    }

    /**
     * Apply search filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $searchInput
     * @return void
     */
    private function applySearchFilter($query, $searchInput): void
    {
        if (!empty($searchInput)) {
            $query->where(function ($q) use ($searchInput) {
                $q->where('orders.ref_no', 'LIKE', "%{$searchInput}%")
                    ->orWhere('customers.businessName', 'LIKE', "%{$searchInput}%");
            });
        }
    }

    /**
     * Apply distribution center filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $distributionCentres
     * @return void
     */
    private function applyDistributionCenterFilter($query, $distributionCentres = null): void
    {
        if ($distributionCentres) {
            $query->whereIn('warehouse_id', $distributionCentres);
        }
    }

    /**
     * Apply status filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $orderStatus
     * @return void
     */
    private function applyStatusFilter($query, $orderStatus): void
    {
        if (!empty($orderStatus)) {
            $query->whereIn('orders.orderStatus', $orderStatus);
        }
    }

    /**
     * Apply client filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $clientName
     * @return void
     */
    private function applyClientFilter($query, $clientName): void
    {
        if (!empty($clientName)) {
            $query->where('customers.businessName', 'LIKE', "%{$clientName}%");
        }
    }

    /**
     * Apply priority filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $priority
     * @return void
     */
    private function applyPriorityFilter($query, $priority): void
    {
        if (!empty($priority)) {
            $query->where('orders.priority', $priority);
        }
    }

    /**
     * Apply vehicle requirement filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $vehicleReq
     * @return void
     */
    private function applyVehicleRequirementFilter($query, $vehicleReq): void
    {
        if (!empty($vehicleReq)) {
            $query->whereJsonContains('orders.vehicle_requirement_id', $vehicleReq);
        }
    }

    /**
     * Apply creation time filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return void
     */
    private function applyCreationTimeFilter($query, $request): void
    {
        if ($request->date) {
            $dateRange = $this->getDateRange($request->date);
            $query->where('deliverBy_datetime', '>=', $dateRange['start'])
                ->where('deliverBy_datetime', '<=', $dateRange['end']);
        }
    }

    /**
     * Get date range
     *
     * @param string $date
     * @return array
     */
    private function getDateRange(string $date): array
    {
        $formattedDate = Carbon::createFromFormat('d/m/Y', $date)->toDateString();
        return [
            'start' => Carbon::parse($formattedDate)->startOfDay(),
            'end' => Carbon::parse($formattedDate)->endOfDay(),
            'dayOfWeek' => Carbon::createFromFormat('d/m/Y', $date)->format('l')
        ];
    }

    /**
     * Apply delivery status filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $deliveryStatus
     * @return void
     */
    private function applyDeliveryStatusFilter($query, $deliveryStatus): void
    {
        if (!empty($deliveryStatus)) {
            $query->whereHas('orderItems');

            $query->where(function ($q) use ($deliveryStatus) {
                if (in_array('Fully delivered', $deliveryStatus)) {
                    $q->orWhereDoesntHave('orderItems', function ($q) {
                        $q->where('itemStatus', '!=', 'Checked');
                    });
                }
                if (in_array('Not delivered', $deliveryStatus)) {
                    $q->orWhereDoesntHave('orderItems', function ($q) {
                        $q->where('itemStatus', '=', 'Checked');
                    });
                }
                if (in_array('Partially delivered', $deliveryStatus)) {
                    $q->orWhere(function ($q) {
                        $q->whereHas('orderItems', function ($q) {
                            $q->where('itemStatus', '=', 'Checked');
                        })->whereHas('orderItems', function ($q) {
                            $q->where('itemStatus', '!=', 'Checked');
                        });
                    });
                }
            });
        }
    }

    /**
     * Apply delivery status filter to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string |null $dropDateFrom
     * @param string |null $dropDateTo
     * @return void
     */
    private function applyTimewindDropFilter($query, $dropDateFrom, $dropDateTo): void
    {
        if (!empty($dropDateFrom) && !empty($dropDateTo)) {
            // Convert request format (d/m/Y) to stored format (Y-m-d H:i:s)
            $dropDateFrom = Carbon::createFromFormat('d/m/Y', $dropDateFrom)->startOfDay()->toDateTimeString();
            $dropDateTo = Carbon::createFromFormat('d/m/Y', $dropDateTo)->endOfDay()->toDateTimeString();

            $query->where('deliverBy_datetime', '>=', $dropDateFrom)
                ->where('deliverBy_datetime', '<=', $dropDateTo);
        }
    }



    /**
     *
     * Save Order
     *
     * Save a new order with customer details
     *
     * @authenticated
     *
     * @bodyParam ref_no string required Order reference number. Example: ORD123
     * @bodyParam lat string required Latitude of delivery location. Example: 51.5074
     * @bodyParam lng string required Longitude of delivery location. Example: -0.1278
     * @bodyParam businessName string required Customer business name. Example: ACME Corp
     * @bodyParam email1 string required Customer email address. Example: contact@acme.com
     * @bodyParam mob string required Customer mobile number. Example: +1234567890
     * @bodyParam warehouse_id integer required Warehouse ID. Example: 1
     * @bodyParam priority string optional Order priority level. Example: high
     * @bodyParam vehicle_requirement_id array optional Vehicle requirements. Example: [1,2]
     * @bodyParam items array optional Order items. Example: [{"bedsName": "Item1", "Qty": 2}]
     * @bodyParam order_opertion_time_window array optional Time windows. Example: [{"date": "2024-01-01", "start_time": "09:00", "end_time": "17:00"}]
     *
     * @response 200 {
     *     "order": {
     *         "id": 1,
     *         "ref_no": "ORD123",
     *         "customer": {},
     *         "orderItems": [],
     *         "orderOpertionTimeWindow": []
     *     },
     *     "success": true
     * }
     *
     * @response 400 {
     *     "errors": {
     *         "ref_no": ["The reference number field is required."]
     *     }
     * }
     */
    public function saveOrder(Request $request)
    {
        // Validate request data
        if (!isset($request->newField) || $request->newField !== 1) {
            $validator = Validator::make($request->all(), [
                'ref_no' => 'required',
                'lat' => 'required',
                'lng' => 'required',
                'businessName' => 'required',
                'email1' => 'required',
                'mob' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
        }

        // Create or update customer
        $customerID = $this->createOrUpdateCustomer($request);

        // Create order
        $order = $this->createOrderRecord($request, $customerID);

        // Add logging after order creation
        OrderLogger::log(
            orderId: $order->id,
            action: OrderAction::CREATED->value,
            status: $order->orderStatus,
            eta: $order->deliverBy_datetime
        );

        // Process order items if present
        if (!empty($request->items)) {
            $this->processSaveOrderItems($request->items, $order->id);
        }

        //  For error bin  order
        if (isset($request->newField) & $request->newField == 1 && isset($request->orderItems) && $order) {
            $this->processImportOrderItems($request->orderItems, $order->id);
        }

        // Process operation time windows if present
        if (!empty($request->order_operation_time_window)) {
            $this->processOperationTimeWindows($request->order_operation_time_window, $order->id);
        }


        // Return response with order details
        $orders = Order::with(['customer', 'orderOperationTimeWindow', 'orderItems'])
            ->where('id', $order->id)
            ->select(Order::getOrderSelectFields())
            ->get()
            ->map(function ($order) {
                $order->position = [(float) $order->lat, (float) $order->lng];   // add the postion array in the order
                return $order;
            });


        return response()->json(['order' => $orders,  'success' => true], 200);
    }

    /**
     * Create or update customer record
     */
    private function createOrUpdateCustomer(Request $request): int
    {
        if ($request->customer_id == '') {
            $customer = new Customer;
        } else {
            $customer = Customer::findOrFail($request->customer_id);
        }

        // for error bin..
        if (isset($request->newField) && $request->newField == 1) {

            $customer->userID = $request->user_id ?? null;
            $customer->businessName = $request->clientName ?? null;
            $customer->mob = $request->contactNumber ?? null;
            $customer->number = $request->contactNumber ?? null;
            $customer->email1 = $request->contactEmail ?? null;
            $customer->web = $request->webref ?? null;
            $customer->street = $request->customerLocationName ?? null;
            $customer->postcode = $request->customerLocationAddress ?? null;

        }

        else{
            $customer->userID = $request->user_id ?? null;
            $customer->businessName = $request->businessName ?? null;
            $customer->mob = $request->mob ?? null;
            $customer->number = $request->number ?? null;
            $customer->email1 = $request->email1 ?? null;
            $customer->web = $request->web ?? null;
            $customer->fax = $request->fax ?? null;
            $customer->street = $request->street ?? null;
            $customer->postcode = $request->postcode ?? null;
        }

        // Save and return ID
        $customer->save();
        return $customer->id;
    }

    /**
     * Create new order record
     */
    private function createOrderRecord(Request $request, int $customerID): Order
    {
        $order = new Order;

        // Basic order information
        $order->ref_no = $request->ref_no ?? $request->orderReference;
        $order->warehouse_id = $request->warehouse_id;
        $order->priority = $request->priority;

        $order->vehicle_requirement_id = $request->vehicle_requirement_id
            ? (is_array($request->vehicle_requirement_id) && !empty($request->vehicle_requirement_id)
                ? (int) $request->vehicle_requirement_id[0]
                : (int) $request->vehicle_requirement_id)
            : null;

        $order->description = $request->description;
        $order->orderType = $request->orderType  ?? $request->task;
        $order->speed_zone_id = $request->speed_zone_id;
        $order->territory_id = $request->territory_id;

        // Measurements
        $order->weight = $request->weight ?? $request->capacity;
        $order->volume = $request->volume ?? $request->volume;

        // Settings and status
        $order->allowNotifications = is_array($request->allowNotifications)
            ? json_encode($request->allowNotifications)
            : $request->allowNotifications;

        $order->stop_sequence = $request->stop_sequence;
        $order->collection = $request->collection ?? false;
        $order->orderStatus = 'Allocated';

        // Timestamps and dates
        $order->deliverBy_datetime = $this->getDeliveryDateTime($request);

        // Location
        $order->lat = $request->lat ?? $request->latitude;
        $order->lng = $request->lng ?? $request->longitude;

        // Other fields
        $order->operation_duration = $request->operation_duration ?? '0 minutes';
        $order->cid = $customerID;
        $order->error = $request->newField ?? 0;
        $order->locked = false;

        $order->save();

        return $order;
    }

    /**
     * Get delivery datetime
     */
    private function getDeliveryDateTime(Request $request): string
    {
        if ($request->has('date') || $request->date != '') {
            return Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
        }
        return Carbon::now()->format('Y-m-d');
    }

    /**
     * Process order items from array format (used in saveOrder)
     *
     * @param array $items Array of order item objects
     * @param int $orderId
     * @return void
     */
    private function processSaveOrderItems(array $items, int $orderId): void
    {
        foreach ($items as $singleObject) {
            $stockItem = $this->createStockItem($singleObject);
            $this->createStockDetail($stockItem->id, $singleObject);
            $this->createOrderItem($orderId, $stockItem->id, $singleObject);
        }
    }

    /**
     * Create stock item
     */
    private function createStockItem(array $itemData): StockItem
    {
        return StockItem::create([
            'itemName' => $itemData['bedsName'] ?? null,
            'itemDescription' => $itemData['notes'] ?? null,
            'itemQty' => $itemData['Qty'] ?? null,
        ]);
    }

    /**
     * Create stock detail
     */
    private function createStockDetail(int $stockItemId, array $itemData): void
    {
        $cbm = $this->calculateCBM($itemData);

        StockDetail::create([
            'sid' => $stockItemId,
            'weight' => $itemData['weight'] ?? null,
            'length' => $itemData['length'] ?? null,
            'width' => $itemData['width'] ?? null,
            'height' => $itemData['height'] ?? null,
            'cbm' => $cbm,
        ]);
    }

    /**
     * Calculate CBM (Cubic Meters)
     */
    private function calculateCBM(array $itemData): ?float
    {
        if (
            isset($itemData['height']) && is_numeric($itemData['height']) &&
            isset($itemData['width']) && is_numeric($itemData['width']) &&
            isset($itemData['length']) && is_numeric($itemData['length'])
        ) {
            return ($itemData['length'] * $itemData['width'] * $itemData['height']) / 1000000;
        }
        return null;
    }

    /**
     * Create order item
     */
    private function createOrderItem(int $orderId, int $stockItemId, array $itemData): void
    {
        $orderItem = new OrderItem;
        $orderItem->oid = $orderId;
        $orderItem->bedsName = $itemData['bedsName'] ?? null;
        $orderItem->notes = $itemData['notes'] ?? null;
        $orderItem->price = $itemData['price'] ?? null;
        $orderItem->Qty = $itemData['Qty'] ?? null;
        $orderItem->costs = $itemData['item_total_amount'] ?? 0;
        $orderItem->barcode = $itemData['item_barcode'] ?? null;
        $orderItem->item_ex_id = $itemData['item_ex_id'] ?? null;
        $orderItem->oid = $orderId;
        $orderItem->itemid = $stockItemId;
        $orderItem->itemStatus = 'Unchecked';
        $orderItem->save();
    }

    /**
     * Process operation time windows
     */
    private function processOperationTimeWindows(array $timeWindows, int $orderId): void
    {
        foreach ($timeWindows as $operationWindow) {
            try {
                $date = $operationWindow['date'] ?? '2024-11-06';

                // Format start_time and end_time with defaults if invalid
                $startTime = $this->formatTimeOrDefault($operationWindow['start_time'] ?? '', '06:30');
                $endTime = $this->formatTimeOrDefault($operationWindow['end_time'] ?? '', '20:00');

                OrderOperationTimeWindow::create([
                    'order_id' => $orderId,
                    'date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            } catch (\Exception $e) {
                // Create record with default values if there's any error
                OrderOperationTimeWindow::create([
                    'order_id' => $orderId,
                    'date' => '2024-11-06',
                    'start_time' => '06:30',
                    'end_time' => '20:00',
                ]);
            }
        }
    }

    /**
     * Format time string or return default value
     *
     * @param string $time
     * @param string $default
     * @return string
     */
    private function formatTimeOrDefault(string $time, string $default): string
    {
        if (empty($time)) {
            return $default;
        }

        try {
            // Try to parse and format the time
            return Carbon::parse($time)->format('H:i');
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     *
     * Update Order
     *
     *
     * Update an existing order
     *
     * @authenticated
     *
     * @bodyParam id integer required Order ID. Example: 1
     * @bodyParam ref_no string required Order reference number. Example: ORD123
     * @bodyParam lat string required Latitude. Example: 51.5074
     * @bodyParam lng string required Longitude. Example: -0.1278
     * @bodyParam businessName string required Business name. Example: ACME Corp
     * @bodyParam email1 string required Email address. Example: contact@acme.com
     * @bodyParam mob string required Mobile number. Example: +1234567890
     * @bodyParam street string required Street address. Example: 123 Main St
     * @bodyParam items array optional Order items
     * @bodyParam order_opertion_time_window array optional Operation time windows
     *
     * @response 200 {
     *     "order": {
     *         "id": 1,
     *         "ref_no": "ORD123",
     *         "customer": {},
     *         "orderItems": [],
     *         "orderOpertionTimeWindow": []
     *     },
     *     "success": true
     * }
     */
    public function updateOrder(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:orders,id',
            'ref_no' => 'required',
            'lat' => 'required',
            'lng' => 'required',
            'businessName' => 'required',
            'email1' => 'required',
            'mob' => 'required',
            'street' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $order = Order::find($request->id);

        if (!$order) {
            return response()->json(['error' => 'Order not found', 'success' => false], 200);
        }

        // Update or create customer
        $customerID = $this->updateOrCreateCustomer($request);

        // Update order details
        $this->updateOrderDetails($order, $request, $customerID);

        // Process order items if present
        if (!empty($request->items)) {
            $this->processOrderItemsUpdate($order, $request->items);
        } else {
            $this->processOrderItemsUpdate($order, []);
        }

        // Process operation time windows
        if (isset($request->order_operation_time_window)) {
            $this->processOperationTimeWindowsUpdate($order->id, $request->order_operation_time_window);
        }

        // Load relationships and return response
        $orders = Order::with('customer', 'orderItems.stock.stockDetail', 'orderOperationTimeWindow')
            ->where('id', $order->id)
            ->get()
            ->map(function ($order) {
                $order->position = [(float) $order->lat, (float) $order->lng];    // add the postion array in the order
                return $order;
            });

        // Add logging after updating order
        OrderLogger::log(
            orderId: $order->id,
            action: OrderAction::UPDATED->value,
            status: $order->orderStatus,
            eta: $order->deliverBy_datetime
        );

        return response()->json(['order' => $orders, 'success' => true], 200);
    }

    /**
     * Update or create customer record
     */
    /**
     * Update or create customer record with null safety checks
     *
     * @param Request $request
     * @return int
     */
    private function updateOrCreateCustomer(Request $request): int
    {
        // Initialize customer model
        $customer = empty($request->customer_id)
            ? new Customer
            : Customer::findOrNew($request->customer_id);

        // Assign values with null safety checks
        $customer->userID = $request->user_id ?? null;
        $customer->businessName = $request->businessName ?? null;
        $customer->mob = $request->mob ?? null;
        $customer->number = $request->number ?? null;
        $customer->email1 = $request->email1 ?? null;
        $customer->web = $request->web ?? null;
        $customer->fax = $request->fax ?? null;
        $customer->street = $request->street ?? null;
        $customer->postcode = $request->postcode ?? null;

        // Save and return ID
        $customer->save();
        return $customer->id;
    }

    /**
     * Update order details
     */
    private function updateOrderDetails(Order $order, Request $request, int $customerID): void
    {
        // Basic order information
        $order->ref_no = $request->ref_no ?? $order->ref_no;
        $order->warehouse_id = $request->warehouse_id ?? $order->warehouse_id;
        $order->priority = $request->priority ?? $order->priority;
        $order->vehicle_requirement_id = $request->vehicle_requirement_id ?? $order->vehicle_requirement_id;
        $order->description = $request->description ?? $order->description;
        $order->orderType = $request->orderType ?? $order->orderType;
        $order->speed_zone_id = $request->speed_zone_id ?? $order->speed_zone_id;
        $order->territory_id = $request->territory_id ?? $order->territory_id;

        // Handle notifications array/json
        $order->allowNotifications = $request->has('allowNotifications')
            ? json_encode($request->allowNotifications)
            : $order->allowNotifications;

        // Sequence and measurements
        $order->stop_sequence = $request->stop_sequence ?? $order->stop_sequence;
        $order->weight = $request->weight ?? $order->weight;
        $order->volume = $request->volume ?? $order->volume;

        // Collection status
        $order->collection = $request->has('collection')
            ? (bool)$request->collection
            : $order->collection;

        // Location coordinates
        $order->lat = $request->lat ?? $order->lat;
        $order->lng = $request->lng ?? $order->lng;

        // Duration and status flags
        $order->operation_duration = $request->operation_duration ?? $order->operation_duration;
        $order->locked = 0; // Explicitly set to 0 as per original code
        $order->error = 0; // Explicitly set to 0 as per original code

        // Customer ID
        $order->cid = $customerID; // Required field from parameter

        $order->save();
    }

    /**
     * Process order items update
     */
    private function processOrderItemsUpdate(Order $order, array $newOrderItems): void
    {
        $retainItemIds = [];

        foreach ($newOrderItems as $newItem) {
            if (isset($newItem['id'])) {
                $this->updateExistingOrderItem($newItem, $retainItemIds);
            } else {
                $this->createNewOrderItem($order->id, $newItem, $retainItemIds);
            }
        }

        // Delete removed items
        $existingItemIds = OrderItem::where('oid', $order->id)->pluck('id')->toArray();
        $itemsToDelete = array_diff($existingItemIds, $retainItemIds);
        OrderItem::whereIn('id', $itemsToDelete)->delete();
    }

    /**
     * Update existing order item
     */
    private function updateExistingOrderItem(array $itemData, array &$retainItemIds): void
    {
        $orderItem = OrderItem::find($itemData['id']);
        if (!$orderItem) return;

        // Create new stock item
        $stockItem = $this->createStockItem($itemData);

        // Create stock detail
        $this->createStockDetail($stockItem->id, $itemData);

        // Update order item
        $orderItem->update([
            'bedsName' => $itemData['bedsName'] ?? null,
            'notes' => $itemData['notes'] ?? null,
            'price' => $itemData['price'] ?? null,
            'Qty' => $itemData['Qty'] ?? null,
            'costs' => $itemData['item_total_amount'] ?? 0,
            'barcode' => $itemData['item_barcode'] ?? null,
            'item_ex_id' => $itemData['item_ex_id'] ?? null,
            'item_actual_quantity' => $itemData['item_actual_quantity'] ?? null,
            'itemid' => $stockItem->id
        ]);

        $retainItemIds[] = $orderItem->id;
    }

    /**
     * Create new order item
     */
    private function createNewOrderItem(int $orderId, array $itemData, array &$retainItemIds): void
    {
        // Create stock item
        $stockItem = $this->createStockItem($itemData);

        // Create stock detail
        $this->createStockDetail($stockItem->id, $itemData);

        // Create order item
        $orderItem = OrderItem::create([
            'oid' => $orderId,
            'bedsName' => $itemData['bedsName'] ?? null,
            'notes' => $itemData['notes'] ?? null,
            'price' => $itemData['price'] ?? null,
            'Qty' => $itemData['Qty'] ?? null,
            'costs' => $itemData['item_total_amount'] ?? 0,
            'barcode' => $itemData['item_barcode'] ?? null,
            'item_ex_id' => $itemData['item_ex_id'] ?? null,
            'itemStatus' => 'Unchecked',
            'item_actual_quantity' => $itemData['item_actual_quantity'] ?? null,
            'itemid' => $stockItem->id
        ]);

        $retainItemIds[] = $orderItem->id;
    }

    /**
     * Process operation time windows update
     */
    private function processOperationTimeWindowsUpdate(int $orderId, $timeWindows): void
    {
        foreach ($timeWindows as $operationWindow) {
            if (isset($operationWindow['id'])) {
                $this->updateExistingTimeWindow($operationWindow);
            } else {
                $this->createNewTimeWindow($orderId, $operationWindow);
            }
        }
    }

    /**
     * Update existing time window
     */
    private function updateExistingTimeWindow(array $timeWindow): void
    {
        $startTime = $this->formatTimeOrDefault($timeWindow['start_time'] ?? '', '06:30');
        $endTime = $this->formatTimeOrDefault($timeWindow['end_time'] ?? '', '20:00');
        OrderOperationTimeWindow::where('id', $timeWindow['id'])
            ->update([
                'date' => $timeWindow['date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'updated_at' => now()
            ]);
    }

    /**
     * Create new time window
     */
    private function createNewTimeWindow(int $orderId, array $timeWindow): void
    {
        $startTime = $this->formatTimeOrDefault($timeWindow['start_time'] ?? '', '06:30');
        $endTime = $this->formatTimeOrDefault($timeWindow['end_time'] ?? '', '20:00');
        OrderOperationTimeWindow::create([
            'order_id' => $orderId,
            'date' => $timeWindow['date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Delete order(s)
     *
     * Delete order(s) and related data
     *
     * @authenticated
     *
     * @bodyParam id integer|array required Order ID or array of order IDs. Example: [1,2]
     *
     * @response 200 {
     *     "message": "Orders deleted successfully",
     *     "deleted_orders": [1, 2]
     * }
     *
     * @response 400 {
     *     "errors": {
     *         "id": ["The id field is required."]
     *     }
     * }
     *
     * @response 404 {
     *     "message": "No orders found with these IDs"
     * }
     */
    public function deleteOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {


            // Convert single ID to array if necessary
            $orderIds = is_array($request->id) ? $request->id : [$request->id];

            // Log deletion for each order
            foreach ($orderIds as $orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    OrderLogger::log(
                        orderId: $orderId,
                        action: OrderAction::CANCELLED->value,
                        status: 'Deleted',
                        eta: null
                    );
                }
            }

            // Check if orders exist
            $orders = Order::whereIn('id', $orderIds)->get();
            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No orders found with these IDs', 'success' => false], 200);
            }

            // Reuse existing method to delete orders and related data
            $this->deleteOrderAndRelatedData($orderIds);



            return response()->json([
                'message' => 'Orders deleted successfully',
                'deleted_orders' => $orderIds
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'An error occurred while deleting orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *
     * Bulk Delete Orders
     *
     * Bulk delete multiple orders
     *
     * @authenticated
     *
     * @bodyParam order_ids array required Array of order IDs to delete. Example: [1,2,3]
     *
     * @response 200 {
     *     "message": "Orders deleted successfully",
     *     "deleted_orders": [1, 2, 3],
     *     "logs": []
     * }
     *
     * @response 400 {
     *     "message": "Invalid input or no order IDs provided"
     * }
     *
     * @response 404 {
     *     "message": "No orders found with these IDs"
     * }
     *
     * @response 500 {
     *     "message": "Something went wrong",
     *     "error": "Error message",
     *     "logs": []
     * }
     */
    public function bulkOrderDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input or no order IDs provided'], 400);
        }

        $orderIds = $request->input('order_ids');

        try {

            DB::enableQueryLog();

            // Log deletion for each order
            foreach ($orderIds as $orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    OrderLogger::log(
                        orderId: $orderId,
                        action: OrderAction::CANCELLED->value,
                        status: 'Bulk Deleted',
                        eta: null
                    );
                }
            }

            // Check if orders exist
            $orders = Order::whereIn('id', $orderIds)->get();
            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No orders found with these IDs', 'success' => false], 200);
            }

            // Reuse existing method to delete orders and related data
            $this->deleteOrderAndRelatedData($orderIds);



            return response()->json([
                'message' => 'Orders deleted successfully',
                'deleted_orders' => $orderIds,
                'logs' => DB::getQueryLog()
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
                'logs' => DB::getQueryLog()
            ], 500);
        }
    }

    /**
     * Delete order items
     *
     * @authenticated
     *
     * @bodyParam ids array required Array of order item IDs to delete. Example: [1,2,3]
     *
     * @response 200 {
     *     "message": "Order items deleted successfully",
     *     "deleted_items": [{
     *         "id": 1,
     *         "oid": 100,
     *         "itemid": 50,
     *         "bedsName": "Item 1",
     *         "itemStatus": "Unchecked"
     *     }]
     * }
     *
     * @response 404 {
     *     "message": "No order items found with these IDs"
     * }
     *
     * @response 500 {
     *     "message": "An error occurred while deleting order items",
     *     "error": "Error message"
     * }
     */
    public function deleteOrderItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|exists:order_items,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input or no order item IDs provided',
                'errors' => $validator->errors()
            ], 400);
        }

        try {


            $itemIds = $request->input('ids');

            // Check if order items exist
            $orderItems = OrderItem::whereIn('id', $itemIds)->get();
            if ($orderItems->isEmpty()) {
                return response()->json(['message' => 'No order items found with these IDs', 'success' => false], 200);
            }

            // Delete the order items
            OrderItem::whereIn('id', $itemIds)->delete();



            return response()->json([
                'message' => 'Order items deleted successfully',
                'deleted_items' => $orderItems
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'An error occurred while deleting order items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Order Item
     *
     * Update order item and related stock information
     *
     * @authenticated
     *
     * @bodyParam item_id integer required The ID of the order item to update. Example: 1
     * @bodyParam bedsName string required The name of the item. Example: "King Size Bed"
     * @bodyParam notes string optional Notes about the item. Example: "Special handling required"
     * @bodyParam price decimal required The price of the item. Example: 199.99
     * @bodyParam Qty integer required The quantity of the item. Example: 2
     * @bodyParam item_total_amount decimal required The total amount for the item. Example: 399.98
     * @bodyParam item_barcode string optional The barcode of the item. Example: "BAR123"
     * @bodyParam item_ex_id string optional External ID of the item. Example: "EXT456"
     * @bodyParam length decimal optional Length of the item in meters. Example: 2.0
     * @bodyParam width decimal optional Width of the item in meters. Example: 1.8
     * @bodyParam height decimal optional Height of the item in meters. Example: 0.5
     * @bodyParam weight decimal optional Weight of the item in kg. Example: 45.5
     *
     * @response 200 {
     *     "orderItem": {
     *         "id": 1,
     *         "bedsName": "King Size Bed",
     *         "price": 199.99,
     *         "Qty": 2
     *     },
     *     "success": true
     * }
     *
     * @response 404 {
     *     "message": "Order item not found"
     * }
     */
    public function updateOrderItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:order_items,id',
            'bedsName' => 'required|string',
            'price' => 'required|numeric',
            'Qty' => 'required|integer|min:1',
            'item_total_amount' => 'required|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {


            // Find and update order item
            $orderItem = OrderItem::findOrFail($request->item_id);
            $this->updateOrderItemDetails($orderItem, $request);

            // Create new stock item
            $stockItem = $this->createStockItem([
                'bedsName' => $request->bedsName,
                'notes' => $request->notes,
                'Qty' => $request->Qty
            ]);

            // Create stock detail using existing method
            $this->createStockDetail($stockItem->id, [
                'weight' => $request->weight,
                'length' => $request->length,
                'width' => $request->width,
                'height' => $request->height
            ]);



            return response()->json(['orderItem' => $orderItem, 'success' => true], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'An error occurred while updating the order item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order item details
     */
    private function updateOrderItemDetails(OrderItems $orderItem, Request $request): void
    {
        $orderItem->bedsName = $request->bedsName;
        $orderItem->notes = $request->notes;
        $orderItem->price = $request->price;
        $orderItem->Qty = $request->Qty;
        $orderItem->costs = $request->item_total_amount;
        $orderItem->barcode = $request->item_barcode ?? null;
        $orderItem->item_ex_id = $request->item_ex_id;
        $orderItem->save();
    }

    /**
     * Clone Order
     *
     * Clone an existing order with all its related items and time windows
     *
     * @authenticated
     *
     * @bodyParam order_id integer required The ID of the order to clone. Example: 1
     * @bodyParam street string required Street address for the new order. Example: "123 Main St"
     * @bodyParam mob string required Mobile number for the new order. Example: "+1234567890"
     *
     * @response 200 {
     *     "order": {
     *         "id": 2,
     *         "ref_no": "ORD123-CLONE",
     *         "parent_order_id": 1,
     *         "warehouse_id": 1,
     *         "orderStatus": "Allocated"
     *     },
     *     "success": true
     * }
     *
     * @response 422 {
     *     "errors": {
     *         "order_id": ["The order id field is required."]
     *     }
     * }
     *
     * @response 404 {
     *     "errors": "Order not found"
     * }
     */
    public function cloneOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'street' => 'required|string',
            'mob' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {


            $originalOrder = Order::with(['orderItems', 'orderOperationTimeWindow'])
                ->findOrFail($request->order_id);

            // Clone the order
            $newOrder = $this->cloneOrderRecord($originalOrder, $request->order_id);

            // Clone related records
            $this->cloneOrderItems($originalOrder->orderItems, $newOrder->id);
            $this->cloneTimeWindows($originalOrder->orderOperationTimeWindow, $newOrder->id);



            return response()->json([
                'order' => $newOrder->fresh(['orderItems', 'orderOperationTimeWindow']),
                'success' => true
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json(['errors' => 'Order not found', 'success' => false], 200);
        } catch (\Exception $e) {

            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /**
     * Clone order record
     */
    private function cloneOrderRecord(Order $originalOrder, int $parentOrderId): Order
    {
        $newOrder = $originalOrder->replicate();
        $newOrder->parent_order_id = $parentOrderId;
        $newOrder->save();
        return $newOrder;
    }

    /**
     * Clone order items
     */
    private function cloneOrderItems($originalItems, int $newOrderId): void
    {
        foreach ($originalItems as $originalItem) {
            $newItem = $originalItem->replicate();
            $newItem->oid = $newOrderId;
            $newItem->save();
        }
    }

    /**
     * Clone time windows
     */
    private function cloneTimeWindows($originalTimeWindows, int $newOrderId): void
    {
        foreach ($originalTimeWindows as $originalWindow) {
            $newWindow = $originalWindow->replicate();
            $newWindow->order_id = $newOrderId;
            $newWindow->save();
        }
    }

    /**
     * Lock or Unlock Orders
     *
     * Toggles the lock status of multiple orders and records the action in history.
     *
     * @authenticated
     *
     * @bodyParam orderIds array required Array of order IDs to lock/unlock. Example: [1,2,3]
     * @bodyParam value integer required Lock status (1 for unlock, 0 for lock). Example: 1
     *
     * @response 200 {
     *     "data": [
     *         {
     *             "id": 1,
     *             "ref_no": "ORD123",
     *             "locked": 1,
     *             "updated_at": "2024-01-01T12:00:00.000000Z"
     *         }
     *     ]
     * }
     *
     * @response 422 {
     *     "message": "The orderIds field is required"
     * }
     */
    public function lockUnlock(Request $request)
    {
        $request->validate([
            'orderIds' => 'required|array',
            'orderIds.*' => 'exists:orders,id',
            'value' => 'required|integer|min:0|max:2'
        ]);

        $requestUser = $this->getRequestUser($request);
        $orders = $this->updateOrderLockStatus($request->orderIds, $request->value, $requestUser);

        return response()->json($orders, 200);
    }

    private function getRequestUser(Request $request): User
    {
        return auth()->user();
    }

    private function updateOrderLockStatus(array $orderIds, int $value, User $requestUser): array
    {
        $orders = [];

        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);

            if ($order) {
                $order->locked = $value;
                $order->save();
                $orders[] = $order;

                // Add logging after status update
                if ($value == '2') {
                    OrderLogger::log(
                        orderId: $orderId,
                        action: OrderAction::STATUS_CHANGED->value,
                        status: 'Send to driver',
                        eta: $order->deliverBy_datetime
                    );
                } else if ($value == '0') {
                    OrderLogger::log(
                        orderId: $orderId,
                        action: OrderAction::STATUS_CHANGED->value,
                        status: 'Locked',
                        eta: $order->deliverBy_datetime
                    );
                } else {
                    OrderLogger::log(
                        orderId: $orderId,
                        action: OrderAction::STATUS_CHANGED->value,
                        status: 'Unlocked',
                        eta: $order->deliverBy_datetime
                    );
                }


                //also lock the child orders
                $order->childOrders()->update(['locked' => $value]);
            }
        }

        if ($value == '2') {
            FcmFunctionHelper::sendDetailsToDriverRouteAssigned($orderIds, $value);
        }

        return $orders;
    }

    /**
     * Unallocate Orders
     *
     * Unallocates single or multiple orders, handling parent-child relationships.
     *
     * @authenticated
     *
     * @bodyParam parent_order_id integer optional The ID of the parent order to unallocate. Example: 1
     * @bodyParam first_child_order_id integer optional The ID of the first child order when unallocating parent. Example: 2
     * @bodyParam orderIds array optional Array of order IDs to unallocate. Example: [1,2,3]
     *
     * @response 200 {
     *     "message": "Orders unallocated successfully.",
     *     "status": 200
     * }
     *
     * @response 404 {
     *     "message": "Order not found",
     *     "status": 404
     * }
     */
    public function unallocateOrders(Request $request)
    {

        try {


            if ($request->has('removeRun') && $request->removeRun) {
                $this->handleRemoveRun($request->orderIds);
            } else {
                if ($request->has('parent_order_id')) {
                    $this->handleParentOrderUnallocation(
                        $request->parent_order_id,
                        $request->first_child_order_id
                    );

                    // Update the  sequence of the orders ...
                    $this->updateNumOFOrders($request->first_child_order_id);
                } else {
                    $this->handleMultipleOrdersUnallocation(
                        $request->orderIds,
                        $this->getRequestUser($request)
                    );
                }
            }


            return response()->json([
                'message' => 'Orders unallocated successfully.',
                'status' => 200
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500
            ]);
        }
    }

    /**
     * Update the num value of the disp table
     * when a parent order is removed form the run .
    */
    private function updateNumOFOrders($parentOrderId)
    {
        $orders = Order::join('disp', 'disp.oid', 'orders.id')
        ->where(function ($query) use ($parentOrderId) {
            $query->where('orders.id', $parentOrderId)
                  ->orWhere('orders.max_parent_order_id', $parentOrderId);
        })
        ->orderBy('disp.num', 'Asc')
        ->get();

        $index = 0;
        foreach ($orders as $order) {
            Disp::where('oid', $order->id)
                ->update(['num' => $index]);
            $index++;
        }
    }

    private function handleRemoveRun($orderId): void
    {
        $parentOrderId = Order::where('id', $orderId)->first()->max_parent_order_id;
        if (!$parentOrderId) {
            $parentOrderId = $orderId;
            $parentOrder = Order::find($orderId);
        } else {
            $parentOrder = Order::find($parentOrderId);
        }



        foreach ($parentOrder->childOrders as $childOrder) {
            $this->unallocateOrder($childOrder);
            $this->removeDispatch($childOrder->id);
        }

        $this->removeDispatch($parentOrderId);
        $this->unallocateOrder($parentOrder);

        if ($parentOrder->locked == 2 && $parentOrder->max_parent_order_id == null) {
            $value = 2;
            FcmFunctionHelper::sendDetailsToDriverRouteUnassigned($parentOrder->id, $value);
        }
    }

    private function handleParentOrderUnallocation(int $parentOrderId, int $firstChildOrderId): void
    {
        $parentOrder = Order::findOrFail($parentOrderId);
        $this->unallocateParentOrder($parentOrder);

        $firstChildOrder = Order::findOrFail($firstChildOrderId);
        if ($firstChildOrder) {
            $this->reassignChildOrders($firstChildOrder, $parentOrderId);
        }
    }

    private function unallocateParentOrder(Order $parentOrder): void
    {
        $parentOrder->update([
            'max_parent_order_id' => null,
            'orderStatus' => 'Allocated',
            'locked' => 1
        ]);

        $this->removeDispatch($parentOrder->id);

        // Add logging after unallocation
        OrderLogger::log(
            orderId: $parentOrder->id,
            action: OrderAction::STATUS_CHANGED->value,
            status: 'Allocated',
            eta: $parentOrder->deliverBy_datetime
        );
    }

    private function reassignChildOrders(Order $firstChildOrder, int $parentOrderId): void
    {
        $firstChildOrder->max_parent_order_id = null;
        $firstChildOrder->save();

        Order::where('max_parent_order_id', $parentOrderId)
            ->where('id', '!=', $firstChildOrder->id)
            ->update(['max_parent_order_id' => $firstChildOrder->id]);
    }

    private function handleMultipleOrdersUnallocation(array $orderIds, User $requestUser)
    {

        $allOrders = Order::whereIn('id', $orderIds)->get();

        $parentOrders = $allOrders->filter(function ($allOrder) {
            return is_null($allOrder->max_parent_order_id);
        });

        $orderUnassigned = 0;

        if ($parentOrders->isNotEmpty()) {
            $firstParentOrder = $parentOrders->first(); // Safely get the first element

            if ($firstParentOrder->locked == 2) {
                $value = $firstParentOrder->locked;
                $orderId = $firstParentOrder->id;

                FcmFunctionHelper::sendDetailsToDriverRouteUnassigned($orderId, $value);
                $orderUnassigned = 1;
            }
        }

        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);
            if ($order) {
                if ($order->locked == 2 &&   $orderUnassigned == 0) {
                    $value = 2;
                    FcmFunctionHelper::sendDetailsToDriverOrderUnassigned($orderId, $value);
                }
                $this->unallocateOrder($order);
                $this->removeDispatch($orderId);

                OrderLogger::log(
                    orderId: $order->id,
                    action: OrderAction::STATUS_CHANGED->value,
                    status: 'Allocated',
                    eta: null
                );
            }
        }
    }

    private function unallocateOrder(Order $order): void
    {
        $order->update([
            'max_parent_order_id' => null,
            'orderStatus' => 'Allocated',
            'locked' => 1
        ]);

        // Add logging after unallocation
        OrderLogger::log(
            orderId: $order->id,
            action: OrderAction::STATUS_CHANGED->value,
            status: 'Allocated',
            eta: $order->deliverBy_datetime
        );
    }

    private function removeDispatch(int $orderId): void
    {
        $dispatch = Disp::where('oid', $orderId)->first();
        if ($dispatch) {
            $dispatch->delete();
        }
    }

    /**
     * Merge Orders
     *
     * Merges orders based on different scenarios like parent-child relationships and routing status.
     *
     * @authenticated
     *
     * @bodyParam order_A integer required Parent order ID. Example: 1
     * @bodyParam order_B integer required New order ID to merge. Example: 2
     * @bodyParam targetId integer required Target order ID. Example: 3
     * @bodyParam index integer required Position index for the merge. Example: 0
     *
     * @response 200 {
     *     "message": "Merge completed successfully"
     * }
     *
     * @response 404 {
     *     "error": "Order not found"
     * }
     */
    public function mergeOrdersToOrder(Request $request)
    {
        //Order A is the parent order
        //Order B is the new order
        //Target Id is the target order on which the merge will be performed
        //Index is the position index for the merge

        //order_B will be an array of order ids.
        $request->validate([
            'order_A' => 'required|exists:orders,id',
            'order_B' => 'required|array',
            'order_B.*' => 'exists:orders,id',
            'targetId' => 'required|exists:orders,id',
            'index' => 'required|integer|min:0'
        ]);

        try {


            $currentIndex = $request->index;
            $results = [];

            $orderMergeService = new OrderMergeService();

            foreach ($request->order_B as $arrayIndex => $orderId) {
                $newOrder = Order::with('disp')->find($orderId);

                //for the first order in the array the target order will be $request->targetId
                // for all the next orders the target order will be the previous order in the array
                $currentTargetId = ($arrayIndex === 0) ? $request->targetId : $request->order_B[$arrayIndex - 1];
                $targetOrder = Order::with('disp')->find($currentTargetId);

                if (!$newOrder || !$targetOrder) {

                    return response()->json(['error' => 'Order not found', 'success' => false], 200);
                }

                // Check for no-op condition - exact match with original
                if (
                    $orderId == $currentTargetId &&
                    $currentTargetId == $request->order_A &&
                    $currentIndex == 0
                ) {
                    $results[] = ['orderId' => $orderId, 'status' => 'skipped', 'message' => 'No operation specified'];
                    continue;
                }

                $results[] = $orderMergeService->mergeOrders($currentIndex, $newOrder, $currentTargetId, $targetOrder, $request->order_A, $orderId);

                $currentIndex++;
            }


            return response()->json([
                'message' => 'Merge operations completed',
                'results' => $results,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage(), 'success' => false], 500);
        }
    }


    /**
     * Resequence Orders
     *
     * Resequences orders based on optimal distance calculations using Google Maps API.
     *
     * @authenticated
     *
     * @bodyParam order_id integer required The ID of the order to resequence. Example: 1
     *
     * @response 200 {
     *     "shortestDistances": [
     *         {
     *             "from": {"id": 1, "lat": 51.5074, "lng": -0.1278},
     *             "to": {"id": 2, "lat": 51.5074, "lng": -0.1278},
     *             "distance": 1.5,
     *             "index": 1
     *         }
     *     ]
     * }
     *
     * @response 404 {
     *     "error": "Order not found"
     * }
     */
    public function reSequence(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $isDispatched = Disp::where('oid', $request->order_id)->exists();
        if (!$isDispatched) {
            return response()->json(['error' => 'Order not dispatched', 'success' => false], 200);
        }

        $parentOrder = $this->getParentOrderWithChildren($request->order_id);
        if (!$parentOrder) {
            return response()->json(['error' => 'Parent order not found', 'success' => false], 200);
        }

        $mergedArray = $this->getMergedOrderArray($parentOrder);
        $warehouse = $this->getWarehouseLocation();

        return response()->json(
            $this->calculateShortestDistances($mergedArray, $warehouse)
        );
    }

    private function getParentOrderWithChildren(int $orderId): ?Order
    {
        return Order::with(['childOrders' => function ($query) {
            $query->join('disp', 'id', '=', 'disp.oid')
                ->orderBy('num', 'ASC');
        }])->find($orderId);
    }

    private function getMergedOrderArray(Order $parentOrder): array
    {
        $orderRun = $parentOrder->toArray();
        $childOrders = $orderRun['child_orders'];
        unset($orderRun['child_orders']);

        return array_merge([$orderRun], $childOrders);
    }

    private function getWarehouseLocation(): array
    {
        $warehouse = Warehouse::find(1);
        return $this->getWarehouseCoordinates($warehouse);
    }

    private function calculateShortestDistances(array $orders, array $warehouseLocation): array
    {
        $shortestDistances = [];
        $usedIndexes = [];
        $numPoints = count($orders);
        $currentIndex = 0;
        $indexCounter = 1;

        while (count($usedIndexes) < $numPoints) {
            $currentPoint = $orders[$currentIndex];
            $nextPoint = $this->findClosestPoint(
                $currentPoint,
                $orders,
                $usedIndexes,
                $currentIndex
            );

            if ($nextPoint) {
                $shortestDistances[] = $nextPoint['data'];
                $this->updateDispatchSequence($currentPoint['id'], $indexCounter);
                $usedIndexes[] = $currentIndex;
                $currentIndex = $nextPoint['index'];
                $indexCounter++;
            } else {
                break;
            }
        }

        return $shortestDistances;
    }

    private function findClosestPoint(
        array $currentPoint,
        array $points,
        array $usedIndexes,
        int $currentIndex
    ): ?array {
        $minDistance = PHP_INT_MAX;
        $closestPoint = null;
        $closestIndex = -1;

        foreach ($points as $index => $point) {
            if ($currentIndex !== $index && !in_array($index, $usedIndexes)) {
                $distance = $this->calculateDistance(
                    $currentPoint,
                    $point
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestPoint = $point;
                    $closestIndex = $index;
                }
            }
        }

        return $closestPoint ? [
            'data' => [
                'from' => $currentPoint,
                'to' => $closestPoint,
                'distance' => $minDistance,
                'index' => count($usedIndexes) + 1
            ],
            'index' => $closestIndex
        ] : null;
    }

    private function calculateDistance(array $origin, array $destination): float
    {
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/directions/json?origin=%f,%f&destination=%f,%f&key=%s',
            $origin['lat'],
            $origin['lng'],
            $destination['lat'],
            $destination['lng'],
            config('services.google.maps_api_key')
        );

        $response = Http::get($url);
        $data = $response->json();

        return isset($data['routes'][0]['legs'][0]['distance']['value'])
            ? $data['routes'][0]['legs'][0]['distance']['value'] / 1000
            : PHP_FLOAT_MAX;
    }

    private function updateDispatchSequence(int $orderId, int $sequence): void
    {
        Disp::where('oid', $orderId)->update(['num' => $sequence]);
    }

    /**
     * Resequence Timeline Orders
     *
     * Resequences orders in a timeline based on optimal distance calculations using Google Maps API.
     *
     * @authenticated
     *
     * @bodyParam all_order array required Array of orders to resequence. Example: [{"id": 1, "position": [51.5074, -0.1278]}]
     * @bodyParam warehouse_id integer required The ID of the warehouse. Example: 1
     *
     * @response 200 {
     *     "orders": [
     *         {
     *             "id": 1,
     *             "position": [51.5074, -0.1278],
     *             "total_distance": 1.5,
     *             "total_duration": 300
     *         }
     *     ],
     *     "processedOrders": [],
     *     "distances": [1.5],
     *     "durations": [300]
     * }
     */
    public function reSequenceTimeline(Request $request)
    {
        $request->validate([
            'all_order' => 'required|array',
            'all_order.*.id' => 'required|exists:orders,id',
            'all_order.*.position' => 'required|array|size:2',
            'warehouse_id' => 'required|exists:warehouses,id'
        ]);

        try {


            $orderIds = array_column($request->all_order, 'id');
            $this->resetParentOrders($orderIds);

            $warehouse = $this->getWarehouse($request->warehouse_id);
            $origin = $this->getWarehouseCoordinates($warehouse);

            // Calculate distances and sort by nearest first
            $sortedOrders = $this->calculateAndSortOrders(
                $request->all_order,
                $origin
            );

            $dispatch = Disp::whereIn('oid', $orderIds)->where('van', '!=', null)->first();
            $dispatchVehicle = $dispatch->van;
            $dispatchRoute = $dispatch->id_route;

            // Process the sorted sequence
            $result = $this->processOptimizedSequence($sortedOrders, $origin, $dispatchVehicle, $dispatchRoute);


            return response()->json($result);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function resetParentOrders(array $orderIds): void
    {
        Order::whereIn('id', $orderIds)->update(['max_parent_order_id' => null]);
    }

    private function getWarehouse(int $warehouseId): Warehouse
    {
        return Warehouse::findOrFail($warehouseId);
    }

    private function getWarehouseCoordinates(Warehouse $warehouse): array
    {
        return [
            'lat' => floatval($warehouse->latitude),
            'lng' => floatval($warehouse->longitude)
        ];
    }

    private function calculateAndSortOrders(array $orders, array $origin): array
    {
        $distanceHelper = new MapsHelper();
        $remainingOrders = $orders;
        $sortedOrders = [];
        $currentPosition = $origin;

        while (!empty($remainingOrders)) {
            $nearestOrderKey = null;
            $minDistance = INF;

            foreach ($remainingOrders as $key => $order) {
                $orderLat = (float)$order['position'][0];
                $orderLng = (float)$order['position'][1];

                $distance = $distanceHelper->calculateHaversineDistance(
                    $currentPosition['lat'],
                    $currentPosition['lng'],
                    $orderLat,
                    $orderLng
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestOrderKey = $key;
                }
            }

            if ($nearestOrderKey !== null) {
                $nearestOrder = $remainingOrders[$nearestOrderKey];
                $sortedOrders[] = [
                    'id' => $nearestOrder['id'],
                    'distance' => $minDistance,
                    'position' => $nearestOrder['position']
                ];

                // Update current position to the selected order's location
                $currentPosition = [
                    'lat' => (float)$nearestOrder['position'][0],
                    'lng' => (float)$nearestOrder['position'][1]
                ];

                unset($remainingOrders[$nearestOrderKey]);
            }
        }

        return $sortedOrders;
    }

    private function processOptimizedSequence(array $sortedOrders, array $origin, $dispVehicle, $dispRoute): array
    {
        $sequence = [];
        $previousOrderId = null;
        $firstOrderId = null;

        foreach ($sortedOrders as $index => $order) {
            $currentOrder = Order::findOrFail($order['id']);

            // Set parent relationship
            if ($previousOrderId) {
                $currentOrder->parent_order_id = $previousOrderId;
            }

            // Track first order in sequence
            if ($index === 0) {
                $firstOrderId = $currentOrder->id;
                $currentOrder->max_parent_order_id = null;
            } else {
                $currentOrder->max_parent_order_id = $firstOrderId;
            }

            $currentOrder->save();

            // Prepare disp record
            Disp::updateOrCreate(
                ['oid' => $currentOrder->id],
                [
                    'num' => $index === 0 ? 0 : $index, // Sequence number
                    'van' => $dispVehicle,
                    'id_route' => $dispRoute
                ]
            );

            $sequence[] = [
                'order_id' => $currentOrder->id,
                'position' => $index,
                'distance_from_warehouse' => $order['distance'],
                'distance_from_previous' => $index > 0
                    ? $this->calculateOrderDistance($previousOrderId, $currentOrder->id)
                    : 0
            ];

            $previousOrderId = $currentOrder->id;
        }

        $distanceHelper = new MapsHelper();
        $distanceHelper->calculateDistanceAndTime($firstOrderId);

        return [
            'warehouse' => $origin,
            'total_orders' => count($sortedOrders),
            'total_distance' => array_sum(array_column($sortedOrders, 'distance')),
            'sequence' => $sequence
        ];
    }

    private function calculateOrderDistance($fromOrderId, $toOrderId): float
    {
        $distanceHelper = new MapsHelper();
        $from = Order::findOrFail($fromOrderId, ['lat', 'lng']);
        $to = Order::findOrFail($toOrderId, ['lat', 'lng']);

        return $distanceHelper->calculateHaversineDistance(
            (float)$from->lat,
            (float)$from->lng,
            (float)$to->lat,
            (float)$to->lng
        );
    }

    /**
     * Reverse Run Sequence
     *
     * Reverses the sequence of orders in a run, updating parent-child relationships and dispatch numbers.
     *
     * @authenticated
     *
     * @bodyParam order_id integer required The ID of the parent order to reverse. Example: 1
     *
     * @response 200 {
     *     "message": "Merge completed successfully"
     * }
     *
     * @response 404 {
     *     "error": "not action are taken"
     * }
     */
    public function reverseRunSequence(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        try {


            $parentOrder = $this->getParentOrderWithChildren($request->order_id);
            if (!$this->isValidReverseOperation($parentOrder)) {
                return response()->json(['error' => 'not action are taken', 'success' => false], 200);
            }

            $lastChildOrder = collect($parentOrder->childOrders)->last();
            $indexOfLastOrder = count($parentOrder->childOrders);

            $this->updateOrderRelationships($parentOrder, $lastChildOrder);
            $this->updateDispatchSequences($parentOrder, $lastChildOrder);

            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($lastChildOrder->id);


            return response()->json(['message' => 'Merge completed successfully'], 200);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function isValidReverseOperation(?Order $parentOrder): bool
    {
        return $parentOrder &&
            $parentOrder->childOrders &&
            !empty($parentOrder->childOrders);
    }

    private function updateOrderRelationships(Order $parentOrder, Order $lastChildOrder): void
    {
        DB::table('orders')
            ->where('id', $lastChildOrder->id)
            ->update(['max_parent_order_id' => null]);

        DB::table('orders')
            ->where('max_parent_order_id', $parentOrder->id)
            ->update(['max_parent_order_id' => $lastChildOrder->id]);

        $parentOrder->max_parent_order_id = $lastChildOrder->id;
        $parentOrder->save();
    }

    private function updateDispatchSequences(Order $parentOrder, Order $lastChildOrder): void
    {
        $reversedChildOrders = collect($parentOrder->childOrders)
            ->reverse()
            ->values()
            ->toArray();

        foreach ($reversedChildOrders as $index => $childOrder) {
            Disp::where('oid', $childOrder['id'])
                ->update(['num' => $index]);
        }

        Disp::where('oid', $parentOrder->id)
            ->update(['num' => count($reversedChildOrders)]);
    }

    /**
     * Store Calculated Time Of All Orders
     *
     * Updates time-related metrics, status logs, and violations for orders and their child orders.
     *
     * @authenticated
     *
     * @bodyParam disp array[] required Array of dispatch data.
     * @bodyParam disp[].id integer required Order ID. Example: 1
     * @bodyParam disp[].loadingTime datetime required Loading time. Example: "2024-07-04 09:00:00"
     * @bodyParam disp[].startTime datetime required Start time. Example: "2024-07-04 09:30:00"
     * @bodyParam disp[].waitingTime datetime required Waiting time. Example: "2024-07-04 09:45:00"
     * @bodyParam disp[].workStartTime datetime required Work start time. Example: "2024-07-04 10:00:00"
     * @bodyParam disp[].returnStartTime datetime required Return start time. Example: "2024-07-04 16:00:00"
     * @bodyParam disp[].returnWareHouseTime datetime required Return warehouse time. Example: "2024-07-04 17:00:00"
     * @bodyParam disp[].arriveTime datetime required Arrive time. Example: "2024-07-04 09:45:00"
     * @bodyParam disp[].operationEndTime datetime required Operation end time. Example: "2024-07-04 15:45:00"
     * @bodyParam disp[].runDuration decimal required Run duration in minutes. Example: 480
     * @bodyParam disp[].runTotalDrivingTime decimal required Total driving time in minutes. Example: 240
     * @bodyParam disp[].timeViolation boolean required Has time violation. Example: false
     * @bodyParam disp[].weightExceeded boolean required Has weight exceeded. Example: false
     * @bodyParam disp[].howMuchWeightOver decimal required Weight over limit. Example: 0
     * @bodyParam disp[].volumeExceeded boolean required Has volume exceeded. Example: false
     * @bodyParam disp[].howMuchVolumeOver decimal required Volume over limit. Example: 0
     * @bodyParam disp[].child_orders array optional Array of child orders with same structure as parent.
     *
     * @response 200 {
     *     "da": [1, 2, 3],
     *     "data": [{
     *         "disp": [{
     *             "id": 1,
     *             "loadingTime": "2024-07-04 09:00:00",
     *             "child_orders": []
     *         }]
     *     }],
     *     "success": true
     * }
     */
    public function storeCalculatedTimeOfAllOders(Request $request)
    {
        try {

            $collectedData = $this->filterDispatchData($request->json()->all());
            $processedOrderIds = $this->processOrdersForCalculation($collectedData);

            return response()->json([
                'da' => $processedOrderIds,
                'data' => $collectedData,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    private function filterDispatchData(array $data)
    {
        return collect($data)->filter(function ($item) {
            return !empty($item['disp']);
        })->values();
    }

    private function processOrdersForCalculation($data): array
    {
        $processedOrderIds = [];

        $data->each(function ($item) use (&$processedOrderIds) {
            collect($item['disp'])->each(function ($dispatch) use (&$processedOrderIds) {
                $this->updateOrderData($dispatch);

                if (!empty($dispatch['child_orders'])) {
                    $this->processChildOrders($dispatch['child_orders'], $processedOrderIds);
                }
            });
        });

        return $processedOrderIds;
    }

    private function updateOrderData(array $orderData): void
    {
        $order = Order::find($orderData['id']);
        if (!$order) return;

        $oldEta = $order->eta;

        $order->update([
            'returnDuration' => $orderData['runDuration'] ?? null,
            'run_total_driving_time' => $orderData['runTotalDrivingTime'] ?? null,
            'loadingTime' => $orderData['loadingTime'] ?? null,
            'unLoadingTime' => $orderData['unLoadingTime'] ?? null,
            'startTime' => $orderData['startTime'] ?? null,
            'waitingTime' => $orderData['waitingTime'] ?? null,
            'workStartTime' => $orderData['workStartTime'] ?? null,
            'returnStartTime' => $orderData['returnStartTime'] ?? null,
            'returnWareHouseTime' => $orderData['returnWareHouseTime'] ?? null,
            'arriveTime' => $orderData['arriveTime'] ?? null,
            'operationEndTime' => $orderData['operationEndTime'] ?? null,
            'timeViolation' => $orderData['timeViolation'] ?? null,
            'weightExceeded' => $orderData['weightExceeded'] ?? null,
            'howMuchWeightOver' => $orderData['howMuchWeightOver'] ?? null,
            'volumeExceeded' => $orderData['volumeExceeded'] ?? null,
            'howMuchVolumeOver' => $orderData['howMuchVolumeOver'] ?? null
        ]);

        // Log if ETA changed
        if ($oldEta !== ($orderData['eta'] ?? null)) {
            OrderLogger::log(
                orderId: $order->id,
                action: OrderAction::ETA_CHANGED->value,
                status: $order->orderStatus,
                eta: $orderData['eta'] ?? null
            );
        }
    }

    private function processChildOrders(array $childOrders, array &$processedOrderIds): void
    {
        collect($childOrders)->each(function ($childOrder) use (&$processedOrderIds) {
            $processedOrderIds[] = $childOrder['id'];
            $this->updateOrderData($childOrder);
        });
    }

    /**
     * Search Orders
     *
     * Search orders by ID.
     *
     * @group Order Management
     *
     * @queryParam id integer Order ID to search for. Example: 12345
     * @queryParam page integer Page number for pagination. Example: 1
     *
     * @response {
     *   "current_page": 1,
     *   "data": [{
     *     "id": 12345,
     *     "cid": 1,
     *     "ref_no": "ORD-12345",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }],
     *   "first_page_url": "http://example.com/api/orders/search?page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "http://example.com/api/orders/search?page=1",
     *   "links": [],
     *   "next_page_url": null,
     *   "path": "http://example.com/api/orders/search",
     *   "per_page": 20,
     *   "prev_page_url": null,
     *   "to": 1,
     *   "total": 1
     * }
     *
     * @response 404 {
     *   "message": "No orders found"
     * }
     */
    public function searchOrders(Request $request)
    {
        try {
            $searchInput = $request->query('id');

            $orders = Order::query()
                ->when($searchInput, function ($query) use ($searchInput) {
                    $query->where('id', $searchInput);
                })
                ->paginate(20);

            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'No orders found',
                    'success' => false
                ], 200);
            }

            return response()->json($orders, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filter Orders
     *
     * Filter and paginate orders based on multiple criteria including distribution center,
     * order status, vehicle requirements, priority, client, and creation time range.
     *
     * @group Order Management
     *
     * @queryParam distribution_centre array Array of warehouse IDs to filter by. Example: [1, 2]
     * @queryParam orderStatus array Array of order statuses to filter by. Example: ["pending", "completed"]
     * @queryParam vehicleReq string Vehicle requirement type. Example: "van"
     * @queryParam priority string Priority level of orders. Example: "high"
     * @queryParam client string Client business name to filter by. Example: "ACME Corp"
     * @queryParam creation_time_from string Start date for filtering (Y-m-d format). Example: "2024-01-01"
     * @queryParam creation_time_to string End date for filtering (Y-m-d format). Example: "2024-12-31"
     *
     * @response {
     *   "current_page": 1,
     *   "data": [{
     *     "id": 1,
     *     "warehouse_id": 1,
     *     "orderStatus": "pending",
     *     "priority": "high",
     *     "time_stamp": "2024-01-01 00:00:00",
     *     "customer": {
     *       "businessName": "ACME Corp"
     *     },
     *     "vehicle_requirements": {
     *       "name": "van"
     *     }
     *   }],
     *   "per_page": 10,
     *   "total": 1
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "distribution_centre": ["The distribution centre must be an array."],
     *     "creation_time_from": ["The creation time from must be a valid date."]
     *   }
     * }
     *
     * @response 500 {
     *   "error": "Something went wrong",
     *   "message": "Error details"
     * }
     */
    public function filterOrders(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'distribution_centre' => 'nullable|array',
                'distribution_centre.*' => 'integer|exists:warehouses,id',
                'orderStatus' => 'nullable|array',
                'orderStatus.*' => 'string|in:pending,processing,completed,cancelled',
                'vehicleReq' => 'nullable|string|exists:vehicle_requirements,name',
                'priority' => 'nullable|string|in:low,medium,high',
                'client' => 'nullable|string|max:255',
                'creation_time_from' => 'nullable|date_format:Y-m-d',
                'creation_time_to' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'after_or_equal:creation_time_from'
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $ordersQuery = Order::query()
                ->leftJoin('customers', 'orders.cid', '=', 'customers.id')
                ->select('orders.*')
                ->with(['customer:id,businessName', 'vehicleRequirements:id,name'])
                ->when($request->filled('distribution_centre'), function ($query) use ($request) {
                    $query->whereIn('orders.warehouse_id', $request->distribution_centre);
                })
                ->when($request->filled('orderStatus'), function ($query) use ($request) {
                    $query->whereIn('orders.orderStatus', $request->orderStatus);
                })
                ->when($request->filled('vehicleReq'), function ($query) use ($request) {
                    $query->whereHas('vehicleRequirements', function ($q) use ($request) {
                        $q->where('name', $request->vehicleReq);
                    });
                })
                ->when($request->filled('priority'), function ($query) use ($request) {
                    $query->where('orders.priority', $request->priority);
                })
                ->when($request->filled('client'), function ($query) use ($request) {
                    $query->where('customers.businessName', $request->client);
                })
                ->when($request->filled('creation_time_from'), function ($query) use ($request) {
                    $query->where('time_stamp', '>=', $request->creation_time_from);
                })
                ->when($request->filled('creation_time_to'), function ($query) use ($request) {
                    $query->where('time_stamp', '<=', $request->creation_time_to);
                });

            return response()->json($ordersQuery->paginate(10));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Google Maps Directions
     *
     * Retrieves directions information from Google Maps API for given coordinates including waypoints.
     *
     * @authenticated
     *
     * @queryParam origin string required The origin coordinates in format "lat,lng". Example: "51.5074,-0.1278"
     * @queryParam destination string required The destination coordinates in format "lat,lng". Example: "51.5074,-0.1278"
     * @queryParam waypoints string optional Waypoint coordinates with optimize flag. Example: "optimize:true|52.6164,-2.0215|52.7762,-1.5820"
     * @queryParam mode string optional Travel mode (driving, walking, bicycling, transit). Default: "driving"
     *
     * @response 200 {
     *     "data": {
     *         "routes": [{
     *             "legs": [{
     *                 "distance": {"text": "2.1 km", "value": 2100},
     *                 "duration": {"text": "5 mins", "value": 300}
     *             }],
     *             "waypoint_order": [1, 0, 2]
     *         }]
     *     },
     *     "success": true
     * }
     */
    public function getDirections(Request $request)
    {
        $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'waypoints' => 'nullable|string',
            'mode' => 'nullable|string|in:driving,walking,bicycling,transit'
        ]);

        try {
            // Parse coordinates
            $origin = $this->parseCoordinates($request->origin);
            $destination = $this->parseCoordinates($request->destination);

            // Use MapsHelper to get distance and time with waypoints
            $mapsHelper = new MapsHelper();
            $result = $mapsHelper->getDistanceAndTimeWithWaypoints(
                $origin,
                $destination,
                $request->waypoints,
                $request->mode ?? 'driving',
                config('services.google.maps_api_key')
            );

            if (empty($result['routes'])) {
                return response()->json([
                    'data' => [],
                    'success' => false,
                    'message' => 'No routes found'
                ], 500);
            }

            // Format the response
            $formattedRoutes = array_map(function ($route) {
                return [
                    'legs' => array_map(function ($leg) {
                        return [
                            'distance' => [
                                'value' => $leg['distance'],
                                'text' => $this->formatDistance($leg['distance'])
                            ],
                            'duration' => [
                                'value' => $leg['duration'],
                                'text' => $this->formatDuration($leg['duration'])
                            ]
                        ];
                    }, $route['legs']),
                    'waypoint_order' => $route['waypoint_order'] ?? []
                ];
            }, $result['routes']);

            return response()->json([
                'data' => [
                    'routes' => $formattedRoutes
                ],
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get directions',
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Parse coordinates string into array
     */
    private function parseCoordinates(string $coords): array
    {
        [$lat, $lng] = explode(',', $coords);
        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng
        ];
    }

    /**
     * Format distance in meters to human readable string
     */
    private function formatDistance(int $meters): string
    {
        if ($meters >= 1000) {
            return round($meters / 1000, 1) . ' km';
        }
        return $meters . ' m';
    }

    /**
     * Format duration in seconds to human readable string
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds >= 3600) {
            $hours = floor($seconds / 3600);
            $mins = round(($seconds % 3600) / 60);
            return $hours . ' hr ' . ($mins > 0 ? $mins . ' mins' : '');
        }
        if ($seconds >= 60) {
            return round($seconds / 60) . ' mins';
        }
        return $seconds . ' secs';
    }

    /**
     * Get the order all information and also there associative  orders information
     */
    public function orderLiveTracking(Request $request)
    {
        // Validate the request to ensure `order_id` is provided
        if (!$request->has('order_id')) {
            return response()->json(['success' => false, 'message' => 'Order ID is required.']);
        }

        try {

            //get data from cache first
            $cacheKey = 'order_tracking_' . $request->order_id;
            if ($cachedData = cache()->get($cacheKey)) {
                return response()->json(['success' => true, 'tracking_data' => $cachedData, 'from_cache' => true]);
            }

            // Retrieve the order by ID
            $targetOrder = Order::query()
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('orders.id', $request->order_id)
                ->select(
                    'orders.max_parent_order_id',
                    'orders.id',
                    'disp.van',
                    'disp.num',
                    'disp.id_route',
                    'orders.lat',
                    'orders.lng',
                    'orders.deliverBy_datetime',
                    'orders.orderStatus',
                    'orders.orderType',
                    'orders.ref_no',
                    'orders.locked'
                )
                ->first();

            // return false message if the data is not found
            if (!$targetOrder) {
                $data = $this->ordersliveTrackingDetailsNotFound($request->order_id);
                return response()->json([
                    'success' => true,
                    'tracking_data' => $data
                ]);
            }

            /**
             * get the route id and pass it to the query ,
             *the route id it needed to retrive the driver live location
             */
            $routeId  =  $targetOrder->id_route;

            /**
             *get the required data .in the first query
             *only we find the the necessary data that we neeed in the subquery
             */
            $order = Order::query()
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where(function ($query) use ($targetOrder) {
                    $query->where('orders.id', $targetOrder->id)
                        ->orWhere(function ($q) use ($targetOrder) {
                            $q->where('orders.max_parent_order_id', $targetOrder->id)
                                ->orWhere('orders.id', $targetOrder->id);
                        });
                })
                ->with([
                    'customer',
                    'warehouseforpd',
                    'orderItems',
                    'disp.driver' => function ($query) use ($routeId) {
                        $query->with(['driverLocation' => function ($query) use ($routeId) {
                            $query->where('route_id', $routeId);
                        }]);
                    }
                ])
                ->get();

            // Format the tracking data
            $formattedData = $this->formatTrackingData($order, $targetOrder);

            // Cache the result for 30 seconds
            cache()->put($cacheKey, $formattedData, now()->addSeconds(30));

            return response()->json([
                'success' => true,
                'tracking_data' => $formattedData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // If order Live Tracking not found
    private function ordersLiveTrackingDetailsNotFound($orderId): array
    {
        $order = Order::with(['warehouse', 'orderCustomer', 'orderOperationTimeWindow'])->where('id', $orderId)->first();

        return [
            "id" => $order->id,
            "reference" => $order->ref_no ?? null,
            "order_type" => $order->orderType,
            "status" => $order->orderStatus,
            "lat" => (float) $order->lat,
            "lng" => (float) $order->lng,
            'date' => $order->orderOperationTimeWindow[0]->date ?? null,
            'start_time' => $order->orderOperationTimeWindow[0]->start_time ?? null,
            'arrival_time' => $order->orderOperationTimeWindow[0]->end_time  ?? null,
            "address" => $order->orderCustomer->street ?? null,
            "warehouse" => $order->warehouse ? [
                'name' => $order->warehouse->name_of_warehouse,
                'address' => $order->warehouse->location_of_warehouse,
                'location' => [
                    'lat' => (float) $order->warehouse->latitude,
                    'lng' => (float) $order->warehouse->longitude
                ]
            ] : null,
        ];
    }

    //route tracking
    public function routeLiveTracking(Request $request)
    {
        //get the route id from the request
        $routeId = $request->route_id;

        //get the route data from the database
        $route = DispRoute::find($routeId);

        //get the driver id from the route
        $driverId = $route->driver_id;

        //get the driver data from the database
        $driver = Driver::find($driverId);

        $route = DispRoute::query()
            ->where('id', $request->route_id)
            ->first();

        //get the orders from the route
        $orders = Order::query()
            ->where('id_route', $routeId)
            ->get();

        dd($orders);
    }

    /**
     * Format order tracking data
     *
     * @param Collection $orders Collection of orders with relationships
     * @return array Formatted tracking data
     */
    private function formatTrackingData($orders, $targetOrder): array
    {
        // Get warehouse location from first order
        $warehouse = $orders->first()->warehouseforpd ?? null;
        $warehouseLocation = $warehouse ? [
            'name' => $warehouse->name_of_warehouse,
            'address' => $warehouse->location_of_warehouse,
            'location' => [
                'lat' => (float) $warehouse->latitude,
                'lng' => (float) $warehouse->longitude
            ]
        ] : null;

        // get the total vehicle orders count
        $vehicletotalOrdersCount = 0;
        if ($orders->isNotEmpty() && $orders->first()->disp) {
            $vehicletotalOrdersCount = Disp::where('id_route', $orders->first()->disp->id_route)->count();
        }

        // Get driver info and location
        $vehicle = $orders->first()->disp->van ?? null;
        $driver = Driver::where('vehicle', $vehicle)->latest()->first();
        $driverLocation = null;
        if ($driver && $driver->driverLocation()->count() > 0) {
            //get the latest driver location
            $driverLocation = $driver->driverLocation()->latest()->first();
            $driverLocation = [
                'lat' => (float) $driverLocation->lat,
                'lng' => (float) $driverLocation->lng,
                'points' => $driverLocation->points,
                'timestamp' => $driverLocation->updated_at
            ];
        }

        $driverInfo = $driver ? [
            'id' => $driver->id,
            'name' => $driver->name,
            'vehicle_id' => $driver->vehicle,
            'location' => $driverLocation
        ] : null;

        // Sort orders by sequence
        $sortedOrders = $orders->sortBy('disp.num')->values();

        $mapsHelper = new MapsHelper();
        $polylines = [];
        $driverFound = false;

        // a route polyline route from the warehouse to target order

        // Get polyline from warehouse to driver's current location
        if ($warehouseLocation && $driverLocation) {
            $polylines['warehouse_to_driver'] = $mapsHelper->getOSRMPolyline(
                $warehouseLocation['location'],
                $driverLocation
            );
        }

        if ($warehouseLocation && $targetOrder) {
            $polylines['warehouse_to_target'] = $mapsHelper->getOSRMPolyline(
                $warehouseLocation['location'],
                [
                    'lat' => (float) $targetOrder->lat,
                    'lng' => (float) $targetOrder->lng
                ]
            );
        }

        // Process order polylines
        $previousPoint = $warehouseLocation['location'];
        foreach ($sortedOrders as $index => $order) {
            $currentPoint = [
                'lat' => (float) $order->lat,
                'lng' => (float) $order->lng
            ];

            // If driver hasn't been found yet, get full polyline
            if (!$driverFound) {
                // Check if driver is between previous point and current point
                if ($driverLocation && $this->isPointBetween($previousPoint, $currentPoint, $driverLocation)) {
                    // Get polyline from previous point to driver location
                    $polylines["segment_{$index}_to_driver"] = $mapsHelper->getOSRMPolyline(
                        $previousPoint,
                        $driverLocation
                    );
                    // Get polyline from driver location to current point
                    $polylines["driver_to_segment_{$index}"] = $mapsHelper->getOSRMPolyline(
                        $driverLocation,
                        $currentPoint
                    );
                    $driverFound = true;
                } else {
                    // Get full polyline for this segment
                    $polylines["segments"]["segment_{$index}"] = $mapsHelper->getOSRMPolyline(
                        $previousPoint,
                        $currentPoint
                    );
                }
            } else {
                // After driver is found, only store points for remaining orders
                $polylines["remaining_points"]["remaining_point_{$index}"] = [
                    'lat' => (float) $order->lat,
                    'lng' => (float) $order->lng
                ];
            }

            $previousPoint = $currentPoint;
        }

        // Format stops data
        $stops = $this->formatStops($sortedOrders);

        //polyline from driver to current order
        if ($driverLocation && $currentPoint) {
            $polylines['driver_to_current_order'] = $mapsHelper->getOSRMPolyline(
                $driverLocation,
                [
                    'lat' => (float) $targetOrder->lat,
                    'lng' => (float) $targetOrder->lng
                ]
            );
        }

        // Compile final response
        return [
            'route_id' => $orders->first()->disp->id_route ?? null,
            'warehouse' => $warehouseLocation,
            'driver' => $driverInfo,
            'stops' => $stops,
            'total_count' => $vehicletotalOrdersCount,
            'metrics' => [
                'total_stops' => count($stops),
                'total_weight' => $orders->sum('weight'),
                'total_volume' => $orders->sum('volume'),
                'total_distance' => $orders->max('total_distance'),
                'total_duration' => $orders->max('total_duration')
            ],
            'polylines' => $polylines,
            'timestamp' => now()->toIso8601String(),
            'date' => $targetOrder->orderOperationTimeWindow[0]->date ?? null,
            'start_time' => $targetOrder->orderOperationTimeWindow[0]->start_time ?? null,
            'arrival_time' => $targetOrder->orderOperationTimeWindow[0]->end_time  ?? null,
            'status' => $this->orderStatus($targetOrder->orderStatus, $targetOrder->locked, $targetOrder->disp->id_route  ?? null),
            'order_type' => $targetOrder->orderType ?? null,
            'address' => $targetOrder->street ?? null,
            'reference' => $targetOrder->ref_no ?? null,
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

        return $orderStatus;
    }

    /**
     * Check if a point is between two other points (approximately)
     */
    private function isPointBetween(array $start, array $end, array $point): bool
    {
        $buffer = 0.0001; // Approximately 11 meters at the equator

        $minLat = min($start['lat'], $end['lat']) - $buffer;
        $maxLat = max($start['lat'], $end['lat']) + $buffer;
        $minLng = min($start['lng'], $end['lng']) - $buffer;
        $maxLng = max($start['lng'], $end['lng']) + $buffer;

        return $point['lat'] >= $minLat &&
            $point['lat'] <= $maxLat &&
            $point['lng'] >= $minLng &&
            $point['lng'] <= $maxLng;
    }

    /**
     * Format stops data
     */
    private function formatStops($sortedOrders): array
    {
        return $sortedOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'reference' => $order->ref_no,
                'sequence' => $order->disp->num,
                'status' => $this->orderStatus($order->orderStatus, $order->locked, $order->disp->id_route  ?? null),
                'location' => [
                    'lat' => (float) $order->lat,
                    'lng' => (float) $order->lng
                ],
                'customer' => [
                    'name' => $order->customer->businessName,
                    'contact' => $order->customer->mob,
                    'email' => $order->customer->email1,
                    'address' => $order->customer->street,
                    'postcode' => $order->customer->postcode
                ],
                'items' => $order->order_items ? $order->order_items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->bedsName,
                        'description' => $item->notes,
                        'quantity' => $item->Qty,
                        'status' => $item->itemStatus,
                        'barcode' => $item->barcode
                    ];
                })->toArray() : [],
                'timing' => [
                    'estimated' => [
                        'arrival' => $order->arriveTime,
                        'completion' => $order->operationEndTime,
                        'duration' => $order->operation_duration
                    ],
                    'actual' => [
                        'arrival' => $order->takenarriveTime,
                        'completion' => $order->takenoperationEndTime,
                        'duration' => $order->takenrunDuration
                    ]
                ],
                'metrics' => [
                    'weight' => (float) $order->weight,
                    'volume' => (float) $order->volume,
                    'distance' => (float) $order->total_distance,
                    'duration' => (float) $order->total_duration
                ]
            ];
        })->toArray();
    }

    /**
     * Convert technical error messages to user-friendly ones
     *
     * @param string $technicalMessage
     * @return string
     */
    private function getFriendlyErrorMessage(string $technicalMessage): string
    {
        // Common error patterns and their friendly messages
        $errorPatterns = [
            '/SQLSTATE/' => 'Database error occurred while processing the order',
            '/Integrity constraint violation/' => 'Order reference number already exists',
            '/Added location is not found/' => 'Invalid delivery location provided',
            '/Invalid location coordinates/' => 'Please provide valid delivery location coordinates',
            '/Failed to create order/' => 'Unable to create order due to missing or invalid information',
            '/Undefined array key/' => 'Missing required order information',
            '/Call to a member function format/' => 'Invalid date format provided',
            '/Division by zero/' => 'Invalid calculation in order details',
            '/must be of type/' => 'Invalid data type provided for order details'
        ];

        // Check for specific error patterns and return friendly message
        foreach ($errorPatterns as $pattern => $friendlyMessage) {
            if (preg_match($pattern, $technicalMessage)) {
                return $friendlyMessage;
            }
        }

        // Default generic message if no specific pattern matches
        return 'Unable to process order. Please check the provided information and try again.';
    }

    /**
     * Change order status
     *
     * Updates the status of an order (Arrived, Attempted Delivery, Suspended, Cancelled, or Delivered).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the status change was successful
     * @responseField message string Success or error message
     */
    public function orderActions(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'order_status' => 'required|integer|between:0,7',
        ]);

        if (!isset($request->order_id) || !isset($request->order_status)) {
            return response()->json(['success' => false, 'message' => 'Invalid details']);
        }

        try {
            $order = Order::find($request->order_id);
            if ($order) {
                $status = '';
                $statusId = 2;
                $serverTime = Carbon::now()->format('H:i:s');

                switch ($request->order_status) {
                    case 0: // Delivery Started
                        $status = 'Delivery Started';
                        $statusId = 1;
                        $order->takenstartTime = $serverTime;
                        break;
                    case 1: // Arrived
                        $status = 'ARRIVED';
                        $statusId = 2;
                        $order->takenstartTime = $serverTime;
                        break;
                    case 2: // Attempted Delivery
                        $status = 'Attempted Delivery';
                        $statusId = 3;
                        $order->takenworkStartTime = $serverTime;
                        break;
                    case 3: // Suspend
                        $status = 'Suspended';
                        $statusId = 4;
                        $order->takenwaitingTime = $serverTime;
                        break;
                    case 4: // Cancelled
                        $status = 'Cancelled';
                        $statusId = 5;

                        if (isset($request->reason_id)) {
                            $orderDetail = new OrderRejectionDetail();
                            $orderDetail->order_id = $order->id;
                            $orderDetail->reason_id = $request->reason_id;
                            $orderDetail->comments = $request->comment;
                            $orderDetail->save();
                        }
                        break;
                    case 5: // Delivered
                        $status = 'Delivered';
                        $statusId = 6;
                        $order->takenoperationEndTime = $serverTime;
                        break;
                    case 6: // Departure from delivery location
                        $status = 'Departure from delivery location';
                        $statusId = 7;
                        $order->takenoperationEndTime = $serverTime;
                        break;
                    case 7: // Completed
                        $status = 'Completed';
                        $statusId = 8;
                        $order->takenoperationEndTime = $serverTime;
                        break;
                    default:
                        return response()->json(['success' => false, 'message' => 'Invalid status.'], 400);
                }

                $order->orderStatus = $status;
                $order->id_status = $statusId;
                $order->save();

                // Add logging after order status change.
                OrderLogger::log(
                    orderId: $order->id,
                    action: OrderAction::STATUS_CHANGED->value,
                    status: $order->orderStatus,
                    eta: $order->deliverBy_datetime
                );

                return response()->json(['success' => true, 'message' => 'Order has been updated.'], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'Order not found.']);
            }
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Error updating order: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'An error occurred while updating the order. Please try again later.'], 500);
        }
    }

    private function processNextOrder(array $nextOrderData, array $previousCoords, int $index): array
    {
        $currentCoords = [
            'lat' => floatval($nextOrderData['order']['position'][0]),
            'lng' => floatval($nextOrderData['order']['position'][1])
        ];

        $distanceData = $this->getDistanceAndDuration($previousCoords, $currentCoords);

        // Update order with new distance and duration
        Order::where('id', $nextOrderData['order']['id'])->update([
            'total_distance' => $distanceData['distance'],
            'total_duration' => $distanceData['duration']
        ]);

        return $distanceData;
    }
}
