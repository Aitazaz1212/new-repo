<?php

namespace App\Http\Controllers;

use App\Models\{
    User,
    Driver,
};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * @tags User Management
 */
class UserController extends Controller
{
    /**
     * Get Users List
     *
     * Retrieves a paginated list of users with their roles.
     *
     * @group User Management
     *
     * @queryParam per_page integer Number of items per page. Example: 10
     * @queryParam userRole string Filter users by role. Example: admin
     * @queryParam searchInput string Search users by name. Example: John
     *
     * @response {
     *   "current_page": 1,
     *   "data": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "roles": [{
     *       "id": 1,
     *       "name": "admin"
     *     }]
     *   }],
     *   "per_page": 10,
     *   "total": 1
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "per_page": ["The per page must be an integer."],
     *     "userRole": ["The selected user role is invalid."]
     *   }
     * }
     */
    public function getUsers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'userRole' => 'nullable|string|exists:roles,name',
            'searchInput' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = User::query()
            ->with('roles')
            ->when($request->searchInput, function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->searchInput}%");
            });

        $users = $query->paginate($request->query('per_page', 15));

        return response()->json($users);
    }

    /**
     * Create New User
     *
     * Creates a new user with specified roles and warehouse access.
     *
     * @group User Management
     *
     * @bodyParam name string required User's full name. Example: John Doe
     * @bodyParam email string required User's email address. Example: john@example.com
     * @bodyParam password string required User's password (min 8 characters). Example: SecurePass123
     * @bodyParam password_confirmation string required Password confirmation. Example: SecurePass123
     * @bodyParam language string optional User's preferred language. Example: en
     * @bodyParam api_access boolean optional Enable API access. Example: true
     * @bodyParam administration_access boolean optional Enable admin access. Example: true
     * @bodyParam dispatcher_access boolean optional Enable dispatcher access. Example: true
     * @bodyParam customer_service_access boolean optional Enable customer service access. Example: true
     * @bodyParam dispatcher array optional Array of warehouse IDs for dispatcher. Example: [1, 2]
     * @bodyParam customer array optional Array of warehouse IDs for customer service. Example: [1, 2]
     *
     * @response {
     *   "user": {
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "success": true
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function saveUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
            'language' => 'nullable|string|max:10',
            'api_access' => 'nullable|boolean',
            'administration_access' => 'nullable|boolean',
            'dispatcher_access' => 'nullable|boolean',
            'customer_service_access' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {


            $user = new User();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->language = $request->language ?? 'en';
            $user->password = Hash::make($request->password);
            $user->api_access = $request->api_access ?? 0;
            $user->admin_access = $request->administration_access ?? 0;

            // Handle dispatcher role and warehouses
            if ($request->dispatcher_access && !empty($request->dispatcher)) {
                $implodedString = implode(", ", $request->dispatcher);
                $user->allocated_warehouses_as_dispatcher = $implodedString;
            }

            // Handle customer service role and warehouses
            if ($request->customer_service_access && !empty($request->customer)) {
                $implodedStringc = implode(", ", $request->customer);
                $user->allocated_warehouses_as_customer = $implodedStringc;
            }

            $user->save();

            // Create role associations in role_user table
            if ($request->administration_access == 1) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => Role::where('slug', 'admin')->first()->id
                ]);
            }

            if ($request->dispatcher_access == 1 && !empty($request->dispatcher)) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => Role::where('slug', 'deliveries_manager')->first()->id
                ]);
            }

            if ($request->customer_service_access == 1) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => Role::where('slug', 'call_staff')->first()->id
                ]);
            }



            return response()->json([
                'user' => $request->all(),
                'success' => true
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Failed to create user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Users
     *
     * Deletes one or more users and their associated roles.
     *
     * @group User Management
     *
     * @bodyParam id array required Array of user IDs to delete. Example: [1, 2]
     *
     * @response {
     *   "message": "Selected user deleted successfully",
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "id": ["The id field is required and must be an array."]
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No users found to delete",
     *   "success": false
     * }
     *
     * @response 500 {
     *   "message": "Failed to delete users",
     *   "error": "Error details",
     *   "success": false
     * }
     */
    public function deleteUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {


            // Check if users exist
            $usersExist = User::whereIn('id', $request->id)->exists();
            if (!$usersExist) {
                return response()->json([
                    'message' => 'No users found to delete',
                    'success' => false,
                    'success' => false
                ], 200);
            }

            // Delete role associations first
            DB::table('role_user')->whereIn('user_id', $request->id)->delete();

            // Delete users
            User::whereIn('id', $request->id)->delete();



            return response()->json([
                'message' => 'Selected user deleted successfully',
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to delete users',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Update driver table if the user is performer.
     */
    private function updateDriver($name, $login, $email, $password, $driverId): void
    {
        $driver =  Driver::findOrFail($driverId);
        if ($driver) {
            $driver->name = $name;
            $driver->uName = $login;
            $driver->email = $email;
            $driver->pwd = Hash::make($password);
            $driver->save();
        }
    }
    /**
     * Update User
     *
     * Updates an existing user's information and roles.
     *
     * @group User Management
     *
     * @bodyParam id integer required User ID to update. Example: 1
     * @bodyParam flag string required Update type flag (1 for basic, 0 for full update). Example: "1"
     * @bodyParam name string required User's full name. Example: John Doe
     * @bodyParam email string required User's email address. Example: john@example.com
     * @bodyParam password string optional User's new password (min 8 characters). Example: NewPass123
     * @bodyParam password_confirmation string required if password is present Password confirmation. Example: NewPass123
     * @bodyParam language string optional User's preferred language. Example: en
     * @bodyParam api_access boolean optional Enable API access. Example: true
     * @bodyParam administration_access boolean optional Enable admin access. Example: true
     * @bodyParam dispatcher_access boolean optional Enable dispatcher access. Example: true
     * @bodyParam customer_service_access boolean optional Enable customer service access. Example: true
     * @bodyParam dispatcher array optional Array of warehouse IDs for dispatcher. Example: [1, 2]
     * @bodyParam customer array optional Array of warehouse IDs for customer service. Example: [1, 2]
     *
     * @response {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "roles": ["admin"]
     *   },
     *   "success": true
     * }
     *
     * @response 404 {
     *   "message": "User not found"
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function updateUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'flag' => 'nullable|in:0,1',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->id,
            'password' => 'nullable|min:8',
            'password_confirmation' => 'required_with:password|same:password',
            'language' => 'nullable|string|max:10',
            'api_access' => 'nullable|boolean',
            'administration_access' => 'nullable|boolean',
            'dispatcher_access' => 'nullable|boolean',
            'customer_service_access' => 'nullable|boolean',
            'dispatcher' => 'required_if:dispatcher_access,1|array',
            'dispatcher.*' => 'exists:warehouses,id',
            'customer' => 'required_if:customer_service_access,1|array',
            'customer.*' => 'exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {


            $user = User::with('roles')->findOrFail($request->id);

            if (isset($request->flag) && $request->flag == '1') {
                // Basic update
                $user->name = $request->name;
                $user->email = $request->email;
                $user->admin_access = $request->administration_access;
                if ($request->filled('password')) {
                    $user->password = Hash::make($request->password);
                }
                $user->api_access = $request->api_access;
                $user->language = $request->language;
            } else {
                // Full update
                $user->name = $request->name;
                $user->email = $request->email;
                $user->language = $request->language;
                $user->admin_access = $request->administration_access;
                if ($request->filled('password')) {
                    $user->password = Hash::make($request->password);
                }
                $user->api_access = $request->api_access;
                $user->username = $request->username;

                // Handle dispatcher role and warehouses
                if ($request->dispatcher_access && !empty($request->dispatcher)) {
                    $implodedString = implode(", ", $request->dispatcher);
                    $user->allocated_warehouses_as_dispatcher = $implodedString;
                    DB::table('role_user')->updateOrInsert(
                        ['user_id' => $user->id, 'role_id' => Role::where('slug', 'deliveries_manager')->first()->id]
                    );
                } else {
                    $user->allocated_warehouses_as_dispatcher = null;
                    DB::table('role_user')
                        ->where('user_id', $user->id)
                        ->where('role_id', Role::where('slug', 'deliveries_manager')->first()->id)
                        ->delete();
                }

                // Handle customer service role and warehouses
                if ($request->customer_service_access && !empty($request->customer)) {
                    $implodedString = implode(", ", $request->customer);
                    $user->allocated_warehouses_as_customer = $implodedString;
                    DB::table('role_user')->updateOrInsert(
                        ['user_id' => $user->id, 'role_id' => Role::where('slug', 'call_staff')->first()->id]
                    );
                } else {
                    $user->allocated_warehouses_as_customer = null;
                    DB::table('role_user')
                        ->where('user_id', $user->id)
                        ->where('role_id', Role::where('slug', 'call_staff')->first()->id)
                        ->delete();
                }

                // Handle admin role
                if ($request->administration_access) {
                    DB::table('role_user')->updateOrInsert(
                        ['user_id' => $user->id, 'role_id' => Role::where('slug', 'admin')->first()->id]
                    );
                } else {
                    DB::table('role_user')
                        ->where('user_id', $user->id)
                        ->where('role_id', Role::where('slug', 'admin')->first()->id)
                        ->delete();
                }
            }

            $user->save();

            // If the user is performer then update the driver table also.
            $ifPerformerExsit = DB::table('role_user')
                ->where('user_id', $user->id)
                ->where('role_id', function ($query) {
                    $query->select('id')
                        ->from('roles')
                        ->where('slug', 'performer')
                        ->limit(1);
                })
                ->first();
            if ($ifPerformerExsit) {
                $this->updateDriver($request->name, $request->username, $request->email, $request->password, $user->driver_id);
            }



            return response()->json([
                'user' => $user->fresh('roles'),
                'success' => true
            ]);
        } catch (ModelNotFoundException $e) {

            return response()->json('User not found', 404);
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Failed to update user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get User Details
     *
     * Retrieves detailed information for a specific user.
     *
     * @group User Management
     *
     * @urlParam id integer required The ID of the user. Example: 1
     *
     * @response {
     *   "user": {
     *     "id": 1,
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "username": "johndoe",
     *     "email": "john@example.com",
     *     "language": "en",
     *     "api_access": true,
     *     "admin_access": true,
     *     "allocated_warehouses_as_dispatcher": "1, 2",
     *     "allocated_warehouses_as_customer": "1, 2",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z",
     *     "roles": [{
     *       "id": 1,
     *       "name": "admin"
     *     }]
     *   }
     * }
     *
     * @response 404 {
     *   "error": "User not found"
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "id": ["The id field is required."]
     *   }
     * }
     */
    public function getUserData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->id);

            return response()->json([
                'user' => $user
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'success' => false
            ], 200);
        }
    }

    /**
     * Update User Profile
     *
     * Updates a user's email and password.
     *
     * @group User Management
     *
     * @bodyParam id integer required The ID of the user. Example: 1
     * @bodyParam password string required Current password for verification. Example: currentPass123
     * @bodyParam new_password string The new password (min 8 characters). Example: newPass123
     * @bodyParam email string The new email address. Example: newemail@example.com
     *
     * @response {
     *   "message": "User updated successfully"
     * }
     *
     * @response 404 {
     *   "error": "User not found"
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "password": ["The current password is incorrect."],
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'password' => 'required|string',
            'new_password' => 'nullable|string|min:8|different:password',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->ignore($request->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->id);

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'password' => ['The current password is incorrect.']
                    ]
                ], 422);
            }

            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->new_password);
            }

            if ($request->filled('email')) {
                $user->email = $request->email;
            }

            $user->save();

            return response()->json([
                'message' => 'User updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'success' => false
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update user',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
