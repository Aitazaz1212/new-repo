<?php

namespace App\Http\Controllers;

use App\Models\CapacityUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @tags Capacity Unit Management
 */
class CapacityUnitController extends Controller
{
    /**
     * Get All Capacity Units
     * 
     * Retrieve a list of all capacity units with their configurations.
     * 
     * @group Capacity Unit Management
     * 
     * @response {
     *   "capacityUnits": [{
     *     "id": 1,
     *     "capacity_unit": "kg",
     *     "capacity_decimal_precision": "2",
     *     "capacity_label": "Weight",
     *     "volume_units": "m³",
     *     "volume_label": "Volume",
     *     "volume_decimal_precision": "3",
     *     "enable_capacity_constraint_one": true,
     *     "enable_capacity_constraint_two": false,
     *     "formatted_capacity_label": "Weight (kg)",
     *     "formatted_volume_label": "Volume (m³)",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   }],
     *   "success": true
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch capacity units",
     *   "error": "Database error",
     *   "success": false
     * }
     */
    public function getCapacityUnits(): JsonResponse
    {
        try {
            $capacityUnits = CapacityUnit::orderBy('capacity_label')
                ->get()
                ->each(function ($unit) {
                    $unit->append([
                        'formatted_capacity_label',
                        'formatted_volume_label'
                    ]);
                });

            return response()->json([
                'capacityUnits' => $capacityUnits,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch capacity units',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update Capacity Unit
     * 
     * Create a new capacity unit or update an existing one.
     * 
     * @group Capacity Unit Management
     * 
     * @bodyParam id integer nullable The ID of the capacity unit to update. Example: 1
     * @bodyParam capacity_unit string nullable The unit of capacity measurement. Example: "kg"
     * @bodyParam capacity_decimal_precision integer nullable Number of decimal places for capacity. Example: 2
     * @bodyParam capacity_label string nullable Label for capacity measurement. Example: "Weight"
     * @bodyParam volume_units string nullable The unit of volume measurement. Example: "m³"
     * @bodyParam volume_label string nullable Label for volume measurement. Example: "Volume"
     * @bodyParam volume_decimal_precision integer nullable Number of decimal places for volume. Example: 3
     * @bodyParam enable_capacity_constraint_one boolean nullable Enable first capacity constraint. Example: true
     * @bodyParam enable_capacity_constraint_two boolean nullable Enable second capacity constraint. Example: false
     * 
     * @response {
     *   "capacityUnit": {
     *     "id": 1,
     *     "capacity_unit": "kg",
     *     "capacity_decimal_precision": 2,
     *     "capacity_label": "Weight",
     *     "volume_units": "m³",
     *     "volume_label": "Volume",
     *     "volume_decimal_precision": 3,
     *     "enable_capacity_constraint_one": true,
     *     "enable_capacity_constraint_two": false,
     *     "formatted_capacity_label": "Weight (kg)",
     *     "formatted_volume_label": "Volume (m³)",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "capacity_decimal_precision": ["The capacity decimal precision must be an integer."]
     *   },
     *   "success": false
     * }
     */
    public function createOrUpdateCapacityUnit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer|exists:capacity_units,id',
            'capacity_unit' => 'nullable|string|max:255',
            'capacity_decimal_precision' => 'nullable|integer|min:0|max:10',
            'capacity_label' => 'nullable|string|max:255',
            'volume_units' => 'nullable|string|max:255',
            'volume_label' => 'nullable|string|max:255',
            'volume_decimal_precision' => 'nullable|integer|min:0|max:10',
            'enable_capacity_constraint_one' => 'nullable|boolean',
            'enable_capacity_constraint_two' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $capacityUnit = CapacityUnit::updateOrCreate(
                ['id' => $request->id],
                $request->only([
                    'capacity_unit',
                    'capacity_decimal_precision',
                    'capacity_label',
                    'volume_units',
                    'volume_label',
                    'volume_decimal_precision',
                    'enable_capacity_constraint_one',
                    'enable_capacity_constraint_two'
                ])
            );

            $capacityUnit->append([
                'formatted_capacity_label',
                'formatted_volume_label'
            ]);



            return response()->json([
                'capacityUnit' => $capacityUnit,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to save capacity unit',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
