<?php

namespace App\Http\Controllers;

use App\Models\Breaks;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @tags Break Management
 */
class BreaksController extends Controller
{
    /**
     * Get Breaks Configuration
     * 
     * Retrieve the breaks configuration including lunch, rest, and coffee breaks.
     * 
     * @group Break Management
     * 
     * @response {
     *   "breaks": {
     *     "id": 1,
     *     "break_type": "lunch",
     *     "break_duration": "01:00:00",
     *     "driving_time_before_break": "04:00:00",
     *     "working_time_before_break": "06:00:00",
     *     "allowed_break_shift": true,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "breaks": "not found",
     *   "success": false
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch breaks",
     *   "error": "Database error",
     *   "success": false
     * }
     */
    public function getBreaks(): JsonResponse
    {
        try {
            $breaks = Breaks::first();

            if (!$breaks) {
                return response()->json([
                    'breaks' => 'Breaks not found',
                    'success' => false
                ], 200);
            }

            return response()->json([
                'breaks' => $breaks,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch breaks: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch breaks',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update Breaks Configuration
     * 
     * Create or update breaks configuration including lunch, rest, and coffee breaks.
     * 
     * @group Break Management
     * 
     * @bodyParam id integer nullable The ID of the break configuration. Example: 1
     * @bodyParam break_type string required The type of break (lunch, rest, coffee). Example: "lunch"
     * @bodyParam break_duration string required The duration of the break (HH:mm:ss). Example: "01:00:00"
     * @bodyParam driving_time_before_break string required The driving time before break (HH:mm:ss). Example: "04:00:00"
     * @bodyParam working_time_before_break string required The working time before break (HH:mm:ss). Example: "06:00:00"
     * @bodyParam allowed_break_shift boolean required Whether the break can be shifted. Example: true
     * 
     * @response {
     *   "breaks": {
     *     "id": 1,
     *     "break_type": "lunch",
     *     "break_duration": "01:00:00",
     *     "driving_time_before_break": "04:00:00",
     *     "working_time_before_break": "06:00:00",
     *     "allowed_break_shift": true,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "break_type": ["The break type must be one of: lunch, rest, coffee."]
     *   },
     *   "success": false
     * }
     */
    public function createOrUpdateBreaks(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer|exists:breaks,id',
            'break_type' => ['required', 'string'],
            'break_duration' => [
                'required',
                'string',
            ],
            'driving_time_before_break' => [
                'required',
                'string',
            ],
            'working_time_before_break' => [
                'required',
                'string',
            ],
            'allowed_break_shift' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $breakType = $request->break_type;
            $breakDuration = $request->break_duration;
            $drivingTimeBeforeBreak = $request->driving_time_before_break;
            $workingTimeBeforeBreak = $request->working_time_before_break;
            $allowedBreakShift = $request->allowed_break_shift;

            $data = [
                'break_type' => $breakType,
                'break_duration' => $breakDuration,
                'driving_time_before_break' => $drivingTimeBeforeBreak,
                'working_time_before_break' => $workingTimeBeforeBreak,
                'allowed_break_shift' => $allowedBreakShift
            ];

            $breaks = Breaks::updateOrCreate(
                ['id' => $request->id],
                $data
            );



            return response()->json([
                'breaks' => $breaks,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            Log::error('Failed to save breaks: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save breaks',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
