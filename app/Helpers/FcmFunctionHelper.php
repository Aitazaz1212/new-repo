<?php

namespace App\Helpers;

use App\Helpers\Helper;
use App\Helpers\OrderProcessingMailHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
/**
 * Helper class for handling Firebase Cloud Messaging (FCM) notifications
 * related to driver operations.
 */
class FcmFunctionHelper
{
    /**
     * Send notification to driver when a new order is added to their route.
     *
     * @param int $orderId The ID of the order that was added
     * @param int $value Status flag (2 = confirmed)
     * @return void
     */
    public static function sendDetailsToDriverNewOrderAdded($orderId, $value)
    {
        $drivers = DB::table('disp')
            ->join('drivers', 'drivers.vehicle', '=', 'disp.van')
            ->where('oid', $orderId)
            ->select('drivers.id', 'drivers.vehicle', 'disp.van', 'disp.id_route', 'disp.oid', 'disp.date')
            ->get();

        if ($value == 2) {
            foreach ($drivers as $driver) {
                if (isset($driver->id) && $driver->id != '') {
                    $tokens = DB::table('driver_fcm_tokens')->select('*')->where('driver_id', $driver->id)->get();

                    $title = "New order assigned " . $driver->id_route;
                    $body = "A new order has been assigned to you on  date   " .str_repeat(" ", 3) . $driver->date;
                    $type = 3;
                    foreach ($tokens as $token) {
                        Helper::sendPushNotification($token->fcm_token, $title, $body, $type);
                    }
                }
            }
        }
    }

    /**
     * Get the selected fields for  rotues
     */
    private static function getSelectedFields()
    {
        return [
            'vehicles.driver_id as id',
            'vehicles.id as vehicle',
            'disp.van',
            'disp.oid',
            'disp.id_route',
            'disp.date' ,
            'orders.max_parent_order_id',
            'orders.cid' ,
            'orders.ref_no' ,
            'orders.deliverBy_datetime' ,
            'customers.businessName as clientName' ,
            'customers.email1 as email' ,
            'customers.street as address' ,
            'customers.postcode',
            'customers.fax',
            'dispRoute.route_notification'
        ];
    }

    /**
     * Send notification to driver when a new route is assigned.
     *
     * @param array $orderIds Array of order IDs in the route
     * @param int $value Status flag (2 = confirmed)
     * @return void
     */
    public static function sendDetailsToDriverRouteAssigned($orderIds, $value)
    {
        $drivers = DB::table('disp')
            ->join('vehicles', 'vehicles.id', '=', 'disp.van')
            ->join('orders', 'orders.id', '=', 'disp.oid')
            ->join('customers', 'orders.cid', '=', 'customers.id')
            ->join('dispRoute', 'disp.id_route', '=', 'dispRoute.id')
            ->whereIn('oid', $orderIds)
            ->select(self::getSelectedFields())
            ->get();

             // log notificaiton for debuging
            Log::channel('fcmlogs')->info([$drivers]);
            
            // Separate parent orders and child orders
            $parentOrders = $drivers->filter(function ($driver) {
                return is_null($driver->max_parent_order_id);
            });

            $childOrders = $drivers->filter(function ($driver) {
                return !is_null($driver->max_parent_order_id);
            });
        if ($value == 2) {
            /**
            *If $parentOrders  objects array are not empty its means
             *we will sent the notifcation for route assigned.
             *if  $parentOrders is empty then we will sent the nofitication order assigned
              * the controll will goes to the else statment.
            */
            if ($parentOrders->isNotEmpty()) {
                foreach ($parentOrders as $parentOrder) {

                    if (isset($parentOrder->id) && $parentOrder->id != '' ) {

                        $tokens = DB::table('driver_fcm_tokens')->select('*')->where('driver_id', $parentOrder->id)->get();
                        if($parentOrder->route_notification == false){

                            DB::table('dispRoute')
                            ->where('id' , $parentOrder->id_route)
                            ->update(['route_notification' => true]);

                            $titleFcm = "Route assigned";
                            $bodyFcm = "A new route has been assigned to you on date " . str_repeat(" ", 3)  . $parentOrder->date;
                            $typeFcm = 1;
                        }
                        else{

                             /**
                             * update the route status if the route is fail  and the user add new order in  that route ,
                             * then update route status from fail to new.
                             */
                            DB::table('dispRoute')
                            ->where('id' , $parentOrder->id_route)
                            ->where('status', 'Failed')
                            ->update(['status' => 'New']);

                            $titleFcm = "New order assigned " ;
                            $bodyFcm = "A new order has been assigned to you on  date" . str_repeat(" ", 3) . $parentOrder->date;
                            $typeFcm = 3;
                        }


                        foreach ($tokens as $token) {

                            // log notificaiton for debuging
                            Log::channel('fcmlogs')->info([$token->fcm_token, $titleFcm, $bodyFcm, $typeFcm]);

                            Helper::sendPushNotification($token->fcm_token, $titleFcm, $bodyFcm, $typeFcm);
                        }
                    }
                }
            }
            else{
                if ($childOrders->isNotEmpty()) {
                    foreach ($childOrders as $childOrder) {
                    /**
                     * update the route status if the route is fail  and the user add new order in  that route ,
                     * then update route status from fail to new.
                     */
                    DB::table('dispRoute')
                    ->where('id' , $childOrder->id_route)
                    ->where('status', 'Failed')
                    ->update(['status' => 'New']);
                     self::sendDetailsToDriverNewOrderAdded($childOrder->oid ,$value);
                    }
                }
            }
             /**
             * Sent email to customer
             */
             OrderProcessingMailHelper::sentEmailToCustomerWhenRouteIsAssigned($drivers);
        }
    }

