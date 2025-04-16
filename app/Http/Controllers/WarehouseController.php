<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Models\WarehouseDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    /**
     * Get Centers
     * 
     * Retrieves a list of warehouse centers along with their associated dispatchers.
     * 
     * @authenticated
     * 
     * @queryParam id integer required The ID of the user requesting the centers. Example: 1
     * 
     * @response 200 {
     *     "centers": [
     *         {
     *             "id": 1,
     *             "name": "Main Warehouse",
     *             "dispatcher": {
     *                 "id": 1,
     *                 "warehouse_id": 1,
     *                 "dispatcher_id": 2,
     *                 "dispacherName": "John Doe"
     *             }
     *         },
     *         {
     *             "id": 2,
     *             "name": "Secondary Warehouse",
     *             "dispatcher": {
     *                 "id": 2,
     *                 "warehouse_id": 2,
     *                 "dispatcher_id": 3,
     *                 "dispacherName": "Jane Smith"
     *             }
     *         }
     *     ],
     *     "success": true
     * }
     */
    public function getCenters(Request $request)
    {
        $centers = Warehouse::with(['dispatchers' => function ($query) {
            $query->leftJoin('users', 'warehouse_dispatchers.dispatcher_id', '=', 'users.id')
                ->select(
                    'warehouse_dispatchers.id',
                    'warehouse_dispatchers.warehouse_id',
                    'warehouse_dispatchers.dispatcher_id',
                    'users.name as dispacherName'
                );
        }])->get();

        return response()->json(['centers' => $centers, 'success' => true]);
    }

    /**
     * Get All Centers
     * 
     * Retrieves a list of all warehouse centers.
     * 
     * @group Warehouse Management
     * 
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "name": "Main Warehouse",
     *     "address": "123 Main St",
     *     "city": "Example City",
     *     "state": "EX",
     *     "postal_code": "12345",
     *     "country": "US",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }]
     * }
     * 
     * @response 500 {
     *   "error": "Something went wrong"
     * }
     */
    public function getAllCenters(Request $request)
    {
        try {
            $warehouses = Warehouse::all();

            return response()->json($warehouses);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Save Center
     * 
     * Creates a new warehouse center with dispatcher assignments.
     * 
     * @group Warehouse Management
     * 
     * @bodyParam name_of_warehouse string required Name of the warehouse. Example: Main Warehouse
     * @bodyParam ref_number integer required Reference number. Example: 1001
     * @bodyParam location_of_warehouse string required Location description. Example: 123 Main Street
     * @bodyParam latitude string required when location_of_warehouse is present Latitude coordinate. Example: 51.5074
     * @bodyParam longitude string required when location_of_warehouse is present Longitude coordinate. Example: -0.1278
     * @bodyParam postalCode string optional Postal code. Example: 12345
     * @bodyParam address_prefix string optional Address prefix. Example: Unit
     * @bodyParam phone string optional Contact phone number. Example: +1234567890
     * @bodyParam driving_time_correction_factor string optional Driving time correction. Example: 1.2
     * @bodyParam daily_driving_limt string optional Daily driving limit. Example: 8
     * @bodyParam duty_time_limt string optional Duty time limit. Example: 10
     * @bodyParam run_duration_limt string optional Run duration limit. Example: 12
     * @bodyParam collection_after_deliveries string optional Collection after deliveries. Example: yes
     * @bodyParam start_fo_day_location string optional Start of day location. Example: Warehouse
     * @bodyParam end_of_day_location string optional End of day location. Example: Warehouse
     * @bodyParam visitDistributionCenter string optional Visit distribution center. Example: yes
     * @bodyParam vistDistributionLocation string optional Visit distribution location. Example: yes
     * @bodyParam dispatcher array optional Array of dispatcher IDs. Example: [1, 2]
     * @bodyParam sunday array optional Sunday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam monday array optional Monday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam tuesday array optional Tuesday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam wednesday array optional Wednesday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam thursday array optional Thursday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam friday array optional Friday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam saturday array optional Saturday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam holidays array optional Holiday schedule. Example: {"start": "09:00", "end": "17:00"}
     * 
     * @response {
     *   "center": {
     *     "id": 1,
     *     "name_of_warehouse": "Main Warehouse",
     *     "location_of_warehouse": "123 Main Street",
     *     "dispatcher": [{
     *       "id": 1,
     *       "name": "John Doe"
     *     }]
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "error": {
     *     "name_of_warehouse": ["The name of warehouse field is required."],
     *     "ref_number": ["The ref number field is required."],
     *     "location_of_warehouse": ["The location of warehouse field is required."]
     *   }
     * }
     * 
     * @response 500 {
     *   "error": "Something went wrong"
     * }
     */
    public function saveCenter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_of_warehouse' => 'required|max:255',
            'ref_number' => 'required|string',
            'location_of_warehouse' => 'required',
        ]);

        $validator->sometimes(['latitude', 'longitude'], 'required', function ($input) {
            return !empty($input->location_of_warehouse);
        });

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {


            $center = new Warehouse();

            // Basic attributes that can be mass assigned
            $attributes = [
                'name_of_warehouse',
                'location_of_warehouse',
                'longitude',
                'latitude',
                'postalCode',
                'ref_number',
                'address_prefix',
                'phone',
                'driving_time_correction_factor',
                'daily_driving_limt',
                'duty_time_limt',
                'run_duration_limt',
                'collection_after_deliveries',
                'start_fo_day_location',
                'end_of_day_location',
                'visitDistributionCenter',
                'vistDistributionLocation'
            ];

            foreach ($attributes as $attribute) {
                if ($request->has($attribute)) {
                    $center->$attribute = $request->$attribute;
                }
            }

            // Handle weekly time windows
            $weeklyTimeWindows = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'holidays'];
            foreach ($weeklyTimeWindows as $timeWindow) {
                if ($request->has($timeWindow)) {
                    $center->$timeWindow = json_encode($request->$timeWindow);
                }
            }

            $center->save();

            // Handle dispatcher assignments
            if ($request->has('dispatcher')) {
                $dispatchers = is_array($request->dispatcher) ? $request->dispatcher : [$request->dispatcher];

                foreach ($dispatchers as $dispatcherId) {
                    WarehouseDispatcher::create([
                        'warehouse_id' => $center->id,
                        'dispatcher_id' => $dispatcherId
                    ]);
                }
            }



            $warehouseWithDispatcher = Warehouse::with('dispatchers')->find($center->id);
            return response()->json([
                'center' => $warehouseWithDispatcher,
                'success' => true
            ]);
        } catch (\Exception $e) {

            return response()->json(['error' => 'Something went wrong', 'success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Center
     * 
     * Updates an existing warehouse center with dispatcher assignments.
     * 
     * @group Warehouse Management
     * 
     * @bodyParam id integer required The ID of the warehouse to update. Example: 1
     * @bodyParam name_of_warehouse string optional Name of the warehouse. Example: Main Warehouse
     * @bodyParam location_of_warehouse string optional Location description. Example: 123 Main Street
     * @bodyParam latitude string optional Latitude coordinate. Example: 51.5074
     * @bodyParam longitude string optional Longitude coordinate. Example: -0.1278
     * @bodyParam postalCode string optional Postal code. Example: 12345
     * @bodyParam ref_number integer optional Reference number. Example: 1001
     * @bodyParam address_prefix string optional Address prefix. Example: Unit
     * @bodyParam phone string optional Contact phone number. Example: +1234567890
     * @bodyParam driving_time_correction_factor string optional Driving time correction. Example: 1.2
     * @bodyParam daily_driving_limt string optional Daily driving limit. Example: 8
     * @bodyParam duty_time_limt string optional Duty time limit. Example: 10
     * @bodyParam run_duration_limt string optional Run duration limit. Example: 12
     * @bodyParam collection_after_deliveries string optional Collection after deliveries. Example: yes
     * @bodyParam start_fo_day_location string optional Start of day location. Example: Warehouse
     * @bodyParam end_of_day_location string optional End of day location. Example: Warehouse
     * @bodyParam visitDistributionCenter string optional Visit distribution center. Example: yes
     * @bodyParam vistDistributionLocation string optional Visit distribution location. Example: yes
     * @bodyParam dispatcher array optional Array of dispatcher IDs. Example: [1, 2]
     * @bodyParam sunday array optional Sunday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam monday array optional Monday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam tuesday array optional Tuesday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam wednesday array optional Wednesday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam thursday array optional Thursday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam friday array optional Friday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam saturday array optional Saturday schedule. Example: {"start": "09:00", "end": "17:00"}
     * @bodyParam holidays array optional Holiday schedule. Example: {"start": "09:00", "end": "17:00"}
     * 
     * @response {
     *   "center": {
     *     "id": 1,
     *     "name_of_warehouse": "Updated Warehouse",
     *     "location_of_warehouse": "123 Main Street",
     *     "dispatcher": [{
     *       "id": 1,
     *       "name": "John Doe"
     *     }]
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "error": "Distribution Center not found"
     * }
     * 
     * @response 500 {
     *   "error": "Something went wrong"
     * }
     */
    public function updateCenter(Request $request): JsonResponse
    {
        try {


            $center = Warehouse::find($request->id);
            if (!$center) {
                return response()->json(['error' => 'Distribution Center not found', 'success' => false], 200);
            }

            // Update basic attributes
            $fieldsToUpdate = [
                'name_of_warehouse',
                'location_of_warehouse',
                'longitude',
                'latitude',
                'postalCode',
                'ref_number',
                'address_prefix',
                'phone',
                'driving_time_correction_factor',
                'daily_driving_limt',
                'duty_time_limt',
                'run_duration_limt',
                'collection_after_deliveries',
                'start_fo_day_location',
                'end_of_day_location',
                'visitDistributionCenter',
                'vistDistributionLocation'
            ];

            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field)) {
                    $center->$field = $request->$field;
                }
            }

            // Update weekly time windows
            foreach (['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'holidays'] as $day) {
                if ($request->has($day)) {
                    $center->$day = json_encode($request->$day);
                }
            }

            $center->save();

            // Update dispatcher assignments
            if ($request->has('dispatcher')) {
                $newDispatchers = (array) $request->dispatcher;

                // Delete removed dispatchers
                WarehouseDispatcher::where('warehouse_id', $center->id)
                    ->whereNotIn('dispatcher_id', $newDispatchers)
                    ->delete();

                // Add new dispatchers
                $existingDispatchers = WarehouseDispatcher::where('warehouse_id', $center->id)
                    ->pluck('dispatcher_id')
                    ->toArray();

                $dispatchersToAdd = array_diff($newDispatchers, $existingDispatchers);
                foreach ($dispatchersToAdd as $dispatcherId) {
                    WarehouseDispatcher::create([
                        'warehouse_id' => $center->id,
                        'dispatcher_id' => $dispatcherId
                    ]);
                }
            }



            return response()->json([
                'center' => $center->load('dispatchers.dispatcher'),
                'success' => true
            ]);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete Centers
     * 
     * Deletes one or more warehouse centers and their associated dispatchers.
     * 
     * @group Warehouse Management
     * 
     * @bodyParam array required Array of warehouse IDs to delete. Example: [1, 2]
     * 
     * @response {
     *   "message": "Warehouses deleted.",
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Warehouses do not exist.",
     *   "success": true
     * }
     * 
     * @response 500 {
     *   "error": "Something went wrong"
     * }
     */
    public function deleteCenter(Request $request): JsonResponse
    {
        try {


            $centers = Warehouse::whereIn('id', $request->all())->get();

            if ($centers->isEmpty()) {
                return response()->json([
                    'message' => 'Warehouses do not exist.',
                    'success' => false
                ], 200);
            }

            // Delete associated dispatchers and warehouses
            foreach ($centers as $center) {
                // Using relationship to delete dispatchers
                $center->dispatchers()->delete();
                $center->delete();
            }



            return response()->json([
                'message' => 'Warehouses deleted.',
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Something went wrong'
            ], 500);
        }
    }
}
