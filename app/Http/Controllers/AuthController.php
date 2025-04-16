<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Login user and create token
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * 
     * @response 200 {
     *   "user": {
     *     "id": "integer",
     *     "first_name": "string",
     *     "last_name": "string",
     *     "username": "string",
     *     "language": "string",
     *     "email": "string",
     *     "email_verified_at": "datetime",
     *     "active_token": "string",
     *     "roles": [
     *       {
     *         "user_id": "integer",
     *         "role_id": "integer",
     *         "role_name": "string"
     *       }
     *     ]
     *   }
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $userData = $user->only([
            'id',
            'name',
            'username',
            'language',
            'email',
            'api_access',
            'allocated_warehouses_as_dispatcher',
            'allocated_warehouses_as_customer',
            'email_verified_at',
            'remember_token'
        ]);

        $userData['roles'] = $user->roles;

        $userData['active_token'] = $token;

        return response()->json([
            'success' => true,
            'user' => $userData
        ]);
    }

    /**
     * Logout user and invalidate token
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @authenticated
     * @response 200 {"message": "Successfully logged out"}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
