<?php

namespace App\Http\Controllers;

use App\Models\PlanningPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @tags Planning Preferences Management
 */
class PlanningPreferencesController extends Controller
{
    /**
     * Create or Update Planning Preferences
     * 
     * Create or update planning preferences configuration.
     * 
     * @group Planning Preferences Management
     * 
     * @bodyParam id integer nullable The ID of the planning preference. Example: 1
     * @bodyParam enable_toll_roads string required Enable or disable toll roads (yes/no). Example: "yes"
     * @bodyParam allow_several_runs_for_a_vehicle_per_day string required Allow multiple runs per vehicle (yes/no). Example: "yes"
     * @bodyParam territories_planning_mode string required Planning mode (strict/flexible/hybrid). Example: "flexible"
     * @bodyParam working_time_before_break string required Working time before break (HH:mm:ss). Example: "04:00:00"
     * @bodyParam enable_on_board_time_limit_for_deliveries string required Enable delivery time limit (yes/no). Example: "yes"
     * @bodyParam on_board_time_limit_for_deliveries object required Delivery time limit configuration
     * @bodyParam on_board_time_limit_for_deliveries.time string required Time value. Example: "02"
     * @bodyParam on_board_time_limit_for_deliveries.value string required Time unit. Example: "hours"
     * @bodyParam enable_on_board_time_limit_for_collections string required Enable collection time limit (yes/no). Example: "yes"
     * @bodyParam on_board_time_limit_for_collections object required Collection time limit configuration
     * @bodyParam on_board_time_limit_for_collections.time string required Time value. Example: "02"
     * @bodyParam on_board_time_limit_for_collections.value string required Time unit. Example: "hours"
     * 
     * @response {
     *   "planningPrefernce": {
     *     "id": 1,
     *     "enable_toll_roads": "yes",
     *     "allow_several_runs_for_a_vehicle_per_day": "yes",
     *     "territories_planning_mode": "flexible",
     *     "working_time_before_break": "04:00:00",
     *     "enable_on_board_time_limit_for_deliveries": "yes",
     *     "on_board_time_limit_for_deliveries": "02:00:00",
     *     "enable_on_board_time_limit_for_collections": "yes",
     *     "on_board_time_limit_for_collections": "02:00:00",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     */
    public function createOrUpdatePlanning(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:planning_preferences,id',
            'enable_toll_roads' => ['required'],
            'allow_several_runs_for_a_vehicle_per_day' => ['required'],
            'territories_planning_mode' => ['nullable'],
            'working_time_before_break' => 'nullable|string',
            'enable_on_board_time_limit_for_deliveries' => ['required'],
            'on_board_time_limit_for_deliveries' => 'nullable|array',
            'on_board_time_limit_for_deliveries.time' => 'nullable',
            'on_board_time_limit_for_deliveries.value' => 'nullable',
            'enable_on_board_time_limit_for_collections' => ['required'],
            'on_board_time_limit_for_collections' => 'nullable|array',
            'on_board_time_limit_for_collections.time' => 'nullable',
            'on_board_time_limit_for_collections.value' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $planning = PlanningPreference::find($request->id);
            if (!$planning) {
                $planning = new PlanningPreference();
            }

            $on_board_for_collection = $request->on_board_time_limit_for_collections['time'] . ' ' .
                $request->on_board_time_limit_for_collections['value'];

            $on_board_for_delivery = $request->on_board_time_limit_for_deliveries['time'] . ' ' .
                $request->on_board_time_limit_for_deliveries['value'];

            $planning->fill([
                'enable_toll_roads' => $request->enable_toll_roads == true ? 'yes' : 'no',
                'allow_several_runs_for_a_vehicle_per_day' => $request->allow_several_runs_for_a_vehicle_per_day == true ? 'yes' : 'no',
                'territories_planning_mode' => $request->territories_planning_mode,
                'working_time_before_break' => $request->working_time_before_break,
                'enable_on_board_time_limit_for_deliveries' => $request->enable_on_board_time_limit_for_deliveries == true ? 'yes' : 'no',
                'on_board_time_limit_for_deliveries' => $on_board_for_delivery,
                'enable_on_board_time_limit_for_collections' => $request->enable_on_board_time_limit_for_collections ? 'yes' : 'no',
                'on_board_time_limit_for_collections' => $on_board_for_collection
            ]);

            $planning->save();



            return response()->json([
                'planningPrefernce' => $planning,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            Log::error('Failed to save planning preferences: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save planning preferences',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Get Planning Preferences
     * 
     * Retrieve the current planning preferences configuration.
     * 
     * @group Planning Preferences Management
     * 
     * @response {
     *   "planningPreference": {
     *     "id": 1,
     *     "enable_toll_roads": "yes",
     *     "allow_several_runs_for_a_vehicle_per_day": "yes",
     *     "territories_planning_mode": "flexible",
     *     "working_time_before_break": "04:00:00",
     *     "enable_on_board_time_limit_for_deliveries": "yes",
     *     "on_board_time_limit_for_deliveries": {
     *       "time": "02",
     *       "value": "hours"
     *     },
     *     "enable_on_board_time_limit_for_collections": "yes",
     *     "on_board_time_limit_for_collections": {
     *       "time": "02",
     *       "value": "hours"
     *     },
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Planning preferences not found",
     *   "success": false
     * }
     */
    public function getPlanningPreference(): JsonResponse
    {
        try {
            $planningPreference = PlanningPreference::latest()->first();

            if (!$planningPreference) {
                return response()->json([
                    'message' => 'Planning preferences not found',
                    'success' => false
                ], 200);
            }

            // Explode time and unit for on_board_time_limit_for_deliveries
            $onBoardTimeLimitDeliveries = explode(' ', $planningPreference->on_board_time_limit_for_deliveries);
            $timeDeliveries = $onBoardTimeLimitDeliveries[0] ?? '';
            $unitDeliveries = $onBoardTimeLimitDeliveries[1] ?? '';

            // Explode time and unit for on_board_time_limit_for_collections
            $onBoardTimeLimitCollections = explode(' ', $planningPreference->on_board_time_limit_for_collections);
            $timeCollections = $onBoardTimeLimitCollections[0] ?? '';
            $unitCollections = $onBoardTimeLimitCollections[1] ?? '';

            // Create associative arrays with time and value keys
            $onBoardTimeLimitDeliveriesArray = ['time' => $timeDeliveries, 'value' => $unitDeliveries];
            $onBoardTimeLimitCollectionsArray = ['time' => $timeCollections, 'value' => $unitCollections];

            // Update planning preferences object with merged data as arrays
            $planningPreference->on_board_time_limit_for_deliveries = $onBoardTimeLimitDeliveriesArray;
            $planningPreference->on_board_time_limit_for_collections = $onBoardTimeLimitCollectionsArray;

            return response()->json([
                'planningPreference' => $planningPreference,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch planning preferences: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch planning preferences',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
