<?php

use App\Http\Controllers\AboutCompanyController;
use App\Http\Controllers\Api\ArtisanCommandController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreaksController;
use App\Http\Controllers\CapacityUnitController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DispatcherController;
use App\Http\Controllers\DriverAppController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverManifestConfigurationController;
use App\Http\Controllers\ExecutionNotifyController;
use App\Http\Controllers\ExecutionPreferenceController;
use App\Http\Controllers\ExportsController;
use App\Http\Controllers\ImportExportSettingController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\OperationDurationController;
use App\Http\Controllers\OrderCancellationReasonController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemCancellationReasonController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\OrderTrackingWidgetController;
use App\Http\Controllers\PlanningPreferencesController;
use App\Http\Controllers\PlanningToolController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SmsEmailFormatController;
use App\Http\Controllers\SmtpController;
use App\Http\Controllers\SpeedZoneController;
use App\Http\Controllers\TerritoriesController;
use App\Http\Controllers\TerritoriesGroupController;
use App\Http\Controllers\TimeWindowController;
use App\Http\Controllers\TrackAndTraceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleRequirementsController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\ForKsystemController;
use Illuminate\Support\Facades\Route;

/**
 * @tags Authentication
 */
Route::post('/login-user', [AuthController::class, 'login'])->name('auth.login');
Route::post('/app/driver/login', [DriverAppController::class, 'driverLogin']);

/**
 * @tags Driver App Management
 */
Route::prefix('app')->middleware('driver-app-auth')->group(function () {
    Route::post('/driver/routes', [DriverAppController::class, 'getDriverRoutes']);
    Route::post('/driver/action/order/status', [DriverAppController::class, 'changeOrderStatus']);
    Route::post('/driver/item/reject', [DriverAppController::class, 'orderItemReject']);
    Route::post('/driver/order/reject/image', [DriverAppController::class, 'orderRejectImage']);
    Route::post('/driver/item/reject/image', [DriverAppController::class, 'orderItemRejectImage']);
    Route::get('/driver/retrieve/image/{orderItemId}', [DriverAppController::class, 'getOrderItemRejectImages']);
    Route::post('/driver/remove/image', [DriverAppController::class, 'removeOrderItemRejectImage']);
    Route::post('/driver/reasons', [DriverAppController::class, 'reasons']);
    Route::post('/driver/order/complete', [DriverAppController::class, 'storeSignatureAndAttachments']);
    Route::post('/driver/order/cancel', [DriverAppController::class, 'orderCancel']);
    Route::post('/driver/single/route', [DriverAppController::class, 'getDriverSingleRoute']);
    Route::post('/driver/scan_and_reject/item', [DriverAppController::class, 'itemScanAndReject']);
    Route::post('/driver/order/items', [DriverAppController::class, 'getOrderItems']);
    Route::post('/driver/order/items/reset', [DriverAppController::class, 'itemReset']);
    Route::post('/driver/order/history', [DriverAppController::class, 'orderHistory']);
    Route::post('/driver/get/route/orders', [DriverAppController::class, 'driverRouteOdersHistory']);
    Route::post('/driver/get/route/orders/history', [DriverAppController::class, 'getRouteOrderHistory']);
    Route::post('/driver/logout', [DriverAppController::class, 'driverLogout']);
    Route::post('/driver/item/cencelation/reason', [DriverAppController::class, 'orderItemCencalationReson']);
    Route::post('/driver/route/fail/', [DriverAppController::class, 'routeCancel']);
    Route::post('/driver/route/update/', [DriverAppController::class, 'routeUpdate']);
    Route::post('/driver/save/coordinates', [DriverAppController::class, 'saveDriverCoordinates'])->name('driver.coordinates.save');
    Route::get('/driver/get/coordinates', [DriverAppController::class, 'getDriverCoordinates'])->name('driver.coordinates.get');


    Route::post('/driver/routes/data' , [DriverAppController::class, 'getAllData']);
});

//@order live tracking
Route::post('/order/live/tracking/', [OrderController::class, 'orderLiveTracking']);

//@route tracking
Route::post('/route/live/tracking/', [OrderController::class, 'routeLiveTracking']);