    /**
     * Send notification to driver when an order is unassigned from their route.
     *
     * @param int $orderIds The ID of the order being unassigned
     * @param int $value Status flag (2 = confirmed)
     * @return void
     */
    public static function sendDetailsToDriverOrderUnassigned($orderIds, $value)
    {
        $drivers = DB::table('disp')
            ->join('drivers', 'drivers.vehicle', '=', 'disp.van')
            ->join('orders', 'disp.oid', '=', 'orders.id')
            ->where('oid', $orderIds)
            ->select('drivers.id', 'drivers.vehicle', 'disp.van', 'orders.max_parent_order_id', 'disp.id_route', 'disp.oid', 'disp.date')
            ->get();

        if ($drivers->isNotEmpty()) {
            $maxParentOrderId = $drivers->first()->max_parent_order_id ?? null;

            // Ensure maxParentOrderId is not null
            if ($maxParentOrderId) {
                $routeId = DB::table('disp')->where('oid', $maxParentOrderId)->first();

                if ($routeId && $value == 2) {
                    foreach ($drivers as $driver) {
                        if (!empty($driver->id)) {
                            $tokens = DB::table('driver_fcm_tokens')
                                ->select('fcm_token')
                                ->where('driver_id', $driver->id)
                                ->get();

                            $titleFcm = "Order Unassigned " . ($routeId->id_route ?? 'Unknown Route') . ',' . $driver->oid;
                            $bodyFcm = " Order has been removed on date " . str_repeat(" ", 3)  . $driver->date;
                            $typeFcm = 4;

                            foreach ($tokens as $token) {
                                Helper::sendPushNotification($token->fcm_token, $titleFcm, $bodyFcm, $typeFcm);
                            }
                        }
                    }
                } else {
                    // Handle cases where $routeId is null
                    // Log::error("Route ID not found for max_parent_order_id: $maxParentOrderId");
                    // Handle the FCM notification for the driver in the future.
                }
            }
        }
    }

    /**
     * Send notification to driver when an entire route is unassigned.
     *
     * @param int $orderId The ID of the order in the route
     * @param int $value Status flag (2 = confirmed)
     * @return void
     */
    public static function sendDetailsToDriverRouteUnassigned($orderId, $value)
    {
        $drivers = DB::table('disp')
            ->join('drivers', 'drivers.vehicle', '=', 'disp.van')
            ->where('oid', $orderId)
            ->select('drivers.id', 'drivers.vehicle', 'disp.van', 'disp.oid', 'disp.id_route', 'disp.date')
            ->get();

        if ($value == 2) {
            foreach ($drivers as $driver) {
                if (isset($driver->id) && $driver->id != '') {
                    $tokens = DB::table('driver_fcm_tokens')->select('*')->where('driver_id', $driver->id)->get();

                    $titleFcm = "Route Unassigned ";
                    $bodyFcm = "Route has been removed on date " . str_repeat(" ", 3)  . $driver->date;
                    $typeFcm = 2;
                    foreach ($tokens as $token) {
                        Helper::sendPushNotification($token->fcm_token, $titleFcm, $bodyFcm, $typeFcm);
                    }
                }
            }
        }
    }
}
