<?php

namespace App\Http\Controllers;

use App\Models\TimeWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Time Window Management
 */
class TimeWindowController extends Controller
{
    /**
     * Create or Update Time Window
     * 
     * Create or update time window settings.
     * 
     * @group Time Window Management
     * 
     * @bodyParam id integer The ID of the time window to update. Example: 1
     * @bodyParam data string The data for the time window. Example: "delivery window"
     * @bodyParam from string required The start time of the window. Example: "09:00"
     * @bodyParam to string required The end time of the window. Example: "17:00"
     * 
     * @response {
     *   "timewindow": {
     *     "id": 1,
     *     "data": "delivery window",
     *     "from": "09:00",
     *     "to": "17:00",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "from": ["The from field is required."]
     *   },
     *   "success": false
     * }
     */
    public function createOrUpdateTimeWindow(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'nullable|integer|exists:time_windows,id',
                'data' => 'nullable|string',
                'from' => 'required|string',
                'to' => 'required|string'
            ]);



            $timeWindow = TimeWindow::find($request->id);
            if (!$timeWindow) {
                $timeWindow = new TimeWindow();
            }

            $timeWindow->fill($validated);
            $timeWindow->save();



            return response()->json([
                'timewindow' => $timeWindow,
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
                'message' => 'Failed to create/update time window',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Get Time Window
     * 
     * Retrieve the current time window settings.
     * 
     * @group Time Window Management
     * 
     * @response {
     *   "timewindow": {
     *     "id": 1,
     *     "data": "delivery window",
     *     "from": "09:00",
     *     "to": "17:00",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "timewindow": "Time Window does not exist",
     *   "success": false
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch time window",
     *   "error": "Database error",
     *   "success": false
     * }
     */
    public function getTimeWindow(): JsonResponse
    {
        try {
            $timeWindow = TimeWindow::first();

            if (!$timeWindow) {
                return response()->json([
                    'timewindow' => 'Time Window does not exist',
                    'success' => false,
                    'success' => false
                ], 200);
            }

            return response()->json([
                'timewindow' => $timeWindow,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch time window',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
