<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Http\Resources\DivisionResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DivisionController extends Controller
{
    public function addDivision(Request $request)
    {
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

        $validation = Validator::make($request->all(), [
            'name' => 'required'
        ]);
        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 404);
        }

        $division = new Division($request->all());
        try {
            $division->save();
        } catch (QueryException $err) {
            return response()->json([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404);
        }
        return (new DivisionResource($division))->response()->setStatusCode(201);
    }

    public function getDivision(Request $request)
    {
        $page = 10;
        $division = new Division;
        $name = $request->name;
        if ($name) {
            $division = $division->where(function (Builder $builder) use ($name) {
                $builder->orWhere('name', 'like', $name . '%');
            });
        }
        try {
            $division = $division->get();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404));
        }
        return DivisionResource::collection($division);
    }

    public function deleteDivision(Request $request)
    {
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

        $division = Division::find($request->id);
        if ($division == null) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'data division is null'
                    ]
                ]
            ], 404);
        }

        try {
            $division->delete();
        } catch (QueryException $err) {
            if ($err->getCode() === '23000') {
                return response()->json([
                    'errors' => [
                        'message' => [
                            'cannot be deleted, because the data is connected by relations'
                        ]
                    ]
                ], 404);
            }
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404));
        }
        return response()->json([
            'data' => true
        ]);
    }
}