Route::middleware('auth:sanctum')->group(function () {

    /**
     * @tags Authentication
     */
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    /**
     * @tags User Management
     */
    Route::get('/edit-profile', [UserController::class, 'getUserData'])->name('users.get');
    Route::post('/update-profile', [UserController::class, 'updateProfile'])->name('users.update-profile');
    Route::get('/get/users', [UserController::class, 'getUsers'])->name('users.list');
    Route::post('/save/user', [UserController::class, 'saveUser'])->name('users.save');
    Route::post('/delete/users', [UserController::class, 'deleteUser'])->name('users.delete');
    Route::post('/update/user', [UserController::class, 'updateUser'])->name('users.update');

    /**
     * @tags Order Management
     */
    Route::post('/import/orders', [OrderController::class, 'import'])->name('orders.import');
    Route::get('/get/orders', [OrderController::class, 'getOrders'])->name('orders.list');
    Route::post('/save/order', [OrderController::class, 'saveOrder'])->name('orders.store');
    Route::post('/update/order', [OrderController::class, 'updateOrder'])->name('orders.update');
    Route::post('/delete/order', [OrderController::class, 'deleteOrder'])->name('orders.delete');
    Route::post('/bulk-order-delete', [OrderController::class, 'bulkOrderDelete'])->name('orders.bulk.delete');
    Route::post('/delete/orderItems', [OrderController::class, 'deleteOrderItem'])->name('orders.delete.items');
    Route::post('/update/orderItem', [OrderController::class, 'updateOrderItem'])->name('orders.update.item');
    Route::post('/clone/order', [OrderController::class, 'cloneOrder'])->name('orders.clone');
    Route::post('/lockUnlock', [OrderController::class, 'lockUnlock'])->name('orders.lock.unlock');
    Route::post('/unallocate/orders', [OrderController::class, 'unallocateOrders'])->name('orders.unallocate');
    Route::post('/merge/orders', [OrderController::class, 'mergeOrdersToOrder'])->name('orders.merge');
    Route::post('/resequence/orders', [OrderController::class, 'reSequence'])->name('orders.resequence');
    Route::post('/resequence_timeline/orders', [OrderController::class, 'reSequenceTimeline'])->name('orders.resequence.timeline');
    Route::post('/reverse-sequence/orders', [OrderController::class, 'reverseRunSequence'])->name('orders.reverse.run');
    Route::post('/store/calculatedtime', [OrderController::class, 'storeCalculatedTimeOfAllOders'])->name('orders.calculated.time');
    Route::get('/search/orders', [OrderController::class, 'searchOrders'])->name('orders.search');
    Route::post('/filter/order', [OrderController::class, 'filterOrders'])->name('orders.filter');
    Route::get('/get/calculatedtimeofoders', [OrderController::class, 'getDirections'])->name('orders.directions');
    Route::post('/orders/actions', [OrderController::class, 'orderActions'])->name('orders.actions');

    /**
     * @tags Order Items
     */
    Route::post('/reports/order/items', [OrderItemController::class, 'orderItemsReport'])->name('order.items.report');
    Route::post('/report/OverallDeliveryAccuracyReport', [OrderItemController::class, 'overallDeliveryAccuracyReport'])->name('order.items.overall.delivery.accuracy');

    /**
     * @tags Warehouse Management
     */
    Route::get('/get-centers', [WarehouseController::class, 'getCenters'])->name('warehouse.centers');
    Route::get('/get/centers', [WarehouseController::class, 'getAllCenters'])->name('warehouse.all.centers');
    Route::post('/distribution-center/save', [WarehouseController::class, 'saveCenter'])->name('warehouse.save');
    Route::post('/distribution-center/update', [WarehouseController::class, 'updateCenter'])->name('warehouse.update');
    Route::post('/distribution-center/delete', [WarehouseController::class, 'deleteCenter'])->name('warehouse.delete');

    /**
     * @tags Planning Tool
     */
    Route::post('/planning/tool', [PlanningToolController::class, 'index'])->name('planning.tool');
    Route::post('/toggleOrder/status', [PlanningToolController::class, 'toggleOrderStatus'])->name('planning.toggle');
    Route::post('/plannningTool/undo', [PlanningToolController::class, 'undo'])->name('planning.undo');
    Route::post('/plannningTool/redo', [PlanningToolController::class, 'redo'])->name('planning.redo');
    Route::post('/search/vehicle/orders/drivers', [PlanningToolController::class, 'searchOrderVehiclesDrivers'])->name('planning.order.vehicles.drivers');
    Route::post('/optimze', [PlanningToolController::class, 'optimize'])->name('planning.optimze');
    Route::get('/get/planning/tool/routes/cache', [PlanningToolController::class, 'getPlanningToolRoutesCache'])->name('planning.tool.routes.cache');
    Route::post('/clear/planning/tool/routes/cache', [PlanningToolController::class, 'clearPlanningToolRoutesCache'])->name('planning.tool.routes.cache.clear');
    Route::post('/delete/planning/tool/routes/cache', [PlanningToolController::class, 'deletePlanningToolRoutesCache'])->name('planning.tool.routes.cache.delete');
    Route::post('/create/planning/tool/routes/cache', [PlanningToolController::class, 'createPlanningToolRoutesCache'])->name('planning.tool.routes.cache.create');
    Route::post('/get/planning/tool/routes/cache/all', [PlanningToolController::class, 'getPoints'])->name('planning.tool.routes.cache.all');
    Route::post('/planning-tool/route-polylines', [PlanningToolController::class, 'getVehicleRoutePolylines']);

    /**
     * @tags Driver Management
     */
    Route::get('/get/drivers', [DriverController::class, 'getDrivers'])->name('driver.list');
    Route::get('/search/drivers', [DriverController::class, 'searchDrivers'])->name('driver.search');
    Route::post('/save/driver', [DriverController::class, 'saveDriver'])->name('driver.save');
    Route::post('/update/driver', [DriverController::class, 'updateDriver'])->name('driver.update');
    Route::post('/delete/drivers', [DriverController::class, 'deleteDrivers'])->name('driver.delete');
    Route::post('/filter/drivers', [DriverController::class, 'filterDrivers'])->name('driver.filter');
    Route::post('/update/driver/start-time', [DriverController::class, 'updateDriverStartTime'])->name('driver.update.start.time');
    Route::post('/report/driverPerformance', [DriverController::class, 'driverPerformance'])->name('driver.performance');
    Route::post('/report/onTime', [DriverController::class, 'onTimeReport'])->name('driver.on.time.report');
    Route::post('/report/dailyRun', [DriverController::class, 'dailyRun'])->name('driver.daily.run');
    Route::post('/unallocated/driver', [DriverController::class, 'unAllocatedDriver'])->name('driver.unallocated');

    /**
     * @tags Vehicle Management
     */
    Route::get('/get/vehicle/orders', [VehicleController::class, 'getOrderOfVehicle'])->name('vehicle.orders');
    Route::post('/plan/to/dispatch', [VehicleController::class, 'assignOrderToVehicle'])->name('vehicle.assign');
    Route::post('/vehicle/swap/runs', [VehicleController::class, 'swapVehicleRuns'])->name('vehicle.swap');
    Route::post('/allocate-order-to-vehicle/orders', [VehicleController::class, 'allocateOrderToVehicle'])->name('vehicle.allocate');
    Route::post('/vehicle/bulk/change', [VehicleController::class, 'bulkChange'])->name('vehicle.bulk.change');
    Route::get('/get-vehicles', [VehicleController::class, 'getVehiclesDetails'])->name('vehicle.details');
    Route::post('/vehicles/save', [VehicleController::class, 'saveVehicle'])->name('vehicle.save');
    Route::post('/vehicle/edit', [VehicleController::class, 'editVehicle'])->name('vehicle.edit');
    Route::post('/vehicles/update', [VehicleController::class, 'updateVehicle'])->name('vehicle.update');
    Route::post('/vehicle/delete', [VehicleController::class, 'deleteVehicle'])->name('vehicle.delete');
    Route::post('/vehicle/filter', [VehicleController::class, 'filterVehicle'])->name('vehicle.filter');
    Route::post('/search/vehicle', [VehicleController::class, 'searchVehicle'])->name('vehicle.search');
    Route::post('/handle/driver/change', [VehicleController::class, 'handleDriverChange'])->name('vehicle.change.driver');
    Route::post('/vehicles/deactivate', [VehicleController::class, 'deactivateVehicle'])->name('vehicle.deactivate');

    /**
     * @tags Vehicle Requirements
     */
    Route::get('/get/vehicle/requirements', [VehicleRequirementsController::class, 'getVehicleRequirment'])->name('vehicle.requirements');
    Route::post('/vehicle/requirements/save', [VehicleRequirementsController::class, 'vehicleRequirmentSave'])->name('vehicle.requirement.save');
    Route::post('/vehicle/requirements/edit', [VehicleRequirementsController::class, 'vehicleRequirmentEdit'])->name('vehicle.requirement.edit');
    Route::post('/vehicle/requirements/update', [VehicleRequirementsController::class, 'vehicleRequirmentUpdate'])->name('vehicle.requirement.update');
    Route::post('/delete/vehicle/requirements', [VehicleRequirementsController::class, 'deleteVehicleRequirment'])->name('vehicle.requirement.delete');

    /**
     * @tags Exports
     */
    Route::post('/export/sheduledOrder', [ExportsController::class, 'sheduledOrdersCsv'])->name('exports.scheduled');
    Route::post('/export/orders', [ExportsController::class, 'exportCSV'])->name('exports.csv');

    /**
     * @tags Dispatcher Management
     */
    Route::get('/get/dispatcher', [DispatcherController::class, 'getDispatcher'])->name('dispatcher.list');

    /**
     * @tags Customer Management
     */
    Route::get('/get/customers', [CustomerController::class, 'getCustomers'])->name('customer.list');
    Route::post('/save/customer', [CustomerController::class, 'saveCustomer'])->name('customer.save');
    Route::post('/delete/customer', [CustomerController::class, 'deleteCustomer'])->name('customer.delete');
    Route::post('/update/customer', [CustomerController::class, 'updateCustomer'])->name('customer.update');
    Route::get('/search/customers', [CustomerController::class, 'customerSearch'])->name('customer.search');
    Route::get('/customer/location', [CustomerController::class, 'getCustomerLocation'])->name('customer.locations');

    /**
     * @tags Vehicle Type Management
     */
    Route::get('/get/vehicle/types', [VehicleTypeController::class, 'getVehicleTypes'])->name('vehicle.types');
    Route::post('/vehicle/type/save', [VehicleTypeController::class, 'vehicleTypeSave'])->name('vehicle.type.save');
    Route::post('/vehicle/type/edit', [VehicleTypeController::class, 'vehicleTypeEdit'])->name('vehicle.type.edit');
    Route::post('/vehicle/type/update', [VehicleTypeController::class, 'vehicleTypeUpdate'])->name('vehicle.type.update');
    Route::post('/delete/vehicle/types', [VehicleTypeController::class, 'deleteVehicleTypes'])->name('vehicle.type.delete');

    /**
     * @tags Speed Zone Management
     */
    Route::get('/get/speedzones', [SpeedZoneController::class, 'getAllSpeedZones'])->name('speed.zones');
    Route::post('/speedzone/save', [SpeedZoneController::class, 'saveSpeedZone'])->name('speed.zone.save');
    Route::post('/update/speedzone', [SpeedZoneController::class, 'updateSpeedZone'])->name('speed.zone.update');
    Route::post('/delete/speedzone', [SpeedZoneController::class, 'deleteSpeedZone'])->name('speed.zone.delete');

    /**
     * @tags Capacity Unit Management
     */
    Route::get('/get/capacity/units', [CapacityUnitController::class, 'getCapacityUnits'])->name('capacity.units');
    Route::post('/createorupdate/capacity/units', [CapacityUnitController::class, 'createOrUpdateCapacityUnit'])->name('capacity.unit.create.update');

    /**
     * @tags Operation Duration Management
     */
    Route::get('/get/operation/duration', [OperationDurationController::class, 'getOpertionDuration'])->name('operation.duration');
    Route::post('/createorupdate/operation/duration', [OperationDurationController::class, 'createOrUpdateOpertionDuration'])->name('operation.duration.create.update');

    /**
     * @tags Breaks Management
     */
    Route::get('/get/breaks', [BreaksController::class, 'getBreaks'])->name('breaks');
    Route::post('/createorupdate/breaks', [BreaksController::class, 'createOrUpdateBreaks'])->name('breaks.create.update');

    /**
     * @tags Territory Management
     */
    Route::get('/get/territories', [TerritoriesController::class, 'getAllTerritories'])->name('territories');
    Route::post('/territory/save', [TerritoriesController::class, 'addTerritories'])->name('territories.add');
    Route::post('/update/territory', [TerritoriesController::class, 'updateTerritory'])->name('territories.update');
    Route::post('/delete/territory', [TerritoriesController::class, 'deleteTerritory'])->name('territories.delete');

    /**
     * @tags Planning Preferences Management
     */
    Route::post('/createorupdate/planning', [PlanningPreferencesController::class, 'createOrUpdatePlanning'])->name('planning.preferences.create.update');
    Route::get('/get/planning', [PlanningPreferencesController::class, 'getPlanningPreference'])->name('planning.preferences.get');

    /**
     * @tags Territory Groups Management
     */
    Route::post('/group/save', [TerritoriesGroupController::class, 'saveGroup'])->name('territory.group.save');
    Route::get('/get/groups', [TerritoriesGroupController::class, 'getAllGroup'])->name('territory.groups');
    Route::post('/delete/group', [TerritoriesGroupController::class, 'deleteGroup'])->name('territory.group.delete');
    Route::post('/assign/orders', [TerritoriesController::class, 'assignOrders'])->name('territory.assign.orders');

    /**
     * @tags SMTP Settings Management
     */
    Route::post('/cus-com/smtpEmail', [SmtpController::class, 'createOrUpdate'])->name('smtp.create.update');
    Route::get('/cus-com/getSmtpEmail', [SmtpController::class, 'getSmtpEmail'])->name('smtp.get');
    Route::post('/cus-com/sendEmail', [SmtpController::class, 'sendEmail'])->name('smtp.send');

    /**
     * @tags Execution Preferences Management
     */
    Route::get('/get/execution/preference', [ExecutionPreferenceController::class, 'getExecutionPreference'])->name('execution.preference.get');
    Route::post('/createorupdate/execution/preference', [ExecutionPreferenceController::class, 'createOrUpdateExecutionPrefernces'])->name('execution.preference.create.update');

    /**
     * @tags Order Cancellation Reasons Management
     */
    Route::get('/get/reasons', [OrderCancellationReasonController::class, 'getAllReasons'])->name('order.cancellation.reasons');
    Route::post('/reason/save', [OrderCancellationReasonController::class, 'saveOrderCancelationReason'])->name('order.cancellation.reason.save');
    Route::post('/update/reason', [OrderCancellationReasonController::class, 'updateReason'])->name('order.cancellation.reason.update');
    Route::post('/delete/reason', [OrderCancellationReasonController::class, 'deleteReasons'])->name('order.cancellation.reason.delete');

    /**
     * @tags Order Item Cancellation Reasons Management
     */
    Route::get('/get/order/items', [OrderItemCancellationReasonController::class, 'getAllitems'])->name('order.item.cancellation.reasons');
    Route::post('/order/item/save', [OrderItemCancellationReasonController::class, 'saveOrderItemCancelationReason'])->name('order.item.cancellation.reason.save');
    Route::post('/update/order/item', [OrderItemCancellationReasonController::class, 'updateItem'])->name('order.item.cancellation.reason.update');
    Route::post('/delete/order/item', [OrderItemCancellationReasonController::class, 'deleteItem'])->name('order.item.cancellation.reason.delete');

    /**
     * @tags Execution Notifications Management
     */
    Route::get('/get/notification', [ExecutionNotifyController::class, 'getNotification'])->name('execution.notification.get');
    Route::post('/createorupdate/notification', [ExecutionNotifyController::class, 'createOrUpdateExecutionNotification'])->name('execution.notification.create.update');

    /**
     * @tags Driver Manifest Configuration Management
     */
    Route::get('/get/execution/driver/manifest/configuration', [DriverManifestConfigurationController::class, 'getExecutionDriverManifest'])->name('driver.manifest.get');
    Route::post('/createorupdate/execution/driver/manifest/configuration', [DriverManifestConfigurationController::class, 'createOrUpdateExecutionDriverManifest'])->name('driver.manifest.create.update');

    /**
     * @tags Order Tracking Widget Management
     */
    Route::get('/get/order/tracking/widget', [OrderTrackingWidgetController::class, 'getOrderTrackingWidget'])->name('order.tracking.widget.get');
    Route::post('/createorupdate/order/tracking/widget', [OrderTrackingWidgetController::class, 'createOrUpdateOrderTrackingWidget'])->name('order.tracking.widget.create.update');

    /**
     * @tags Localization Management
     */
    Route::post('/localization', [LocalizationController::class, 'create'])->name('localization.create.update');
    Route::get('/getLocalization', [LocalizationController::class, 'getLocalization'])->name('localization.get');

    /**
     * @tags SMS & Email Format Management
     */
    Route::get('/get/sms/email/format', [SmsEmailFormatController::class, 'getSmsEmailFormat'])->name('sms.email.format.get');
    Route::post('/createorupdate/sms/email/format', [SmsEmailFormatController::class, 'createOrUpdateSmsEmailFormat'])->name('sms.email.format.create.update');

    /**
     * @tags Import Export Settings Management
     */
    Route::get('/get/import/export/setting', [ImportExportSettingController::class, 'getImportExportSetting'])->name('import.export.setting.get');
    Route::post('/createorupdate/import/export/setting', [ImportExportSettingController::class, 'createOrUpdateImportExportSetting'])->name('import.export.setting.create.update');

    /**
     * @tags API Key Management
     */
    Route::get('/get/api-keys', [ApiKeyController::class, 'getApiKeys'])->name('api.keys.get');
    Route::post('/save/api-key', [ApiKeyController::class, 'saveApiKey'])->name('api.key.create.update');
    Route::post('/edit/api-key', [ApiKeyController::class, 'editApiKey'])->name('api.key.edit');
    Route::post('/update/api-key', [ApiKeyController::class, 'updateApiKey'])->name('api.key.update');
    Route::post('/delete/api-key', [ApiKeyController::class, 'deleteApiKey'])->name('api.key.delete');

    /**
     * @tags Company Management
     */
    Route::post('/aboutCompany', [AboutCompanyController::class, 'aboutCompany'])->name('about.company');
    Route::get('/get/about_company', [AboutCompanyController::class, 'getAboutCompany'])->name('about.company.get');

    /**
     * @tags Time Window Management
     */
    Route::post('/createorupdate/timewindow', [TimeWindowController::class, 'createOrUpdateTimeWindow'])->name('time.window.create.update');
    Route::get('/get/timewindow', [TimeWindowController::class, 'getTimeWindow'])->name('time.window.get');

    //These Routes are temporary for testing
    Route::get('/get/roles', [RolesController::class, 'getRoles'])->name('roles.get');
    Route::post('/delete/role', [RolesController::class, 'deleteRole'])->name('role.delete');

    /**
     * @tags Track and Trace Management
     */
    Route::post('/track/operation_environment', [TrackAndTraceController::class, 'operationEnvironment'])->name('operation.environment');
    Route::post('/track/vehicle_track&trace', [TrackAndTraceController::class, 'vehicleTrack'])->name('vehicle.track.trace');
    Route::post('/track/search/orders', [TrackAndTraceController::class, 'searchOrderIntrackAndTrace'])->name('track.search.orders');

    Route::post('artisan', [ArtisanCommandController::class, 'execute']);
    Route::get('/update/driver/usernames', [DriverController::class, 'updateDriverUsernames'])->name('driver.usernames.update');

    /**
     * @tags Driver App Settings
     */
    Route::get('/driver/app/settings', [DriverAppController::class, 'getDriverAppSettings'])->name('driver.app.settings');
    Route::post('/driver/app/settings', [DriverAppController::class, 'updateDriverAppSettings'])->name('driver.app.settings.update');
});


/**
 * Routes protected by the CheckApiKey middleware
 * Share routes with k3 .
 */
Route::middleware(['key-check-middleware', 'App\Http\Middleware\CheckApiKey'])->group(function () {
    // Share routes with k3
    Route::get('/shared/orders', [ForKsystemController::class, 'getRoutes'])->name('shared.orders');
    Route::get('shared/orders/{orderReference}/tracking', [ForKsystemController::class, 'getOrderTracking'])->name('order.tracking');
    Route::get('shared/orders/{orderReference}/pod', [ForKsystemController::class, 'getOrderPod'])->name('order.pod');
    Route::get('shared/orders/{orderReference}/widget', [ForKsystemController::class, 'getOrderWidget'])->name('order.widget');
    Route::get('shared/orders/{orderReference}/attachments', [ForKsystemController::class, 'getOrderAttachments'])->name('order.attachments');

    Route::get('/shared/distribution_centre/{dcReference}/{shiftDate}', [ForKsystemController::class, 'getDistribution'])->name('shared.distribution');
});
