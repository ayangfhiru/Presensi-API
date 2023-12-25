<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Mail\LoginEmail;
use App\Models\Division;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\UserRegisterRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserController extends Controller
{
    public function register(UserRegisterRequest $request)
    {
        $request->validated();
        $auth = Auth::user();
        if ($auth->role_id != 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'non-admin role'
                    ]
                ]
            ], 400));
        }

        if (User::where('username', $request->username)->count() == 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'username' => [
                        'username alredy registered'
                    ]
                ]
            ], 400));
        }

        if (User::where('email', $request->email)->count() == 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'email' => [
                        'email alredy registered'
                    ]
                ]
            ], 400));
        }

        if (User::where('phone', $request->phone)->count() == 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'phone' => [
                        'phone alredy registered'
                    ]
                ]
            ], 400));
        }

        $user = new User($request->all());
        $password = Str::random(6);
        $user->password = Hash::make($password);
        try {
            $user->save();
            $role = Role::find($request->role_id)->name;
            $division = $request->division_id ? Division::find($request->division_id)->name : null;
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        $mailData = [
            'role' => $role,
            'division' => $division,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $password
        ];

        Mail::to('fhiruayang@gmail.com')->send(new LoginEmail($mailData));

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function getUser(Request $request)
    {
        $auth = Auth::user();
        $page = 10;
        $user = User::where(function (Builder $builder) use ($request) {
            $username = $request->username;
            if ($username) {
                $builder->where(function (Builder $builder) use ($username) {
                    $builder->orWhere('username', 'like', $username . '%');
                });
            }
        });

        if ($auth->role_id == 2) {
            $user = $user->whereHas('participant', function (Builder $builder) use ($auth) {
                $builder->where('mentor_id', '=', $auth->id);
            });
        }

        if ($auth->role_id == 3) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 400));
        }

        try {
            $user = $user->paginate($page);
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return UserResource::collection($user);
    }

    public function deleteUser(Request $request)
    {
        $auth = Auth::user();
        $user = User::find($request->id);
        if ($auth->role_id != 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'role' => [
                        'non-admin role'
                    ]
                ]
            ], 400));
        }

        if ($user == null) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'data not found'
                    ]
                ]
            ], 400);
        }

        if ($user->role_id == 1) {
            $admin = User::where('role_id', '=', 1)->get()->count();
            if ($admin <= 1) {
                return response()->json([
                    'errors' => [
                        'message' => [
                            'admin user cannot be empty'
                        ]
                    ]
                ], 400);
            }
        }

        try {
            $user->delete();
        } catch (QueryException $err) {
            return response()->json([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400);
        }

        return response()->json([
            'data' => true
        ]);
    }
}
