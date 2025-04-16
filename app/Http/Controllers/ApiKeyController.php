<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @tags API Key Management
 */
class ApiKeyController extends Controller
{
    /**
     * Get API Keys
     *
     * Retrieve all API keys.
     *
     * @group API Key Management
     *
     * @response {
     *   "apiKeys": [
     *     {
     *       "id": 1,
     *       "name": "Production API Key",
     *       "key": "api_key_123",
     *       "created_at": "2024-03-20T12:00:00Z",
     *       "updated_at": "2024-03-20T12:00:00Z"
     *     }
     *   ],
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "No API keys found",
     *   "success": false
     * }
     *
     * @response 500 {
     *   "message": "Failed to fetch API keys",
     *   "error": "Database error",
     *   "success": false
     * }
     */
    public function getApiKeys(): JsonResponse
    {
        try {
            $apiKeys = ApiKey::all();

            if ($apiKeys->isEmpty()) {
                return response()->json([
                    'message' => 'No API keys found',
                    'success' => false
                ], 200);
            }

            return response()->json([
                'apiKeys' => $apiKeys,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch API keys',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create API Key
     *
     * Create a new API key.
     *
     * @group API Key Management
     *
     * @bodyParam name string The name of the API key. Example: "Production API Key"
     * @bodyParam key string The API key value. Example: "api_key_123"
     *
     * @response {
     *   "apiKey": {
     *     "id": 1,
     *     "name": "Production API Key",
     *     "key": "api_key_123",
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
    public function saveApiKey(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string',
                'key' => 'nullable|string'
            ]);

            // Check if the key already exists
            if (!empty($validated['key']) && ApiKey::where('key', $validated['key'])->exists()) {
                return response()->json([
                    'message' => 'The API key already exists.',
                    'success' => false
                ], 409); // HTTP status code 409 (Conflict)
            }



            $apiKey = new ApiKey();
            $apiKey->fill($validated);
            $apiKey->save();



            return response()->json([
                'apiKey' => $apiKey,
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
                'message' => 'Failed to create API key',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Edit API Key
     *
     * Retrieve a specific API key by ID.
     *
     * @group API Key Management
     *
     * @queryParam key_id integer required The ID of the API key. Example: 1
     *
     * @response {
     *   "apiKey": {
     *     "id": 1,
     *     "name": "Production API Key",
     *     "key": "api_key_123",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "API key not found",
     *   "success": false
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "key_id": ["The key id field is required."]
     *   },
     *   "success": false
     * }
     */
    public function editApiKey(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'key_id' => 'required|integer|exists:api_keys,id'
            ]);

            $apiKey = ApiKey::findOrFail($validated['key_id']);

            return response()->json([
                'apiKey' => $apiKey,
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'API key not found',
                'success' => false
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch API key',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update API Key
     *
     * Update an existing API key.
     *
     * @group API Key Management
     *
     * @bodyParam key_id integer required The ID of the API key to update. Example: 1
     * @bodyParam name string The name of the API key. Example: "Production API Key"
     * @bodyParam key string The API key value. Example: "api_key_123"
     *
     * @response {
     *   "apiKey": {
     *     "id": 1,
     *     "name": "Production API Key",
     *     "key": "api_key_123",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "API key not found",
     *   "success": false
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "key_id": ["The key id field is required."]
     *   },
     *   "success": false
     * }
     */
    public function updateApiKey(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'key_id' => 'nullable|integer|exists:api_keys,id',
                'name' => 'required|string',
                'key' => 'required|string'
            ]);



            $apiKey = ApiKey::findOrFail($validated['key_id']);
            $apiKey->fill([
                'name' => $validated['name'],
                'key' => $validated['key']
            ]);
            $apiKey->save();



            return response()->json([
                'apiKey' => $apiKey,
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'API key not found',
                'success' => false,
                'success' => false
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to update API key',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Delete API Key
     *
     * Delete an existing API key.
     *
     * @group API Key Management
     *
     * @bodyParam key_id integer required The ID of the API key to delete. Example: 1
     *
     * @response {
     *   "apiKeys": [
     *     {
     *       "id": 2,
     *       "name": "Production API Key",
     *       "key": "api_key_123",
     *       "created_at": "2024-03-20T12:00:00Z",
     *       "updated_at": "2024-03-20T12:00:00Z"
     *     }
     *   ],
     *   "message": "API key deleted successfully",
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "API key not found",
     *   "success": false
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "key_id": ["The key id field is required."]
     *   },
     *   "success": false
     * }
     */
    public function deleteApiKey(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'key_id' => 'required|integer|exists:api_keys,id'
            ]);



            $apiKey = ApiKey::findOrFail($validated['key_id']);
            $apiKey->delete();

            $apiKeys = ApiKey::all();



            return response()->json([
                'apiKeys' => $apiKeys,
                'message' => 'API key deleted successfully',
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'API key not found',
                'success' => false
            ], 200);
        } catch (\Exception $e) {

            Log::error('Failed to delete API key: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete API key',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
