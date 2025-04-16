<?php

namespace App\Http\Controllers;

use App\Models\TerritoriesGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @tags Territory Groups Management
 */
class TerritoriesGroupController extends Controller
{
    /**
     * Create or Update Territory Group
     * 
     * Create a new territory group or update an existing one.
     * 
     * @group Territory Groups Management
     * 
     * @bodyParam id integer nullable The ID of the territory group to update. Example: 1
     * @bodyParam group_name string required The name of the territory group. Example: "North Zone"
     * @bodyParam warehouse_id integer nullable The ID of the warehouse. Example: 1
     * 
     * @response {
     *   "group": {
     *     "id": 1,
     *     "name": "North Zone",
     *     "warehouse_id": 1,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "group_name": ["The group name field is required."]
     *   },
     *   "success": false
     * }
     */
    public function saveGroup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:territories_groups,id',
            'group_name' => 'required|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $territoriesGroup = TerritoriesGroup::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->group_name,
                    'warehouse_id' => $request->warehouse_id
                ]
            );



            return response()->json([
                'group' => $territoriesGroup,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to save territory group',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Get All Territory Groups
     * 
     * Retrieve all territory groups with their associated territories.
     * 
     * @group Territory Groups Management
     * 
     * @queryParam warehouse_id integer nullable The ID of the warehouse to filter groups. Example: 1
     * 
     * @response {
     *   "groups": [{
     *     "id": 1,
     *     "name": "North Zone",
     *     "warehouse_id": 1,
     *     "territories_count": 5,
     *     "territories": [{
     *       "id": 1,
     *       "name": "Territory 1",
     *       "ref_no": "TER001",
     *       "coordinates": "[[51.5074,-0.1278]]",
     *       "radius": "5000",
     *       "drivers": [{
     *         "id": 1,
     *         "name": "John Doe"
     *       }]
     *     }],
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   }],
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "warehouse_id": ["The warehouse id must be a valid warehouse ID."]
     *   },
     *   "success": false
     * }
     */
    public function getAllGroup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'nullable|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $groups = TerritoriesGroup::all();

            return response()->json([
                'groups' => $groups,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch territory groups',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Delete Territory Group
     * 
     * Delete a territory group and its associated territories.
     * 
     * @group Territory Groups Management
     * 
     * @bodyParam id integer required The ID of the territory group to delete. Example: 1
     * 
     * @response {
     *   "group": {
     *     "id": 1,
     *     "name": "North Zone",
     *     "warehouse_id": 1,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Territory group not found",
     *   "success": false
     * }
     */
    public function deleteGroup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:territories_groups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $territoryGroup = TerritoriesGroup::find($request->id);

            if (!$territoryGroup) {
                return response()->json([
                    'message' => 'Territory group not found',
                    'success' => false,
                    'success' => false
                ], 200);
            }

            // Delete associated territories using the relationship
            if ($territoryGroup->territories()->exists()) {
                $territoryGroup->territories()->delete();
            }

            $territoryGroup->delete();



            return response()->json([
                'group' => $territoryGroup,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to delete territory group',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
