<?php

namespace App\Http\Controllers;

use App\Models\VehicleType;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

/**
 * @tags Vehicle Type Management
 */
class VehicleTypeController extends Controller
{
    /**
     * Get Vehicle Types
     * 
     * Get a list of all vehicle types with their details.
     * 
     * @group Vehicle Type Management
     * 
     * @response {
     *   "vehicleTypes": [{
     *     "id": 1,
     *     "name": "Van",
     *     "ref_no": "VAN001",
     *     "working_time_before_break": "240",
     *     "routing_mode": "fastest",
     *     "avoid_urban_areas": "1",
     *     "avoid_london_ultra_low_emission_zone": "1",
     *     "weight": "3500",
     *     "height": "2.5",
     *     "width": "2.0",
     *     "length": "5.0",
     *     "axle_load": "1500",
     *     "dimensions": "H: 2.5, W: 2.0, L: 5.0",
     *     "vehicles": [{
     *       "id": 1,
     *       "name": "Vehicle 1"
     *     }]
     *   }],
     *   "success": true
     * }
     */
    public function getVehicleTypes(Request $request): JsonResponse
    {
        try {
            $vehicleTypes = VehicleType::all();

            return response()->json([
                'vehicleTypes' => $vehicleTypes,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch vehicle types',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Save Vehicle Type
     * 
     * Create or update a vehicle type with its details.
     * 
     * @group Vehicle Type Management
     * 
     * @bodyParam name string nullable The name of the vehicle type. Example: "Van"
     * @bodyParam ref_no string nullable Unique reference number. Example: "VAN001"
     * @bodyParam working_time_before_break string nullable Working time before break in minutes. Example: "240"
     * @bodyParam routing_mode string nullable Routing mode (fastest, shortest, etc). Example: "fastest"
     * @bodyParam avoid_urban_areas string nullable Whether to avoid urban areas. Example: "1"
     * @bodyParam avoid_london_ultra_low_emission_zone string nullable Whether to avoid ULEZ. Example: "1"
     * @bodyParam weight string nullable Maximum weight in kg. Example: "3500"
     * @bodyParam height string nullable Height in meters. Example: "2.5"
     * @bodyParam width string nullable Width in meters. Example: "2.0"
     * @bodyParam length string nullable Length in meters. Example: "5.0"
     * @bodyParam axle_load string nullable Maximum axle load in kg. Example: "1500"
     * 
     * @response {
     *   "vehicleTypes": {
     *     "id": 1,
     *     "name": "Van",
     *     "ref_no": "VAN001",
     *     "working_time_before_break": "240",
     *     "routing_mode": "fastest",
     *     "avoid_urban_areas": "1",
     *     "avoid_london_ultra_low_emission_zone": "1",
     *     "weight": "3500",
     *     "height": "2.5",
     *     "width": "2.0",
     *     "length": "5.0",
     *     "axle_load": "1500"
     *   },
     *   "success": true
     * }
     */
    public function vehicleTypeSave(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'ref_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('vehicle_types', 'ref_no')->ignore($request->id)
            ],
            'working_time_before_break' => 'nullable|string|max:255',
            'routing_mode' => 'nullable|string|max:255',
            'avoid_urban_areas' => 'nullable|boolean',
            'avoid_london_ultra_low_emission_zone' => 'nullable|boolean',
            'weight' => 'nullable',
            'height' => 'nullable',
            'width' => 'nullable',
            'length' => 'nullable',
            'axle_load' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $vehicleType = VehicleType::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
                    'ref_no' => $request->ref_no,
                    'working_time_before_break' => $request->working_time_before_break,
                    'routing_mode' => $request->routing_mode,
                    'avoid_urban_areas' => $request->avoid_urban_areas,
                    'avoid_london_ultra_low_emission_zone' => $request->avoid_london_ultra_low_emission_zone,
                    'weight' => $request->weight,
                    'height' => $request->height,
                    'width' => $request->width,
                    'length' => $request->length,
                    'axle_load' => $request->axle_load
                ]
            );



            return response()->json([
                'vehicleTypes' => $vehicleType,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to save vehicle type',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Edit Vehicle Type
     * 
     * Get vehicle type details by ID.
     * 
     * @group Vehicle Type Management
     * 
     * @bodyParam id integer required The ID of the vehicle type. Example: 1
     * 
     * @response {
     *   "vehicleType": {
     *     "id": 1,
     *     "name": "Van",
     *     "ref_no": "VAN001",
     *     "working_time_before_break": "240",
     *     "routing_mode": "fastest",
     *     "avoid_urban_areas": "1",
     *     "avoid_london_ultra_low_emission_zone": "1",
     *     "weight": "3500",
     *     "height": "2.5",
     *     "width": "2.0",
     *     "length": "5.0",
     *     "axle_load": "1500",
     *     "dimensions": "H: 2.5, W: 2.0, L: 5.0"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Vehicle type not found",
     *   "success": false
     * }
     */
    public function vehicleTypeEdit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:vehicle_types,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid vehicle type ID',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $vehicleType = VehicleType::findOrFail($request->id);

            return response()->json([
                'vehicleType' => $vehicleType,
                'success' => true
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Vehicle type not found',
                'success' => false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch vehicle type',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update Vehicle Type
     * 
     * Update an existing vehicle type with new details.
     * 
     * @deprecated Use vehicleTypeSave() instead
     * @group Vehicle Type Management
     * 
     * @bodyParam id integer required The ID of the vehicle type. Example: 1
     * @bodyParam name string nullable The name of the vehicle type. Example: "Van"
     * @bodyParam ref_no string nullable Unique reference number. Example: "VAN001"
     * @bodyParam working_time_before_break string nullable Working time before break. Example: "240"
     * @bodyParam routing_mode string nullable Routing mode (fastest, shortest). Example: "fastest"
     * @bodyParam avoid_urban_areas string nullable Whether to avoid urban areas. Example: "1"
     * @bodyParam avoid_london_ultra_low_emission_zone string nullable Whether to avoid ULEZ. Example: "1"
     * @bodyParam weight string nullable Maximum weight in kg. Example: "3500"
     * @bodyParam height string nullable Height in meters. Example: "2.5"
     * @bodyParam width string nullable Width in meters. Example: "2.0"
     * @bodyParam length string nullable Length in meters. Example: "5.0"
     * @bodyParam axle_load string nullable Maximum axle load in kg. Example: "1500"
     * 
     * @response {
     *   "vehicleType": {
     *     "id": 1,
     *     "name": "Van",
     *     "ref_no": "VAN001",
     *     "working_time_before_break": "240",
     *     "routing_mode": "fastest",
     *     "avoid_urban_areas": "1",
     *     "avoid_london_ultra_low_emission_zone": "1",
     *     "weight": "3500",
     *     "height": "2.5",
     *     "width": "2.0",
     *     "length": "5.0",
     *     "axle_load": "1500"
     *   },
     *   "success": true
     * }
     */
    public function vehicleTypeUpdate(Request $request): JsonResponse
    {
        //Method vehicleTypeUpdate is deprecated. Use vehicleTypeSave instead.

        if (!$request->id) {
            return response()->json([
                'message' => 'Vehicle type ID is required',
                'success' => false
            ], 422);
        }

        // Forward the request to vehicleTypeSave
        return $this->vehicleTypeSave($request);
    }

    /**
     * Delete Vehicle Types
     * 
     * Delete one or multiple vehicle types by their IDs.
     * 
     * @group Vehicle Type Management
     * 
     * @bodyParam id array required Array of vehicle type IDs to delete. Example: [1, 2]
     * 
     * @response {
     *   "message": "The selected vehicle types are deleted",
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Invalid vehicle type IDs",
     *   "errors": {
     *     "id": ["The id field is required."]
     *   },
     *   "success": false
     * }
     * 
     * @response 404 {
     *   "message": "One or more vehicle types not found",
     *   "success": false
     * }
     */
    public function deleteVehicleTypes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|array',
            'id.*' => 'required|integer|exists:vehicle_types,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid vehicle type IDs',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            // Check for any related vehicles before deletion
            $hasRelatedVehicles = Vehicle::whereIn('vehicle_type', $request->id)->exists();

            if ($hasRelatedVehicles) {
                return response()->json([
                    'message' => 'Cannot delete vehicle types that are in use',
                    'success' => false
                ], 422);
            }

            $deleted = VehicleType::whereIn('id', $request->id)->delete();

            if (!$deleted) {
                throw new ModelNotFoundException('One or more vehicle types not found');
            }



            return response()->json([
                'message' => 'The selected vehicle types are deleted',
                'success' => true
            ], 200);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to delete vehicle types',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
