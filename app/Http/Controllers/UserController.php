<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUserUpdateRequest;
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
    public function userRegister(UserRegisterRequest $request)
    {
        $request->validated();
        $user = Auth::user();
        if ($user->role_id != 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'non-admin role'
                    ]
                ]
            ], 404));
        }

        if (User::where('email', $request->email)->count() == 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'email' => [
                        'email alredy registered'
                    ]
                ]
            ], 404));
        }

        if (User::where('phone', $request->phone)->count() == 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'phone' => [
                        'phone alredy registered'
                    ]
                ]
            ], 404));
        }

        $newUser = new User($request->all());
        $password = Str::random(6);
        $newUser->password = Hash::make($password);
        try {
            $newUser->save();
            $role = Role::find($request->role_id)->name;
            $division = $request->division_id ? Division::find($request->division_id)->name : null;
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404));
        }

        $mailData = [
            'role' => $role,
            'division' => $division,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $password
        ];

        Mail::to('fhiruayang@gmail.com')->send(new LoginEmail($mailData));

        return (new UserResource($newUser))->response()->setStatusCode(201);
    }

    public function getUser(Request $request)
    {
        $user = Auth::user();
        $page = 25;
        $getUser = User::where(function (Builder $builder) use ($request) {
            $name = $request->name;
            if ($name) {
                $builder->where(function (Builder $builder) use ($name) {
                    $builder->orWhere('name', 'like', $name . '%');
                });
            }

            $division = $request->division;
            if ($division) {
                $builder->whereHas('division', function (Builder $builder) use ($division) {
                    $builder->where('id', '=', $division);
                });
            }
        });

        if ($user->role_id == 1) {
            $role = $request->role;
            if ($role) {
                $getUser = $getUser->whereHas('role', function (Builder $builder) use ($role) {
                    $builder->where('id', '=', $role);
                });
            }
        }

        if ($user->role_id == 2) {
            $getUser = $getUser->whereHas('participant', function (Builder $builder) use ($user) {
                $builder->where('mentor_id', '=', $user->id);
            });
        }

        if ($user->role_id == 3) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 404));
        }

        try {
            $getUser = $getUser->paginate($page);
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404));
        }
        return UserResource::collection($getUser);
    }

    public function updateUser(AdminUserUpdateRequest $request)
    {
        $request->validated();
        $user = Auth::user();
        if ($user->role_id != 1) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-admin role'
                    ]
                ]
            ], 404);
        }

        $updateUser = User::find($request->id);

        if (isset($request->status)) {
            $updateUser->status = $request->status;
        }
        if (isset($request->role)) {
            $role = Role::find($request->role);
            if ($role == null) {
                return response()->json([
                    'errors' => [
                        'role' => [
                            'role not found'
                        ]
                    ]
                ], 404);
            }
            $updateUser->role_id = $request->role;
        }
        if (isset($request->division)) {
            $division = Division::find($request->division);
            if ($division == null) {
                return response()->json([
                    'errors' => [
                        'division' => [
                            'division not found'
                        ]
                    ]
                ], 404);
            }
            $updateUser->division_id = $request->division;
        }
        if (isset($request->password)) {
            $newPassword = $request->password;
            $updateUser->password = Hash::make($newPassword);
        }

        try {
            $updateUser->save();
        } catch (QueryException $err) {
            return response()->json([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404);
        }

        return new UserResource($updateUser);
    }

    public function deleteUser(Request $request)
    {
        $user = Auth::user();
        $deleteUser = User::find($request->id);
        if ($user->role_id != 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'role' => [
                        'non-admin role'
                    ]
                ]
            ], 404));
        }

        if ($deleteUser == null) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'data not found'
                    ]
                ]
            ], 404);
        }

        if ($deleteUser->role_id == 1) {
            $admin = User::where('role_id', '=', 1)->get()->count();
            if ($admin <= 1) {
                return response()->json([
                    'errors' => [
                        'message' => [
                            'admin user cannot be empty'
                        ]
                    ]
                ], 404);
            }
        }

        try {
            $deleteUser->delete();
        } catch (QueryException $err) {
            return response()->json([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404);
        }

        return response()->json([
            'data' => true
        ]);
    }
}
