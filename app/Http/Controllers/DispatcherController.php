<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Models\Role;


/**
 * @tags Dispatcher Management
 */
class DispatcherController extends Controller
{
    /**
     * Get Dispatchers
     * 
     * Retrieves all users with dispatcher role.
     * 
     * @group Dispatcher Management
     * 
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }]
     * }
     * 
     * @response 500 {
     *   "error": "Something went wrong"
     * }
     */
    public function getDispatcher(): JsonResponse
    {
        try {
            $dispatchers = User::dispatchers()->get();

            return response()->json($dispatchers, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
