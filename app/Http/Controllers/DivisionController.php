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
            ], 400);
        }

        $validation = Validator::make($request->all(), [
            'name' => 'required'
        ]);
        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 400);
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
            ], 400);
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
                $builder->orWhere('name', 'like', $name.'%');
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
            ], 400));
        }
        return DivisionResource::collection($division);
    }

    public function deleteDivision(Request $requst)
    {
        $user = Auth::user();
        $division = Division::find($requst->id);
        if ($user->role_id != 1) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-admin role'
                    ]
                ]
            ], 400);
        }

        try {
            $division->delete();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return response()->json([
            'data' => true
        ]);
    }
}
