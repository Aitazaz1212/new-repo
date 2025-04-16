<?php

namespace App\Http\Controllers;

use App\Models\Territory;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @tags Territory Management
 */
class TerritoriesController extends Controller
{
    /**
     * Get All Territories
     *
     * Retrieve all territories for a specific warehouse.
     *
     * @group Territory Management
     *
     * @queryParam warehouse_id integer nullable The ID of the warehouse to filter territories. Example: 1
     *
     * @response {
     *   "territories": [{
     *     "id": 1,
     *     "name": "North Zone",
     *     "ref_no": "TER001",
     *     "coordinates": "[[51.5074,-0.1278],[51.5074,-0.1278]]",
     *     "radius": "5000",
     *     "warehouse_id": "1",
     *     "color": "#FF0000",
     *     "group_id": "1",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z",
     *     "drivers": [{
     *       "id": 1,
     *       "name": "John Doe"
     *     }]
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
    public function getAllTerritories(Request $request): JsonResponse
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
            if ($request->warehouse_id === null) {
                // Fetch all territory
                $territory = Territory::all();
            } else {
                // Fetch  territories base on warehouse
                $warehouseId = $request->warehouse_id;
                $territory = Territory::where('warehouse_id', $warehouseId)
                    ->get();
            }


            return response()->json([
                'territories' => $territory,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch territories',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create Territory
     *
     * Create a new territory with specified configuration.
     *
     * @group Territory Management
     *
     * @bodyParam name string required The name of the territory. Example: "North Zone"
     * @bodyParam ref_no string required Reference number for the territory. Example: "TER001"
     * @bodyParam coordinates string required JSON array of coordinate pairs. Example: "[[51.5074,-0.1278],[51.5074,-0.1278]]"
     * @bodyParam radius string required Radius of the territory in meters. Example: "5000"
     * @bodyParam warehouse_id string nullable ID of the associated warehouse. Example: "1"
     * @bodyParam color string nullable Color code for the territory. Example: "#FF0000"
     * @bodyParam group_id string nullable ID of the territory group. Example: "1"
     *
     * @response {
     *   "territory": {
     *     "id": 1,
     *     "name": "North Zone",
     *     "ref_no": "TER001",
     *     "coordinates": "[[51.5074,-0.1278],[51.5074,-0.1278]]",
     *     "radius": "5000",
     *     "warehouse_id": "1",
     *     "color": "#FF0000",
     *     "group_id": "1",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   },
     *   "success": false
     * }
     */
    public function addTerritories(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'ref_no' => 'required|string|max:255',
            'coordinates' => 'required|array',
            'radius' => 'nullable|string',
            'warehouse_id' => 'nullable|string|exists:warehouses,id',
            'color' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            ],
            'group_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $territory = Territory::create([
                'name' => $request->name,
                'ref_no' => $request->ref_no,
                'coordinates' => json_encode($request->coordinates),
                'radius' => $request->radius,
                'warehouse_id' => $request->warehouse_id,
                'color' => $request->color,
                'group_id' => $request->group_id
            ]);



            return response()->json([
                'territory' => $territory,
                'success' => true
            ], 201);
        } catch (\Exception $e) {

            Log::error('Failed to create territory: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create territory',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update Territory
     *
     * Update an existing territory configuration.
     *
     * @group Territory Management
     *
     * @bodyParam id integer required The ID of the territory to update. Example: 1
     * @bodyParam name string required The name of the territory. Example: "North Zone"
     * @bodyParam ref_no string required Reference number for the territory. Example: "TER001"
     * @bodyParam coordinates array required Array of coordinate pairs. Example: [[51.5074,-0.1278],[51.5074,-0.1278]]
     * @bodyParam radius string required Radius of the territory in meters. Example: "5000"
     * @bodyParam warehouse_id string nullable ID of the associated warehouse. Example: "1"
     * @bodyParam color string nullable Color code for the territory. Example: "#FF0000"
     * @bodyParam group_id string nullable ID of the territory group. Example: "1"
     *
     * @response {
     *   "territory": {
     *     "id": 1,
     *     "name": "North Zone",
     *     "ref_no": "TER001",
     *     "coordinates": "[[51.5074,-0.1278],[51.5074,-0.1278]]",
     *     "radius": "5000",
     *     "warehouse_id": "1",
     *     "color": "#FF0000",
     *     "group_id": "1",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "Territory not found",
     *   "success": false
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   },
     *   "success": false
     * }
     */
    public function updateTerritory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:territories,id',
            'name' => 'required|string|max:255',
            'ref_no' => 'required|string|max:255',
            'coordinates' => 'nullable|array',
            'radius' => 'nullable|string',
            'warehouse_id' => 'nullable|string|exists:warehouses,id',
            'color' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            ],
            'group_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $territory = Territory::find($request->id);

            if (!$territory) {
                return response()->json([
                    'message' => 'Territory not found',
                    'success' => false,
                ], 200);
            }

            $territory->name = $request->name;
            $territory->ref_no = $request->ref_no;
            $territory->warehouse_id = $request->warehouse_id;

            if (count($request->coordinates) > 0) {
                $territory->coordinates = json_encode($request->coordinates);
            }

            $territory->radius = $request->radius;
            $territory->color = $request->color;
            $territory->group_id = $request->group_id;

            $territory->save();



            return response()->json([
                'territory' => $territory,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to update territory',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Delete Territories
     *
     * Delete one or multiple territories.
     *
     * @group Territory Management
     *
     * @bodyParam id array required Array of territory IDs to delete. Example: [1, 2, 3]
     *
     * @response {
     *   "territories": [{
     *     "id": 1,
     *     "name": "North Zone",
     *     "ref_no": "TER001",
     *     "coordinates": "[[51.5074,-0.1278],[51.5074,-0.1278]]",
     *     "radius": "5000",
     *     "warehouse_id": "1",
     *     "color": "#FF0000",
     *     "group_id": "1",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   }],
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "id": ["The id field is required and must be an array."]
     *   },
     *   "success": false
     * }
     */
    public function deleteTerritory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|array',
            'id.*' => 'required|exists:territories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $deletedTerritories = [];
            foreach ($request->id as $territoryId) {
                $territory = Territory::find($territoryId);
                if ($territory) {
                    $territory->delete();
                    $deletedTerritories[] = $territory;
                }
            }



            return response()->json([
                'territories' => $deletedTerritories,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            Log::error('Failed to delete territories: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete territories',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Assign Orders to Territories
     *
     * Assign multiple orders to specified territories.
     *
     * @group Territory Management
     *
     * @bodyParam territires_id array required Array of territory IDs. Example: [1, 2, 3]
     * @bodyParam warehouseid integer required The warehouse ID. Example: 1
     *
     * @response {
     *   "message": "Orders assigned successfully",
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "territires_id": ["The territories id field is required."]
     *   },
     *   "success": false
     * }
     *
     * @response 404 {
     *   "message": "No orders found for the specified warehouse",
     *   "success": false
     * }
     */
    public function assignOrders(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'territires_id' => 'required|array',
                'territires_id.*' => 'required|exists:territories,id',
                'warehouseid' => 'required|exists:warehouses,id'
            ]);



            $orders = Order::where('warehouse_id', $validated['warehouseid'])->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'No orders found for the specified warehouse',
                    'success' => false,
                ], 200);
            }

            foreach ($orders as $order) {
                $order->territory_id = json_encode($validated['territires_id']);
                $order->save();
            }



            return response()->json([
                'message' => 'Orders assigned successfully',
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to assign orders to territories',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
