<?php

namespace App\Http\Controllers;

use App\Models\DispRoute;
use App\Models\Driver;
use App\Models\DriverFcmToken;
use App\Models\Order;
use App\Models\OrderImage;
use App\Models\OrderItem;
use App\Models\Vehicle;
use App\Models\Disp;
use App\Models\OrderItemCancellationReason;
use App\Models\OrderItemRejectedImage;
use App\Models\OrderRejectionDetail;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\OrderLogger;
use App\Enums\OrderAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @tags Driver App
 * APIs for managing Driver mobile application functionality
 */
class DriverAppController extends Controller
{
    private static function getOrderFields()
    {
        return [
            'orders.id',
            'orders.ref_no',
            'orders.company_order_id as comapany_id',
            'orders.orderType',
            'orders.deliverBy_datetime',
            'orders.orderStatus',
            'orders.lat',
            'orders.lng',
            'orders.workStartTime as startTime',
            'orders.returnWareHouseTime  as endtime',
            'orders.cid',
            'orders.warehouse_id',
            'orders.volume',
            'orders.weight',
            'orders.orderType  as serviceLevel',
            'orders.id_company',
            'orders.collection',
            'orders.loadingTime',
            'orders.unLoadingTime',
        ];
    }

    /**
     * Login driver with credentials
     *
     * Authenticates a driver using their credentials and device information.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the login was successful
     * @responseField message string Success or error message
     * @responseField driver object Driver details (only returned on success)
     * @responseField driver.id integer Driver's ID
     * @responseField driver.name string Driver's name
     * @responseField driver.uName string Driver's username
     * @responseField driver.num string Driver's number
     * @responseField driver.active boolean Driver's active status
     */
    public function driverLogin(Request $request)
    {
        $request->validate([
            /** @var string Driver's username */
            'name' => 'required|string',
            /** @var string Driver's password */
            'password' => 'required|string',
            /** @var string Unique device identifier */
            'device_id' => 'required|string',
            /** @var string Firebase Cloud Messaging token */
            'fcm_token' => 'required|string',
            /** @var string Version of the app */
            'version' => 'required|string',
        ]);

        // Get the latest version from settings file
        $filePath = 'driver_app_settings.json';
        $latestVersion = '1.0.0'; // Default fallback version
        $apkUrl = ''; // Default empty APK URL

        if (Storage::exists($filePath)) {
            $settings = json_decode(Storage::get($filePath), true);
            if ($settings && isset($settings['latest_version']) && $settings['latest_version'] !== null) {
                $latestVersion = $settings['latest_version'];
                $apkUrl = $settings['apk_url'] ?? '';
            }
        }

        // Check if app version matches the required version
        if ($request->version !== $latestVersion) {
            return response()->json([
                'success' => false,
                'message' => 'Please update your app to the latest version',
                'required_version' => $latestVersion,
                'download_url' => $apkUrl
            ], 200);
        }

        // Find the driver by name
        $driver = Driver::select('id', 'name', 'uName', 'num', 'active', 'pwd')->where('uName', $request->name)->first();
        // Check if driver exists and if password matches
        if ($driver && Hash::check($request->password, $driver->pwd)) {

            DriverFcmToken::where('driver_id', $driver->id)->where('device_id', $request->device_id)->delete();

            $fcmdata = new DriverFcmToken;
            $fcmdata->driver_id = $driver->id;
            $fcmdata->device_id = $request->device_id;
            $fcmdata->fcm_token = $request->fcm_token;
            $fcmdata->save();
            unset($driver->pwd);

            $token = self::generateToken($driver);

            return response()->json([
                'message' => 'Driver login successfully.',
                'success' => true,
                'driver' => $driver,
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'message' => 'Driver not found',
            'success' => false,
        ], 200);
    }

    private function generateToken($driver)
    {
        $driver->token = $driver->id . '|' . str()->random(40);
        $driver->save();
        return $driver->token;
    }

    /**
     * Logout driver from device
     *
     * Logs out a driver from a specific device by removing their FCM token.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the logout was successful
     * @responseField message string Success or error message
     */
    public function driverLogout(Request $request)
    {
        $request->validate([
            /** @var int Driver's ID */
            'driver_id' => 'required|integer',
            /** @var string Device identifier to logout from */
            'device_id' => 'required|string',
        ]);

        if (! isset($request->driver_id) || ! isset($request->device_id)) {
            return response()->json(['success' => false, 'message' => 'Invalid details']);
        }
        // Find the driver by id
        $driver = Driver::where('id', $request->driver_id)->first();
        if ($driver) {
            $token = DriverFcmToken::where('driver_id', $driver->id)->where('device_id', $request->device_id)->first();
            if ($token) {
                $token->delete();
                $driver->token = null;
                $driver->save();

                return response()->json(['success' => true, 'message' => 'Driver successfully logged out on this device.']);
            } else {
                return response()->json(['success' => false, 'message' => 'Token not found for this device.']);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'driver not found.']);
        }
    }

    /**
     * Get driver's assigned routes
     *
     * Retrieves all routes assigned to a driver for the current day.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the request was successful
     * @responseField data array List of routes
     * @responseField data[].route_id integer Route identifier
     * @responseField data[].date string Route date
     * @responseField data[].startTime string Route start time
     * @responseField data[].endtime string Route end time
     * @responseField data[].status string Route status
     * @responseField data[].order_ids array List of order IDs associated with the route
     */
    public function getDriverRoutes(Request $request)
    {
        $request->validate([
            /** @var int Driver's ID */
            'driverId' => 'required|integer',
        ]);

        $driverWithVehicle = Vehicle::where('driver_id',  $request->driverId)->first();

        if ($driverWithVehicle) {
            $date = Carbon::now();
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            $formattedDate = $date->format('d-M-Y');

            /**
             * Retrieve records from DispRoute where the date is either today or a future date
             * not past.
             */
            $data = DispRoute::query()
                ->where('dispRoute.date', '>=', $formattedDate)
                ->where('dispRoute.vehicle', $driverWithVehicle->id)
                ->where('dispRoute.driver_id', $driverWithVehicle->driver_id)
                ->where('dispRoute.status', '!=', 'Failed')
                ->whereHas('routewithfirstorder', function ($query) {
                    $query->join('orders', 'disp.oid', '=', 'orders.id')
                        ->where('orders.locked', '2');
                })
                ->with(['routewithfirstorder' => function ($query) {
                    $query->join('orders', 'disp.oid', '=', 'orders.id')
                        ->select('orders.id', 'orders.workStartTime as  startTime', 'orders.returnWareHouseTime  as endtime', 'orders.max_parent_order_id', 'disp.id_route', 'disp.oid');
                }, 'routewithfirstorder.childOrders' => function ($query) {
                    // Ensure child orders are selected
                    $query->where('orders.locked', '2');
                    $query->select('id', 'max_parent_order_id');
                }])
                ->select('id', 'date', 'status')
                ->get();

            $result = $data->filter(function ($item) {
                // Collect main order ID and child order IDs
                $orderIds = collect([$item->routewithfirstorder->id])
                    ->merge($item->routewithfirstorder->childOrders->pluck('id'))
                    ->toArray();

                // Fetch statuses of all orders using the Order model
                $allOrders = Order::whereIn('id', $orderIds)
                    ->pluck('orderStatus');

                // Check if at least one order has a status other than "delivered" or "canceled"
                $hasAtLeastOneValidStatus = $allOrders->contains(function ($status) {

                    return ! in_array($status, ['Delivered', 'Cancelled', 'Completed']);
                });

                // Include only items with at least one valid status
                return $hasAtLeastOneValidStatus;
            })->map(function ($item) {
                // Collect main order ID and child order IDs
                $orderIds = collect([$item->routewithfirstorder->id])
                    ->merge($item->routewithfirstorder->childOrders->pluck('id'))
                    ->toArray();

                return [
                    'route_id' => $item->id,
                    'date' => $item->date,
                    'startTime' => $item->routewithfirstorder->startTime,
                    'endtime' => $item->routewithfirstorder->endtime,
                    'status' => $item->status, // Adjust as needed
                    'order_ids' => $orderIds,
                ];
            })->values(); // This will reset the keys and return a simple indexed array

            return response()->json(['success' => true, 'data' => $result], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 200);
        }
    }

