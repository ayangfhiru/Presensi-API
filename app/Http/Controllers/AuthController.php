<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Resources\AuthResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthController extends Controller
{
    public function login(UserLoginRequest $request)
    {
        $request->validated();

        $user = User::where('email', $request['email'])->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'email or password wrong'
                    ]
                ]
            ], 401));
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        $user['token'] = $token;

        return new AuthResource($user);
    }

    public function updateAccount(UserUpdateRequest $request)
    {
        $request->validated();
        if ($request->validated() == null) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'enter the data you want to update!'
                    ]
                ]
            ], 400);
        }
        $user = Auth::user();

        if (isset($request->name)) {
            $user->name = $request->name;
        }
        if (isset($request->email)) {
            $user->email = $request->email;
        }
        if (isset($request->phone)) {
            $user->phone = $request->phone;
        }
        if (isset($request->password)) {
            $user->password = Hash::make($request->password);
        }

        try {
            $user->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return new AuthResource($user);
    }
    
    public function detailAccount(Request $request): AuthResource
    {
        try {
            $user = Auth::user();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return new AuthResource($user);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $user->tokens()->delete();
        return response()->json([
            'data' => true
        ]);
    }
}
