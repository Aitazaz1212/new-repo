<?php

namespace App\Http\Controllers;

use App\Models\Localization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @tags Localization Management
 */
class LocalizationController extends Controller
{
    /**
     * Create or Update Localization
     * 
     * Create a new localization or update existing one.
     * 
     * @group Localization Management
     * 
     * @bodyParam id integer The ID of the localization to update. Example: 1
     * @bodyParam currency string required The currency setting. Max length: 200 characters. Example: "USD"
     * @bodyParam distance_units string required The distance units setting. Max length: 200 characters. Example: "miles"
     * 
     * @response {
     *   "data": {
     *     "id": 1,
     *     "currency": "USD",
     *     "distance_units": "miles",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "currency": ["The currency field is required."]
     *   },
     *   "success": false
     * }
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'sometimes|integer|exists:localizations,id',
                'currency' => 'required|string|max:200',
                'distance_units' => 'required|string|max:200'
            ]);



            if (isset($validated['id'])) {
                $data = Localization::findOrFail($validated['id']);
                $data->update([
                    'currency' => $validated['currency'],
                    'distance_units' => $validated['distance_units']
                ]);
            } else {
                $data = Localization::create([
                    'currency' => $validated['currency'],
                    'distance_units' => $validated['distance_units']
                ]);
            }



            return response()->json([
                'data' => $data,
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
                'message' => 'Failed to create/update localization',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Get Localization
     * 
     * Retrieve localization settings.
     * 
     * @group Localization Management
     * 
     * @response {
     *   "data": {
     *     "id": 1,
     *     "currency": "USD",
     *     "distance_units": "miles",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Localization not found",
     *   "success": false
     * }
     */
    public function getLocalization(): JsonResponse
    {
        try {
            $localization = Localization::first();

            if (!$localization) {
                return response()->json([
                    'message' => 'Localization not found',
                    'success' => false,
                    'success' => false
                ], 200);
            }

            return response()->json([
                'data' => $localization,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch localization',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
