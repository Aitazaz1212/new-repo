<?php

namespace App\Http\Controllers;

use App\Models\SpeedZone;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * @tags Speed Zone Management
 */
class SpeedZoneController extends Controller
{
    /**
     * Get All Speed Zones
     * 
     * Retrieve a list of all speed zones with their details.
     * 
     * @group Speed Zone Management
     * 
     * @response {
     *   "speedZones": [{
     *     "id": 1,
     *     "name": "City Center",
     *     "coordinates": "[[51.5074, -0.1278], [51.5075, -0.1279]]",
     *     "radius": "500",
     *     "speed_correction_error": "10",
     *     "color": "#FF0000",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   }],
     *   "success": true
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch speed zones",
     *   "error": "Database connection error",
     *   "success": false
     * }
     */
    public function getAllSpeedZones(Request $request): JsonResponse
    {
        try {
            $speedZones = SpeedZone::orderBy('name')
                ->get();

            return response()->json([
                'speedZones' => $speedZones,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch speed zones',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Save Speed Zone
     * 
     * Create or update a speed zone with its details.
     * 
     * @group Speed Zone Management
     * 
     * @bodyParam name string required The name of the speed zone. Example: "City Center"
     * @bodyParam coordinates array required Array of coordinate pairs. Example: [[51.5074, -0.1278], [51.5075, -0.1279]]
     * @bodyParam radius string required The radius of the speed zone in meters. Example: "500"
     * @bodyParam speed_correction_error string nullable The speed correction error percentage. Example: "10"
     * @bodyParam color string nullable The color code for the zone. Example: "#FF0000"
     * 
     * @response {
     *   "speedZone": {
     *     "id": 1,
     *     "name": "City Center",
     *     "coordinates": [[51.5074, -0.1278], [51.5075, -0.1279]],
     *     "radius": "500",
     *     "speed_correction_error": "10",
     *     "color": "#FF0000",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "name": ["The name field is required."],
     *     "coordinates": ["The coordinates field is required."],
     *     "radius": ["The radius field is required."]
     *   },
     *   "success": false
     * }
     */
    public function saveSpeedZone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'coordinates' => 'required|array',
            'coordinates.*.*' => 'required|numeric',
            'radius' => 'nullable|string',
            'speed_correction_error' => 'nullable',
            'color' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $speedZone = SpeedZone::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
                    'coordinates' => $request->coordinates,
                    'radius' => $request->radius ?? 0,
                    'speed_correction_error' => (string) $request->speed_correction_error,
                    'color' => $request->color
                ]
            );



            return response()->json([
                'speedZone' => $speedZone,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to save speed zone',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update Speed Zone
     * 
     * Update an existing speed zone with new details.
     * 
     * @deprecated Use saveSpeedZone() instead
     * @group Speed Zone Management
     * 
     * @bodyParam id integer required The ID of the speed zone. Example: 1
     * @bodyParam name string nullable The name of the speed zone. Example: "City Center"
     * @bodyParam coordinates array nullable Array of coordinate pairs. Example: [[51.5074, -0.1278], [51.5075, -0.1279]]
     * @bodyParam radius string nullable The radius of the speed zone in meters. Example: "500"
     * @bodyParam speed_correction_error string nullable The speed correction error percentage. Example: "10"
     * @bodyParam color string nullable The color code for the zone. Example: "#FF0000"
     * 
     * @response {
     *   "speedZone": {
     *     "id": 1,
     *     "name": "City Center",
     *     "coordinates": [[51.5074, -0.1278], [51.5075, -0.1279]],
     *     "radius": "500",
     *     "speed_correction_error": "10",
     *     "color": "#FF0000"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Speed zone not found",
     *   "success": false
     * }
     */
    public function updateSpeedZone(Request $request): JsonResponse
    {
        // Method updateSpeedZone is deprecated. Use saveSpeedZone instead.

        if (!$request->id) {
            return response()->json([
                'message' => 'Speed zone ID is required',
                'success' => false
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:speed_zones,id',
            'name' => 'nullable|string|max:255',
            'coordinates' => 'nullable|array',
            'coordinates.*.*' => 'nullable|numeric',
            'radius' => 'nullable|string',
            'speed_correction_error' => 'nullable',
            'color' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $speedZone = SpeedZone::findOrFail($request->id);

            if ($request->name) {
                $speedZone->name = $request->name;
            }
            if ($request->coordinates) {
                $speedZone->coordinates = $request->coordinates;
            }
            if ($request->radius) {
                $speedZone->radius = $request->radius ?? 0;
            }
            if ($request->speed_correction_error) {
                $speedZone->speed_correction_error = $request->speed_correction_error;
            }
            if ($request->color) {
                $speedZone->color = $request->color;
            }

            $speedZone->save();

            return response()->json([
                'speedZone' => $speedZone,
                'success' => true
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Speed zone not found',
                'success' => false
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update speed zone',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Delete Speed Zones
     * 
     * Delete one or multiple speed zones by their IDs.
     * 
     * @group Speed Zone Management
     * 
     * @bodyParam id array required Array of speed zone IDs to delete. Example: [1, 2]
     * 
     * @response {
     *   "message": "speed zones are deleted successfully",
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Invalid speed zone IDs",
     *   "errors": {
     *     "id": ["The id field is required."]
     *   },
     *   "success": false
     * }
     * 
     * @response 404 {
     *   "message": "Speed Zone not found",
     *   "success": false
     * }
     */
    public function deleteSpeedZone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|array',
            'id.*' => 'required|integer|exists:speed_zones,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid speed zone IDs',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $speedZones = SpeedZone::whereIn('id', $request->id)->get();

            if ($speedZones->isEmpty()) {
                return response()->json([
                    'message' => 'Speed Zone not found',
                    'success' => false
                ], 200);
            }

            foreach ($speedZones as $speedZone) {
                $speedZone->delete();
            }



            return response()->json([
                'message' => 'speed zones are deleted successfully',
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to delete speed zones',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
