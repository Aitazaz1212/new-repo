<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function getRoles()
    {
        return Role::all();
    }

    public function deleteRole(Request $request)
    {
        Role::where('id', $request->id)->delete();
        return response()->json(['message' => 'Role deleted successfully']);
    }
}