    /**
     * Get single route details
     *
     * Retrieves detailed information about a specific route including all orders.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the request was successful
     * @responseField data array List of orders in the route
     * @responseField data[].id integer Order ID
     * @responseField data[].ref_no string Order reference number
     * @responseField data[].orderType string Type of order
     * @responseField data[].orderStatus string Current status of the order
     * @responseField data[].company_name string Company name
     * @responseField data[].orderCustomer object Customer details
     * @responseField data[].orderItemForApp array List of items in the order
     */
    public function getDriverSingleRoute(Request $request)
    {
        $request->validate([
            /** @var int Driver's ID */
            'driverId' => 'required|integer',
            /** @var int Route ID */
            'route_id' => 'required|integer',
        ]);

        // $driver = Driver::find($request->driverId);
        $driverWithVehicle = Vehicle::where('driver_id',  $request->driverId)->first();
        if ($driverWithVehicle) {
            $date = Carbon::now();
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();
            $route = DispRoute::query()
                ->join('disp', 'disp.id_route', 'dispRoute.id')
                ->where('id', $request->route_id)
                ->where('disp.num', 0)
                ->select('disp.id_route', 'dispRoute.id', 'disp.oid')
                ->first();
            if ($route) {

                /**
                 * Retrieve records from orders where the date is either today or a future date
                 * not past.
                 */
                $data = Order::query()
                    ->join('disp', 'disp.oid', '=', 'orders.id')
                    ->leftjoin('company', 'company.id', '=', 'orders.id_company')
                    ->where('disp.van', $driverWithVehicle->id)
                    ->where('deliverBy_datetime', '>=', $startOfDay)
                    ->where('locked', '2')
                    // ->where('orders.orderStatus', '!=', 'Delivered')
                    ->where(function ($query) use ($route) {
                        $query->where('orders.id', $route->oid);
                        $query->orWhere('orders.max_parent_order_id', $route->oid);
                    })
                    // ->where(function ($query) use ($request) {
                    //     $query->where('orders.id', $request->order_id)
                    //           ->orWhere('orders.max_parent_order_id', $request->order_id);
                    // })
                    ->select(self::getOrderFields())
                    ->selectRaw('company.company_name')
                    ->selectRaw("
                    CASE
                        WHEN orders.orderStatus = 'Routed' THEN 'New'
                        ELSE orders.orderStatus
                    END as orderStatus
                ")
                    ->with(['orderCustomer', 'orderItemForApp', 'warehouseforpd', 'orderOperationTimeWindow'])
                    ->orderBy('disp.num', 'asc') // Order by disp.num in ascending ord
                    ->get();

                return response()->json(['success' => true, 'data' => $data], 200);
            } else {
                return response()->json(['success' => false, 'data' => 'Not found.'], 200);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 200);
        }
    }

    public function backOfgetDriverRoutes(Request $request)
    {
        $driver = Driver::find($request->driverId);
        if ($driver) {
            $date = Carbon::now();
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            $data = Order::query()
                ->join('disp', 'disp.oid', '=', 'orders.id')
                ->leftjoin('company', 'company.id', '=', 'orders.id_company')
                ->where('disp.van', $driver->vehicle)
                ->where('disp.num', '0')
                // ->where('deliverBy_datetime', '>=', $startOfDay)
                // ->where('deliverBy_datetime', '<', $endOfDay)
                // ->where('orders.orderStatus', '=', 'Routed')
                // ->where('orders.locked', '=', '2')
                ->whereNull('orders.max_parent_order_id')
                ->orderBy('deliverBy_datetime', 'DESC')
                ->select(self::getOrderFields())
                ->selectRaw('company.company_name')
                ->selectRaw("
                CASE
                    WHEN orders.orderStatus = 'Routed' THEN 'New'
                    WHEN orders.orderStatus = 'ARRIVED' THEN 'New'
                    ELSE orders.orderStatus
                END as orderStatus
            ")
                ->get();

            // Iterate over each order and add the first object to routeChildOrders
            foreach ($data as $orderData) {

                $orderData->routeChildOrders = Order::query()
                    ->join('disp', 'disp.oid', '=', 'orders.id')
                    ->join('company', 'company.id', '=', 'orders.id_company')
                    ->where('disp.van', $driver->vehicle)
                    ->where(function ($query) use ($orderData) {
                        $query->where('orders.id', $orderData->id)
                            ->orWhere('orders.max_parent_order_id', $orderData->id);
                    })
                    // ->where('deliverBy_datetime', '>=', $startOfDay)
                    // ->where('deliverBy_datetime', '<', $endOfDay)
                    // ->where('orders.orderStatus', '=', 'Routed')
                    ->with(['orderCustomer', 'orderItemForApp'])
                    ->orderBy('deliverBy_datetime', 'DESC')
                    ->select(self::getOrderFields())
                    ->selectRaw('company.company_name')
                    ->selectRaw("
                CASE
                        WHEN orders.orderStatus = 'Routed' THEN 'ARRIVED'
                        ELSE orders.orderStatus
                    END as orderStatus
                ")
                    ->get();
            }

            return response()->json(['success' => true, 'data' => $data], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 200);
        }
    }

    /**
     * Get order cancellation reasons
     *
     * Retrieves list of available reasons for order cancellation.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the request was successful
     * @responseField reasons array List of cancellation reasons
     * @responseField reasons[].id integer Reason ID
     * @responseField reasons[].name string Reason description
     */
    public function reasons()
    {

        $reason = DB::table('order_cancellation_reasons')->select('id', 'name')->get();

        return response()->json(['success' => true, 'reasons' => $reason], 200);
    }

    /**
     * Change order status
     *
     * Updates the status of an order (Arrived, Attempted Delivery, Suspended, Cancelled, or Delivered).
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the status change was successful
     * @responseField message string Success or error message
     */
    public function changeOrderStatus(Request $request)
    {

        $request->validate([
            /** @var int Driver's ID */
            'driver_id' => 'required|integer',
            /** @var int Order ID */
            'order_id' => 'required|integer',
            /**
             * @var int Status code
             *
             * @example 1 = Arrived, 2 = Attempted Delivery, 3 = Suspended, 4 = Cancelled, 5 = Delivered
             */
            'order_status' => 'required|integer|between:0,7',
        ]);

        if (! isset($request->driver_id) || ! isset($request->order_id) || ! isset($request->order_status)) {
            return response()->json(['success' => false, 'message' => 'Invalid details']);
        }

        $driver = Driver::find($request->driver_id);

        if ($driver) {

            $order = Order::find($request->order_id);
            if ($order) {

                $status = '';
                $statusId = 23;
                $serverTime = $request->server_time ?  $request->server_time  : Carbon::now()->format('H:i:s');

                if ($request->order_status == 0) {
                    // Delivery Started
                    $status = 'Delivery Started';
                    $statusId = 1;
                    $order->takenstartTime = $serverTime;
                    // takenstartTime
                } elseif ($request->order_status == 1) {
                    // Arrived
                    $status = 'ARRIVED';
                    $statusId = 23;
                    $order->takenarriveTime = $serverTime;
                    // takenarriveTime
                } elseif ($request->order_status == 2) {

                    // Attempted Delivery
                    $status = 'Attempted Delivery';
                    $statusId = 10;
                    $order->takenworkStartTime = $serverTime;
                    // takenworkStartTime
                } elseif ($request->order_status == 3) {

                    // Suspend
                    $status = 'Suspended';
                    $statusId = 24;
                    $order->takenwaitingTime = $serverTime;
                    // takenwaitingTime

                } elseif ($request->order_status == 4) {

                    //   Cancelled
                    $status = 'Cancelled';
                    $statusId = 9;
                } elseif ($request->order_status == 5) {
                    // COMPLETED
                    $status = 'Delivered';
                    $statusId = 5;
                    $order->takenoperationEndTime = $serverTime;
                    // takenoperationEndTime
                }

                $order->orderStatus = $status;
                $order->id_status = $statusId;
                $order->save();

                // Log status change
                OrderLogger::log(
                    orderId: $order->id,
                    action: OrderAction::STATUS_CHANGED->value,
                    status: $status,
                    userId: $driver->id,
                    eta: $order->deliverBy_datetime,
                    actionBy: "Driver"
                );

                //  $tokens = DB::table('driver_fcm_tokens') ->select('*') ->where('driver_id',$driver->id)->get();

                //   $titleFcm = "Order ". $order->ref_no;
                //   $bodyFcm  = "Dear, order status been changed to " . $status;
                //   $typeFcm  = 0;
                //   foreach($tokens as $token){
                //       Helper::sendPushNotification($token->fcm_token, $titleFcm, $bodyFcm,$typeFcm);
                //   }

                //Update the routes status..
                $this->updateRouteStatus($order->id);

                return response()->json(['success' => true, 'message' => 'Order has been updated.'], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'Order not found.']);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.']);
        }
    }

    /**
     * Reject order item
     *
     * Marks an order item as rejected with a reason and optional comments.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the rejection was successful
     * @responseField message string Success or error message
     */
    public function orderItemReject(Request $request)
    {
        $request->validate([
            /** @var int Driver's ID */
            'driver_id' => 'required|integer',
            /** @var int Item ID */
            'item_id' => 'required|integer',
            /** @var int Order ID */
            'order_id' => 'required|integer',
            /** @var int Reason ID */
            'reason' => 'required|integer',
            /** @var string Optional comments */
            'comments' => 'nullable|string',
            /** @var bool Whether entire order is rejected */
            'is_order_rejected' => 'nullable|boolean',
        ]);

        if (
            ! isset($request->driver_id) || ! isset($request->item_id)
            || ! isset($request->order_id) || ! isset($request->reason)
        ) {
            return response()->json(['success' => false, 'message' => 'Invalid details']);
        }

        $driver = Driver::find($request->driver_id);
        if ($driver) {

            $order = Order::find($request->order_id);
            if ($order) {
                if ($request->is_order_rejected == 1) {
                    $orderItem = OrderItem::find($request->item_id);
                    if ($orderItem) {

                        $orderItem->is_rejected = 1;
                        $orderItem->rejected_reason = $request->reason;
                        $orderItem->comments = $request->comments;
                        $orderItem->itemStatus = 'Rejected';
                        $orderItem->save();

                        // Log item rejection
                        OrderLogger::log(
                            orderId: $request->order_id,
                            action: OrderAction::STATUS_CHANGED->value,
                            status: 'Item Rejected',
                            userId: $driver->id,
                            eta: null,
                            actionBy: "Driver"
                        );

                        return response()->json(['success' => true, 'message' => 'Order Item has been updated.'], 200);
                    } else {
                        return response()->json(['success' => false, 'message' => 'Item not found.']);
                    }
                } else {
                    // order reject  Details
                    $orderDetail = new OrderRejectionDetail;
                    $orderDetail->order_id = $order->id;
                    $orderDetail->reason_id = $request->reason;
                    $orderDetail->comments = $request->comments;
                    $orderDetail->save();
                    // update order
                    $order->orderStatus = 'Cancelled';
                    $order->id_status = '9';
                    $order->save();
                    $orderItems = OrderItem::where('oid', $order->id)->get();
                    if ($orderItems) {
                        foreach ($orderItems as $item) {
                            $getItem = OrderItem::find($item->id);
                            $getItem->is_rejected = 1;
                            $getItem->rejected_reason = $request->reason;
                            $getItem->comments = $request->comments;
                            $getItem->save();
                        }
                    }

                    // Log order cancellation
                    OrderLogger::log(
                        orderId: $order->id,
                        action: OrderAction::CANCELLED->value,
                        status: 'Cancelled',
                        userId: $driver->id,
                        eta: null,
                        actionBy: "Driver"
                    );

                    return response()->json(['success' => true, 'message' => 'Order has been updated.'], 200);
                }

                // update the order compleation status
                $this->updateOrderCompletionStatus($order->id);
            } else {
                return response()->json(['success' => false, 'message' => 'Order not found.']);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.']);
        }
    }

    /**
     * Upload rejected item image
     *
     * Stores an image for a rejected order item.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @requestMediaType multipart/form-data
     *
     * @responseField success boolean Whether the upload was successful
     * @responseField message string Success or error message
     * @responseField data object Image details (only on success)
     */
    public function orderItemRejectImage(Request $request)
    {
        $request->validate([
            /** @var int Order item ID */
            'order_item_id' => 'required|integer',
            /** @var string Base64 encoded image */
            'image' => 'required|string',
        ]);

        if ($request->order_item_id) {
            $imagePath = $this->saveBase64Image($request->image, 'uploads/rejected-images');

            $imageSave = OrderItemRejectedImage::create([
                'order_item_id' => $request->order_item_id,
                'image' => $imagePath,
                'type' => 'item_reject',
            ]);

            if ($imageSave) {
                $imageSave->makeHidden(['created_at', 'updated_at']);

                return response()->json([
                    'success' => true,
                    'message' => 'Order item image has been successfully created.',
                    'data' => $imageSave,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save order item image.',
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Image data not provided.',
        ], 400);
    }

    public function orderRejectImage(Request $request)
    {

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'order_id' => 'required|integer',
        ]);

        if ($request->has('image')) {
            $imagePath = $this->saveBase64Image($validated['image'], 'uploads/rejected-images');

            $imageSave = OrderImage::create([
                'order_id' => $validated['order_id'],
                'path' => $imagePath,
                'type' => 'order_rejected',
            ]);

            if ($imageSave) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order  image has been successfully created.',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save order  image.',
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Image data not provided.',
        ], 400);
    }

    public function storeSignatureAndAttachments(Request $request)
    {
        // return($request->all());
        try {

            if (! isset($request->driver_id)) {
                return response()->json(['success' => false, 'message' => 'Invalid details']);
            }

            $driver = Driver::find($request->driver_id);
            $order_id = $request->order_id;
            $orderItems = $request->order_items_id;
            $signature = $request->signature;
            $attachment = $request->attachment;
            $signaturePath = $this->saveBase64Image($signature, 'uploads/order-signatures');

            $attachmentPaths = [];
            foreach ($attachment as $attachmentBase64) {
                $attachmentPaths[] = $this->saveBase64Image($attachmentBase64, 'uploads/order-attachments');
            }

            $attachmentsPathString = implode(',', $attachmentPaths);

            // store the time in witch time the order is completed
            $serverTime =  $request->server_time ?  $request->server_time  : Carbon::now()->format('H:i:s');

            $order = Order::where('id', $order_id)->firstOrFail();

            $order->orderStatus = 'Completed';
            $order->id_status = '8';

            if ($serverTime && $order->takenarriveTime) {
                // Convert both times to Carbon instances
                $serverTimeCarbon = Carbon::createFromFormat('H:i:s', $serverTime);
                $takenArriveTimeCarbon = Carbon::createFromFormat('H:i:s', $order->takenarriveTime);

                // Calculate the difference in seconds
                $timeDifference = $takenArriveTimeCarbon->diffInSeconds($serverTimeCarbon);

                // Store the time difference
                $order->takenoperationEndTime = $timeDifference;
            }

            $order->save();

            // Log delivery completion
            OrderLogger::log(
                orderId: $order->id,
                action: OrderAction::STATUS_CHANGED->value,
                status: 'Completed',
                userId: $driver->id,
                eta: null,
                actionBy: "Driver"
            );

            // orderDetails
            $orderDetail = new OrderImage;
            $orderDetail->order_id = $order->id;
            $orderDetail->type = 'order_completed';
            $orderDetail->path = $attachmentsPathString;
            $orderDetail->save();

            // store the order signature is well.
            if ($signaturePath) {
                $orderDetail = new OrderImage;
                $orderDetail->order_id = $order->id;
                $orderDetail->type = 'order_signature';
                $orderDetail->path = $signaturePath;
                $orderDetail->save();
            }
            // $tokens = DB::table('driver_fcm_tokens') ->select('*') ->where('driver_id',$driver->id)->get();

            //      $titleFcm = "Order ". $order->ref_no;
            //      $bodyFcm  = "Dear, order status has been completed. ";
            //      $typeFcm  = 0;
            //      foreach($tokens as $token){
            //          Helper::sendPushNotification($token->fcm_token, $titleFcm, $bodyFcm,$typeFcm);
            //       }

            if ($request->van_image) {

                $vanImage = $this->saveBase64Image($request->van_image, 'uploads/order-van');
                $orderDetail = new OrderImage;
                $orderDetail->order_id = $order->id;
                $orderDetail->type = 'van_image';
                $orderDetail->path = $vanImage;
                $orderDetail->save();
            }
            if (! empty($orderItems)) {
                foreach ($orderItems as $orderItem) {
                    $orderItemData = OrderItem::where('id', $orderItem)->where('oid', $order_id)->firstOrFail();

                    $orderItemData->itemStatus = 'Delivered';
                    $orderItemData->id_item_status = '5';
                    $orderItemData->save();
                }
            }
            if ($order) {

                // Update the compeltion status of the order base on the order item...
                $this->updateOrderCompletionStatus($order->id);

                //Update the routes status..
                $this->updateRouteStatus($order->id);

                return response()->json(['success' => true, 'message' => 'Order updated successfully.']);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to update the order.'], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ], 500);
        }
    }

    protected function saveBase64Image($base64Image, $folderName)
    {
        $image = base64_decode($base64Image);
        $fileName = uniqid() . '.png';
        $filePath = $folderName . '/' . $fileName;
        if (! file_exists(public_path($folderName))) {
            mkdir(public_path($folderName), 0777, true);
        }
        file_put_contents(public_path($filePath), $image);

        return $filePath;
    }

    public function getOrderItemRejectImages($orderItemId)
    {
        $images = OrderItemRejectedImage::where('order_item_id', $orderItemId)->get();

        if ($images->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No images found for the specified order item.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'images' => $images,
        ], 200);
    }

    public function removeOrderItemRejectImage(Request $request)
    {
        $image = OrderItemRejectedImage::where('id', $request->image_id)->first();
        if (! $image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found for the specified order item.',
            ], 404);
        }

        $filePath = public_path($image->image);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image has been successfully removed.',
        ], 200);
    }

    // order cancel
    public function orderCancel(Request $request)
    {
        if (! isset($request->driver_id) || ! isset($request->order_id) || ! isset($request->reason) || ! isset($request->comments) || ! isset($request->image)) {
            return response()->json(['success' => false, 'message' => 'Invalid details']);
        }

        $driver = Driver::find($request->driver_id);

        if ($driver) {

            $order = Order::find($request->order_id);
            if ($order) {
                // Validate image format first
                // if ($request->has('image')) {
                $imagePath = $this->saveBase64Image($request->image, 'uploads/rejected-images');
                $imageSave = OrderImage::create([
                    'order_id' => $order->id,
                    'path' => $imagePath,
                    'type' => 'order_rejected',
                ]);
                // }
                $status = 'Cancelled';
                $statusId = 9;

                $order->orderStatus = $status;
                $order->id_status = $statusId;
                $order->save();
                // order rejection details
                $orderDetail = new OrderRejectionDetail;
                $orderDetail->order_id = $order->id;
                $orderDetail->reason_id = $request->reason;
                $orderDetail->comments = $request->comments;
                $orderDetail->save();

                // Log order cancellation
                OrderLogger::log(
                    orderId: $order->id,
                    action: OrderAction::CANCELLED->value,
                    status: 'Cancelled',
                    userId: $driver->id,
                    eta: null,
                    actionBy: "Driver"
                );

                //  $tokens = DB::table('driver_fcm_tokens') ->select('*') ->where('driver_id',$driver->id)->get();

                //  $titleFcm = "Order ". $order->ref_no;
                //  $bodyFcm  = "Dear, order status has been canceled. ";
                //  $typeFcm  = 0;
                //  foreach($tokens as $token){
                //      Helper::sendPushNotification($token->fcm_token, $titleFcm, $bodyFcm,$typeFcm);
                //   }

                 //Update the routes status..
                 $this->updateRouteStatus($order->id);

                return response()->json(['success' => true, 'message' => 'Order has been Canceled.'], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'Order not found.']);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.']);
        }
    }

    // scan and reject item

    public function itemScanAndReject(Request $request)
    {
        try {
            if (!isset($request->driver_id) || !isset($request->item_id)) {
                return response()->json(['success' => false, 'message' => 'Invalid details']);
            }

            $driver = Driver::find($request->driver_id);
            if (!$driver) {
                return response()->json(['success' => false, 'message' => 'Driver not found.']);
            }

            if ($request->is_rejected == 1) {
                $orderItem = OrderItem::find($request->item_id);
                if ($orderItem) {
                    if ($request->qty_rejected + $orderItem->qty_rejected > $orderItem->Qty) {
                        return response()->json([
                            'success' => false,
                            'message' => 'The rejected quantity cannot be greater than the available quantity.',
                        ]);
                    }

                    $orderItem->is_rejected = 1;
                    $orderItem->rejected_reason = $request->reason;
                    $orderItem->itemStatus = 'Rejected';
                    $orderItem->comments = $request->comments;
                    $orderItem->qty_rejected += $request->qty_rejected;
                    $orderItem->save();

                    $imagePath = $this->saveBase64Image($request->image, 'uploads/rejected-images');

                    OrderItemRejectedImage::create([
                        'order_item_id' => $request->item_id,
                        'image' => $imagePath,
                        'type' => 'item_scan',
                    ]);

                    // Log item rejection
                    OrderLogger::log(
                        orderId: $orderItem->oid,
                        action: OrderAction::STATUS_CHANGED->value,
                        status: 'Item Rejected - Scan',
                        userId: $driver->id,
                        eta: null,
                        actionBy: "Driver"
                    );

                    // update the  order completeion status
                    $this->updateOrderCompletionStatus($orderItem->oid);

                    return response()->json(['success' => true, 'message' => 'Order Item has been updated.'], 200);
                } else {
                    return response()->json(['success' => false, 'message' => 'Order Item not found.'], 200);
                }
            } else {
                $orderItem = OrderItem::find($request->item_id);
                if ($orderItem) {
                    if ($request->qty_scanned + $orderItem->qty_scanned > $orderItem->Qty) {
                        return response()->json([
                            'success' => false,
                            'message' => 'The scanned quantity cannot be greater than the available quantity.',
                        ]);
                    }

                    $orderItem->qty_scanned += $request->qty_scanned;
                    $orderItem->itemStatus = 'Delivered';
                    $orderItem->save();

                    // Log item scan
                    OrderLogger::log(
                        orderId: $orderItem->oid,
                        action: OrderAction::STATUS_CHANGED->value,
                        status: 'Item Scanned',
                        userId: $driver->id,
                        eta: null,
                        actionBy: "Driver"
                    );
                    // Update the order completion status
                    $this->updateOrderCompletionStatus($orderItem->oid);
                    return response()->json(['success' => true, 'message' => 'Order Item has been updated.'], 200);
                } else {
                    return response()->json(['success' => false, 'message' => 'Order Item not found.'], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    //get the order items
    public function getOrderItems(Request $request)
    {
        if (! isset($request->driver_id) || ! isset($request->order_id)) {
            return response()->json(['success' => false, 'message' => 'Invalid details']);
        }

        $orderItem = OrderItem::whereIn('oid', [$request->order_id])
            //  ->where('itemStatus' , '!=' , 'Delivered')
            ->leftjoin('orders', 'orders.id', 'orderItems.oid')
            ->leftjoin('stock_items', 'orderItems.itemid', 'stock_items.id')
            ->leftjoin('stockDetail', 'stock_items.id', 'stockDetail.sid')
            ->leftjoin('order_item_cancellation_reasons', 'orderItems.rejected_reason', 'order_item_cancellation_reasons.id')
            ->select(
                'orderItems.id',
                'orderItems.qty_scanned',
                'orderItems.rejected_reason',
                'orderItems.qty_rejected',
                'orderItems.itemid',
                'orderItems.barcode',
                'orderItems.item_ex_id as external_id',
                'orderItems.Qty as qty',
                'orderItems.oid',
                'orderItems.bedsName as sku',
                'stockDetail.weight',
                'stockDetail.cbm',
                'stockDetail.length',
                'stockDetail.width',
                'stockDetail.height',
                'stock_items.itemDescription',
                'stock_items.itemName',
                'stock_items.itemCode',
                'orders.collection',
                'order_item_cancellation_reasons.name'
            )
            ->get();
        if ($orderItem) {
            return response()->json(['success' => true, 'items' => $orderItem], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Order Item not found.'], 200);
        }
    }

    //  reset items
    public function itemReset(Request $request)
    {
        if (! isset($request->driver_id) || ! isset($request->item_id)) {
            return response()->json(['success' => false, 'message' => 'Invalid details']);
        }

        $orderItem = OrderItem::find($request->item_id);
        if ($orderItem) {
            $orderItem->is_rejected = 0;
            $orderItem->qty_rejected = 0;
            $orderItem->qty_scanned = 0;
            $orderItem->itemStatus = 'Unchecked';
            $orderItem->save();
            //  delete the images
            $image = OrderItemRejectedImage::where('order_item_id', $request->item_id)->first();
            if ($image) {

                $filePath = public_path($image->image);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                $image->delete();
            }

            // update the order completion status...
            $this->updateOrderCompletionStatus($orderItem->oid);

            // end of the  delete  image code
            return response()->json(['success' => true, 'message' => 'Order Item has been reset.', 'items' => $orderItem], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Order Item not found.'], 200);
        }
    }

    //  order history
    public function orderHistory(Request $request)
    {
        // $driver = Driver::find($request->driver_id);
        $driverWithVehicle = Vehicle::where('driver_id',  $request->driver_id)->first();

        if ($driverWithVehicle) {
            $data = DispRoute::query()
                ->where('dispRoute.vehicle', $driverWithVehicle->id)
                ->where('dispRoute.driver_id', $driverWithVehicle->driver_id)
                ->where(function ($query) {
                    $query->where('dispRoute.status', 'Failed') // Condition 1: Routes with status "Failed"
                        ->orWhereHas('routewithfirstorder', function ($query) {
                            $query->join('orders', 'disp.oid', '=', 'orders.id')
                                ->where('orders.locked', '2');
                        });
                })
                ->with([
                    'routewithfirstorder' => function ($query) {
                        $query->join('orders', 'disp.oid', '=', 'orders.id')
                            ->select(
                                'orders.id',
                                'orders.startTime',
                                'orders.loadingTime',
                                'orders.returnWareHouseTime as endtime',
                                'orders.max_parent_order_id',
                                'disp.id_route',
                                'disp.oid'
                            );
                    },
                    'routewithfirstorder.childOrders' => function ($query) {
                        $query->select('id', 'max_parent_order_id');
                    },
                ])
                ->select('id', 'date', 'status') // Include "status" in the main query
                ->get();

            $result = $data->filter(function ($item) {
                // Check if routewithfirstorder exists
                if (! $item->routewithfirstorder) {
                    return false; // Exclude this item
                }

                if ($item->status === 'Failed') {
                    // Include routes with status "Failed"
                    return true;
                }

                // Collect main order ID and child order IDs
                $orderIds = collect([$item->routewithfirstorder->id])
                    ->merge($item->routewithfirstorder->childOrders->pluck('id'))
                    ->toArray();

                // Fetch statuses of all orders using the Order model
                $allOrders = Order::whereIn('id', $orderIds)
                    ->pluck('orderStatus');

                // Check if all statuses are either "delivered" or "canceled"
                $allOrdersCancelledOrDelivered = $allOrders->every(function ($status) {
                    return in_array($status, ['Delivered', 'Cancelled', 'Departure from delivery location', 'Completed']);
                });

                return $allOrdersCancelledOrDelivered;
            })->map(function ($item) {
                // Check if routewithfirstorder exists
                if (! $item->routewithfirstorder) {
                    return null; // Skip this item in mapping
                }

                // Collect main order ID and child order IDs
                $orderIds = collect([$item->routewithfirstorder->id])
                    ->merge($item->routewithfirstorder->childOrders->pluck('id'))
                    ->toArray();

                // Fetch orders with statuses 'Delivered' or 'Cancelled'
                $deliveredOrCancelledOrders = Order::whereIn('id', $orderIds)
                    ->whereIn('orderStatus', ['Delivered', 'Cancelled', 'Completed'])
                    ->get(['id', 'orderStatus']);

                return [
                    'route_id' => $item->id,
                    'date' => $item->date,
                    'startTime' => $item->routewithfirstorder->loadingTime,
                    'endtime' => $item->routewithfirstorder->endtime,
                    'status' => 'Completed',
                    'order_ids' => $orderIds,
                    // 'orders' => $deliveredOrCancelledOrders, // Include delivered/cancelled orders
                ];
            })->filter()->values(); // Remove null values from the map

            return response()->json(['success' => true, 'data' => $result], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 200);
        }
    }

    //  single route  orders history
    public function driverRouteOdersHistory(Request $request)
    {
        // DispRoute::where('')
        $driver = Driver::find($request->driver_id);
        if ($driver) {
            $orders = Order::whereIn('id', $request->order_id)
                ->with([
                    'orderCustomer',
                    'orderItemForApp' => function ($query) {
                        $query->with('itemImages');
                    },
                    'OrderImages',
                ])
                ->select('id', 'ref_no', 'deliverBy_datetime', 'orderStatus', 'lat', 'lng', 'cid')
                ->get();

            return response()->json(['success' => true, 'data' => $orders], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 200);
        }
    }

    public function orderItemCencalationReson(Request $request)
    {

        $driver = Driver::find($request->driver_id);
        if ($driver) {
            $reasons = OrderItemCancellationReason::select('id', 'name')->get();

            return response()->json(['data' => $reasons, 'success' => true], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 200);
        }
    }

    // cancel route
    public function routeCancel(Request $request)
    {
        // Validate request parameters
        if (!$request->filled(['driver_id', 'route_id'])) {
            return response()->json(['success' => false, 'message' => 'Invalid details'], 400);
        }

        // Find the driver
        $driver = Driver::find($request->driver_id);
        if (!$driver) {
            return response()->json(['success' => false, 'message' => 'Driver not found'], 404);
        }

        // Find the route with disp join
        $route = DispRoute::query()
            ->where('dispRoute.id', $request->route_id)
            ->join('disp', 'disp.id_route', '=', 'dispRoute.id')
            ->select('dispRoute.*', 'disp.oid') // Ensure oid is retrieved
            ->first();

        if (!$route) {
            return response()->json(['success' => false, 'message' => 'Route not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Update orders related to the route
            $ordersUpdated = DB::table('orders')
                ->where('id', $route->oid)
                ->orWhere('max_parent_order_id', $route->oid)
                ->update([
                    'orderStatus' => 'Cancelled',
                    'id_status' => '9',
                ]);

            // Log affected orders
            Log::info('Orders updated for cancellation', [
                'route_id' => $route->id,
                'max_parent_id' => $route->oid,
                'affected_orders' => $ordersUpdated,
                'timestamp' => now(),
            ]);

            // Update the route status
            $routeUpdated = DB::table('dispRoute')
                ->where('id', $route->id)
                ->update([
                    'status' => 'Failed',
                ]);

            // Log route status update
            Log::info('Route status updated to Failed', [
                'route_id' => $route->id,
                'status' => 'Failed',
                'affected_rows' => $routeUpdated,
                'timestamp' => now(),
            ]);

            DB::commit();

            return response()->json(['message' => 'The route has been cancelled.', 'success' => true], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling route', [
                'error' => $e->getMessage(),
                'route_id' => $route->id,
                'timestamp' => now(),
            ]);
            return response()->json(['success' => false, 'message' => 'An error occurred while cancelling the route'], 500);
        }
    }

    // cancel update
    public function routeUpdate(Request $request)
    {

        if (! isset($request->driver_id) || ! isset($request->route_id) || ! isset($request->route_status)) {
            return response()->json(['success' => false, 'message' => 'Invalid details']);
        }
        $driver = Driver::find($request->driver_id);
        if ($driver) {
            $route = DispRoute::query()
                ->where('id', $request->route_id)
                ->first();
            if ($route) {
                DB::table('dispRoute')
                    ->where('id', $route->id)
                    ->update([
                        'status' => $request->route_status,
                    ]);

                return response()->json(['message' => 'the route is been updated.', 'success' => true], 200);
            } else {

                return response()->json(['message' => 'Route not found.', 'success' => true], 200);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 200);
        }
    }

    /**
     * Save Driver Coordinates
     *
     * Save the coordinates of a driver.
     *
     * @group Driver Management
     */
    public function saveDriverCoordinates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|integer|exists:drivers,id',
            'route_id' => 'required|integer',
            'lat' => 'required',
            'lng' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $driver = Driver::find($request->driver_id);

        $driverLocation = $driver->driverLocation()->where('route_id', $request->route_id)->first();

        $driver_lat = $request->lat;
        $driver_lng = $request->lng;

        if ($this->isPointOutsideGreatBritain($driver_lat, $driver_lng)) {
            $driver_lat = 52.533195966321 + 0.0001;
            $driver_lng = -2.0832931995392 + 0.0001;
        }

        if ($driverLocation) {
            // Update existing record
            $existingPoints = json_decode($driverLocation->points, true) ?? [];

            $existingPoints[] = ['lat' => $driver_lat,  'lng' => $driver_lng];

            $driverLocation->update([
                'lat' => $driver_lat,
                'lng' => $driver_lng,
                'points' => json_encode($existingPoints),
            ]);
        } else {
            // Create a new record
            $driver->driverLocation()->create([
                'driver_id' => $driver->id,
                'route_id' => $request->route_id,
                'lat' => $driver_lat,
                'lng' => $driver_lng,
                'points' => json_encode([['lat' => $driver_lat,  'lng' => $driver_lng]]),
            ]);
        }

        return response()->json(['message' => 'Driver coordinates updated successfully'], 200);
    }

    //check if driver location is outside the great britain
    private function isPointOutsideGreatBritain($lat, $lng)
    {
        return $lat < 49.95 || $lat > 60.83 || $lng < -13.16 || $lng > 3.4;
    }

    /**
     * Get Driver Coordinates
     *
     * Get the coordinates of a driver.
     *
     * @group Driver Management
     */
    public function getDriverCoordinates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|integer|exists:drivers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $driver = Driver::find($request->driver_id);
        $location = $driver->driverLocation;

        return response()->json(['lat' => $location->lat, 'lng' => $location->lng], 200);
    }

    // update completion status of the order
    private function updateOrderCompletionStatus($orderId)
    {
        $getItemsStatus = OrderItem::where('oid', $orderId)->pluck('itemStatus');

        if ($getItemsStatus->contains('Rejected') || $getItemsStatus->contains('Checked') || $getItemsStatus->contains('Unchecked')) {
            if ($getItemsStatus->every(fn($status) => in_array($status, ['Rejected', 'Checked', 'Unchecked']))) {
                $completionStatus = 'Not Delivered';
            } else {
                $completionStatus = 'Partially Delivered';
            }
        } elseif ($getItemsStatus->every(fn($status) => $status === 'Delivered')) {
            $completionStatus = 'Fully Delivered';
        } else {
            $completionStatus = 'New'; // Handle unexpected scenarios
        }

        Order::where('id', $orderId)
            ->update(['completion_status' => $completionStatus]);
    }

    public function getDriverAppSettings()
    {
        // Define the storage path for the settings file
        $filePath = 'driver_app_settings.json';

        // Check if the settings file exists
        if (Storage::exists($filePath)) {
            $settings = Storage::get($filePath);
            $settings = json_decode($settings, true);

            // If the settings are null or the latest_version is null, set a default version
            if (!$settings || !isset($settings['latest_version']) || $settings['latest_version'] === null) {
                $version = '1.0.0';
                $apkUrl = '';
                //remove the file if it exists
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }

                Storage::put($filePath, json_encode([
                    'latest_version' => $version,
                    'apk_url' => $apkUrl
                ], JSON_PRETTY_PRINT));
            } else {
                $version = $settings['latest_version'];
                $apkUrl = $settings['apk_url'] ?? '';
            }
        } else {
            // Create the file with default settings if it doesn't exist
            $version = '1.0.0';
            $apkUrl = '';
            Storage::put($filePath, json_encode([
                'latest_version' => $version,
                'apk_url' => $apkUrl
            ], JSON_PRETTY_PRINT));

            // Log file creation for debugging
            Log::info('Created driver app settings file', [
                'path' => Storage::path($filePath),
                'content' => json_encode(['latest_version' => $version, 'apk_url' => $apkUrl])
            ]);
        }

        return response()->json([
            'success' => true,
            'version' => $version,
            'apk_url' => $apkUrl
        ], 200);
    }

    public function updateDriverAppSettings(Request $request)
    {
        $request->validate([
            'latest_version' => 'required|string',
            'apk_url' => 'nullable|string|url',
        ]);

        $filePath = 'driver_app_settings.json';
        $latest_version = $request->latest_version;
        $apk_url = $request->apk_url ?? '';

        // Create or update the settings file
        $settings = [
            'latest_version' => $latest_version,
            'apk_url' => $apk_url
        ];

        //remove the file if it exists
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }

        Storage::put($filePath, json_encode($settings, JSON_PRETTY_PRINT));

        // Verify the file was updated correctly
        $savedSettings = json_decode(Storage::get($filePath), true);

        if (
            $savedSettings &&
            isset($savedSettings['latest_version']) &&
            $savedSettings['latest_version'] === $latest_version
        ) {
            return response()->json([
                'success' => true,
                'message' => 'Driver app settings updated successfully',
                'version' => $savedSettings['latest_version'],
                'apk_url' => $savedSettings['apk_url'] ?? ''
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update driver app settings',
                'attempted_value' => $latest_version,
                'current_value' => $savedSettings['latest_version'] ?? null
            ], 500);
        }
    }

    /**
     * Get All Driver Data in a Single Request with Nested Structure
     *
     * Retrieves routes, orders and items in a single API call
     * with a hierarchical structure: routes → orders → items
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the request was successful
     * @responseField data.routes array List of driver routes with nested orders and items
     */
    public function getAllData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|integer',
            'include_completed' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $driverId = $request->driver_id;
        $includeCompleted = $request->include_completed ?? false;

        // Get driver with vehicle info
        $driverWithVehicle = Vehicle::where('driver_id', $driverId)->first();

        if (!$driverWithVehicle) {
            return response()->json(['success' => false, 'message' => 'Driver not found or no vehicle assigned.'], 404);
        }

        try {
            // Get current date
            $date = Carbon::now();
            $formattedDate = $date->format('d-M-Y');
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            // First, get all routes for this driver
            $routesQuery = DispRoute::query()
                ->where('dispRoute.vehicle', $driverWithVehicle->id)
                ->where('dispRoute.driver_id', $driverWithVehicle->driver_id);

            if (!$includeCompleted) {
                   $routesQuery->whereRaw("STR_TO_DATE(dispRoute.date, '%d-%b-%Y') >= STR_TO_DATE(?, '%d-%b-%Y')", [$formattedDate])
                    ->where('dispRoute.status', '!=', 'Failed')
                    ->where('dispRoute.status', '!=', 'Completed');
            }

            $routesQuery->with(['disp' => function ($query) {
                $query->orderBy('num', 'asc');

                // Apply condition on the related `order` table properly
                $query->whereHas('order', function ($orderQuery) {
                    $orderQuery->where('locked', '2');
                });

                $query->with(['order' => function ($orderQuery) {
                    $orderQuery->leftJoin('company', 'company.id', '=', 'orders.id_company')
                        ->with('orderCustomer', 'orderItemForApp', 'warehouseforpd', 'orderOperationTimeWindow');

                    // Disable unwanted global scope and relationships
                    $orderQuery->withoutGlobalScope('withExecutionRelations')
                        ->without(['communicationLogs', 'orderLogs', 'orderAttachments', 'OrderRejectionDetails']);

                    // Get the selected fields from the orders table
                    $orderQuery->select(self::getOrderFields())
                        ->addSelect('company.company_name');
                }]);
            }]);

            $routes = $routesQuery->get();

            // Format the query result to the requeired format
            $formatedData = $this->formataData($routes);

            return response()->json([
                'success' => true,
                'routes' =>  $formatedData
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('getAllData error: ' . $e->getMessage(), [
                'driver_id' => $driverId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change the format of the GetAllData query...
     */
    private function formataData($routes)
    {
        return $routes->filter(function ($route) {
            return $route->disp->count() !== 0;
        })->map(function ($route) {
            $startTime = null;
            $endTime = null;
            $unLoadingTimeIfCollection = null;

            $orders = $route->disp->map(function ($disp) use (&$startTime, &$endTime, &$unLoadingTimeIfCollection) {
                if ($disp->num == 0) {
                    $startTime = $disp->order->loadingTime;

                    if ($disp->order->orderType == "Collection") {
                        $unLoadingTimeIfCollection = $disp->order->unLoadingTime;
                    } else {
                        $endTime = $disp->order->returnWareHouseTime;
                    }
                }

                $order = clone $disp->order;
                if ($order->orderStatus === 'Routed') {
                    $order->orderStatus = 'New';
                }

                return $order;
            });

            // Now finalize $endTime
            if ($unLoadingTimeIfCollection) {
                $endTime = $unLoadingTimeIfCollection;
            }

            return [
                'route_id' => $route->id,
                'date' => $route->date,
                'status' => $route->status,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'orders' => $orders,
            ];
        })->values();
    }


    /**
     * Get Route and Order History in a Single Request
     *
     * Retrieves completed routes and their orders in a hierarchical structure
     * similar to getAllData, but includes only completed/cancelled orders.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @responseField success boolean Whether the request was successful
     * @responseField data.routes array List of completed driver routes with nested orders
     */
    public function getRouteOrderHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $driverId = $request->driver_id;

        // Get driver with vehicle info
        $driverWithVehicle = Vehicle::where('driver_id', $driverId)->first();

        if (!$driverWithVehicle) {
            return response()->json(['success' => false, 'message' => 'Driver not found or no vehicle assigned.'], 404);
        }

        try {
            // Get routes for this driver that are either failed or have orders with locked status
            $routes = DispRoute::query()
                ->where('dispRoute.vehicle', $driverWithVehicle->id)
                ->where('dispRoute.driver_id', $driverWithVehicle->driver_id)
                ->where(function ($query) {
                    $query->where('dispRoute.status', 'Failed')
                        ->orWhereHas('disp.order', function ($query) {
                            $query->where('orders.locked', '2');
                        });
                })
                ->with(['disp' => function ($query) {
                    $query->orderBy('num', 'asc');

                    $query->with(['order' => function ($orderQuery) {
                        $orderQuery->leftJoin('company', 'company.id', '=', 'orders.id_company')
                            ->with([
                                'orderCustomer',
                                'orderItemForApp' => function ($query) {
                                    $query->with('itemImages');
                                },
                                'OrderImages',
                                'childOrders' => function ($query) {
                                    $query->select('id', 'max_parent_order_id', 'orderStatus');
                                }
                            ]);

                        // Disable unwanted global scope and relationships
                        $orderQuery->withoutGlobalScope('withExecutionRelations')
                            ->without(['communicationLogs', 'orderLogs', 'orderAttachments', 'OrderRejectionDetails']);

                        // Get the selected fields from the orders table
                        $orderQuery->select(self::getOrderFields())
                            ->addSelect('company.company_name');
                    }]);
                }])
                ->select('id', 'date', 'status')
                ->get();

            // Filter routes to only include completed/cancelled orders or failed routes
            $formattedRoutes = $routes->filter(function ($route) {
                // If route is failed, include it
                if ($route->status === 'Failed') {
                    return true;
                }

                // If no orders in the route, exclude it
                if ($route->disp->count() === 0) {
                    return false;
                }

                // Get all orders including child orders
                $mainOrders = $route->disp->pluck('order');
                $orderIds = $mainOrders->pluck('id')->toArray();

                // Get child orders for each main order
                $childOrderIds = [];
                foreach ($mainOrders as $order) {
                    if ($order && $order->childOrders) {
                        $childOrderIds = array_merge($childOrderIds, $order->childOrders->pluck('id')->toArray());
                    }
                }

                // Combine main and child order IDs
                $allOrderIds = array_merge($orderIds, $childOrderIds);

                // Check if all orders are completed or cancelled
                $allOrders = Order::whereIn('id', $allOrderIds)
                    ->pluck('orderStatus');

                $allOrdersComplete = $allOrders->every(function ($status) {
                    return in_array($status, ['Delivered', 'Cancelled', 'Departure from delivery location', 'Completed']);
                });

                return $allOrdersComplete;
            })
                ->map(function ($route) {
                    // Get all orders from disp relation
                    $orders = $route->disp->map(function ($disp) {
                        if (!$disp->order) {
                            return null;
                        }

                        return $disp->order;
                    })->filter()->values();

                    // Return the formatted route with its orders
                    return [
                        'route_id' => $route->id,
                        'date' => $route->date,
                        'status' => $route->status === 'Failed' ? 'Failed' : 'Completed',
                        'startTime' => $orders->first() ? $orders->first()->loadingTime : null,
                        'endTime' => $orders->first() ? $orders->first()->endtime : null,
                        'orders' => $orders
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'routes' => $formattedRoutes
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('getRouteOrderHistory error: ' . $e->getMessage(), [
                'driver_id' => $driverId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update the route status
    private function updateRouteStatus($orderId)
    {
        $disptach = Disp::where('oid', $orderId)->first();

        if (!$disptach) {
            return; // Early return if dispatch not found
        }

        // Get all orders of the same route
        $orders = Disp::where('id_route', $disptach->id_route)
            ->join('orders', 'orders.id', '=', 'disp.oid')
            ->select(['orders.id', 'orders.orderStatus', 'disp.id_route'])
            ->get();

        // List of statuses that are acceptable for route completion
        $completedStatuses = [
            'Delivered',
            'Cancelled',
            'Departure from delivery location',
            'Completed',
        ];

        // Check if all orders have one of the completed statuses
        $allCompleted = $orders->every(function ($order) use ($completedStatuses) {
            return in_array($order->orderStatus, $completedStatuses);
        });

        // If all are completed, update the route status
        if ($allCompleted && count($orders)) {
            DispRoute::where('id', $orders[0]->id_route)
                ->update(['status' => 'Completed']);
        }
    }

}
