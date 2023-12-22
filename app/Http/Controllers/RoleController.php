<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Resources\RoleResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleController extends Controller
{
    public function addRole(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 400);
        }

        $user = Auth::user();
        if ($user->role_id != 1) {
            return response()->json([
                'errors' => [
                    'role' => [
                        'non-admin role'
                    ]
                ]
            ], 400);
        }
        
        $role = new Role($request->all());
        try {
            $role->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return (new RoleResource($role))->response()->setStatusCode(201);
    }
    public function getRole()
    {
        try {
            $role = Role::all();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return RoleResource::collection($role);
    }
    public function updateRole(Request $request)
    {
        $data = $request->validate([
            'name' => 'required'
        ]);
        $role = Role::find($request['id']);
        $user = Auth::user();
        if ($user->role_id != 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'role' => [
                        'role not admin'
                    ]
                ]
            ], 400));
        }

        if (isset($data['name'])) {
            $role->name = $data['name'];
        }

        try {
            $role->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return new RoleResource($role);
    }
    public function deleteRole(Request $request)
    {
        $role = Role::find($request['id']);
        $user = Auth::user();
        if ($user->role_id != 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'role' => [
                        'role not admin'
                    ]
                ]
            ], 400));
        }
        try {
            $role->delete();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return new RoleResource($role);
    }
}
