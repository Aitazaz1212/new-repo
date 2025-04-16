<?php

namespace App\Http\Controllers;

use App\Models\VehicleRequirements;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Vehicle;
use App\Models\VehicleRequirement;

/**
 * @tags Vehicle Requirements Management
 */
class VehicleRequirementsController extends Controller
{
    /**
     * Get Vehicle Requirements
     * 
     * Get a list of all vehicle requirements with their details.
     * 
     * @group Vehicle Requirements
     * 
     * @response {
     *   "vehicleRequirments": [{
     *     "id": 1,
     *     "name": "Refrigerated",
     *     "ref_no": "REF001",
     *     "working_time_before_break": 240,
     *     "incompatible_vehicle_requirements": [2, 3],
     *     "vehicles": [{
     *       "id": 1,
     *       "name": "Vehicle 1"
     *     }]
     *   }],
     *   "success": true
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch vehicle requirements",
     *   "error": "Error message",
     *   "success": false
     * }
     */
    public function getVehicleRequirment(Request $request): JsonResponse
    {
        try {
            $vehicleRequirment = VehicleRequirement::all();

            return response()->json([
                'vehicleRequirments' => $vehicleRequirment,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch vehicle requirements',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Save Vehicle Requirement
     * 
     * Create or update a vehicle requirement with its details.
     * 
     * @group Vehicle Requirements
     * 
     * @bodyParam name string nullable The name of the requirement. Example: "Refrigerated"
     * @bodyParam ref_no string nullable Unique reference number. Example: "REF001"
     * @bodyParam working_time_before_break string nullable Working time before break. Example: "240"
     * @bodyParam incompatible_vehicle_requirements string nullable Comma-separated IDs of incompatible requirements. Example: "1,2,3"
     * 
     * @response {
     *   "vehicleRequirments": {
     *     "id": 1,
     *     "name": "Refrigerated",
     *     "ref_no": "REF001",
     *     "working_time_before_break": "240",
     *     "incompatible_vehicle_requirements": "1,2,3",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "ref_no": ["The reference number must be unique."]
     *   },
     *   "success": false
     * }
     */
    public function vehicleRequirmentSave(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'ref_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('vehicle_requirements', 'ref_no')->ignore($request->id)
            ],
            'working_time_before_break' => 'nullable|string|max:255',
            'incompatible_vehicle_requirements' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $vehicleRequirment = VehicleRequirements::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
                    'ref_no' => $request->ref_no,
                    'working_time_before_break' => $request->working_time_before_break,
                    'incompatible_vehicle_requirements' => $request->incompatible_vehicle_requirements
                ]
            );



            return response()->json([
                'vehicleRequirments' => $vehicleRequirment,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to save vehicle requirement',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Edit Vehicle Requirement
     * 
     * Get vehicle requirement details by ID.
     * 
     * @group Vehicle Requirements
     * 
     * @bodyParam id integer required The ID of the vehicle requirement. Example: 1
     * 
     * @response {
     *   "vehicleRequirments": {
     *     "id": 1,
     *     "name": "Refrigerated",
     *     "ref_no": "REF001",
     *     "working_time_before_break": "240",
     *     "incompatible_vehicle_requirements": "1,2,3",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Vehicle requirement not found",
     *   "success": false
     * }
     */
    public function vehicleRequirmentEdit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:vehicle_requirements,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid vehicle requirement ID',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $vehicleRequirment = VehicleRequirements::findOrFail($request->id);

            return response()->json([
                'vehicleRequirments' => $vehicleRequirment,
                'success' => true
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Vehicle requirement not found',
                'success' => false,
                'success' => false
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch vehicle requirement',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update Vehicle Requirement
     * 
     * Update an existing vehicle requirement.
     * 
     * @deprecated Use vehicleRequirmentSave() in VehicleRequirementsController instead 
     * @group Vehicle Requirements
     * 
     * @bodyParam id integer required The ID of the vehicle requirement. Example: 1
     * @bodyParam name string nullable The name of the requirement. Example: "Refrigerated"
     * @bodyParam ref_no string nullable Unique reference number. Example: "REF001"
     * @bodyParam working_time_before_break string nullable Working time before break. Example: "240"
     * @bodyParam incompatible_vehicle_requirements string nullable Comma-separated IDs of incompatible requirements. Example: "1,2,3"
     * 
     * @response {
     *   "vehicleRequirments": {
     *     "id": 1,
     *     "name": "Refrigerated",
     *     "ref_no": "REF001",
     *     "working_time_before_break": "240",
     *     "incompatible_vehicle_requirements": "1,2,3"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Vehicle requirement not found",
     *   "success": false
     * }
     */
    public function vehicleRequirmentUpdate(Request $request): JsonResponse
    {
        //Method vehicleRequirmentUpdate is deprecated. Use vehicleRequirmentSave instead

        if (!$request->id) {
            return response()->json([
                'message' => 'Vehicle requirement ID is required',
                'success' => false
            ], 422);
        }

        // Forward the request to saveVehicle
        return $this->vehicleRequirmentSave($request);
    }

    /**
     * Delete Vehicle Requirements
     * 
     * Delete one or multiple vehicle requirements by their IDs.
     * 
     * @group Vehicle Requirements
     * 
     * @bodyParam id array required Array of vehicle requirement IDs to delete. Example: [1, 2]
     * 
     * @response {
     *   "message": "The selected vehicle requirements are deleted",
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Invalid vehicle requirement IDs",
     *   "errors": {
     *     "id": ["The id field is required."]
     *   },
     *   "success": false
     * }
     * 
     * @response 404 {
     *   "message": "One or more vehicle requirements not found",
     *   "success": false
     * }
     */
    public function deleteVehicleRequirment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|array',
            'id.*' => 'required|integer|exists:vehicle_requirements,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid vehicle requirement IDs',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            // Check for any related vehicles before deletion
            $hasRelatedVehicles = Vehicle::whereIn('supported_vehicle', $request->id)->exists();

            if ($hasRelatedVehicles) {
                return response()->json([
                    'message' => 'Cannot delete vehicle requirements that are in use',
                    'success' => false
                ], 422);
            }

            $deleted = VehicleRequirements::whereIn('id', $request->id)->delete();

            if (!$deleted) {
                throw new ModelNotFoundException('One or more vehicle requirements not found');
            }



            return response()->json([
                'message' => 'The selected vehicle requirements are deleted',
                'success' => true
            ], 200);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
                'success' => false
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to delete vehicle requirements',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
