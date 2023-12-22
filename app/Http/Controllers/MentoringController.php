<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mentoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Http\Resources\MentoringResource;
use App\Http\Requests\MentoringAddRequest;
use App\Http\Requests\MentoringUpdateRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MentoringController extends Controller
{
    public function addMentoring(MentoringAddRequest $request)
    {
        $request->validated();
        $auth = Auth::user();

        if ($auth->role_id != 1) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-admin role'
                    ]
                ]
            ], 400);
        }

        $mentor = User::find($request->mentor_id);
        $participant = User::find($request->participant_id);
        if ($mentor->role_id !== 2 || $participant->role_id !== 3) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'data entered invalid'
                    ]
                ]
            ], 400);
        }

        if ($mentor->division_id != $participant->division_id) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'division data is not the same'
                    ]
                ]
            ], 400);
        }

        $mentoring = new Mentoring($request->all());
        try {
            $mentoring->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return (new MentoringResource($mentoring))->response()->setStatusCode(201);
    }

    public function getMentoring()
    {
        $page = 10;
        $auth = Auth::user();
        if ($auth->role_id == 1) {
            $mentoring = Mentoring::paginate($page);
        }

        if ($auth->role_id == 2) {
            $mentoring = Mentoring::where('mentor_id', '=', $auth->id)->paginate($page);
        }

        if ($auth->role_id == 3) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 400);
        }

        try {
            $mentoring;
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return MentoringResource::collection($mentoring);
    }

    public function updateMentoring(MentoringUpdateRequest $request)
    {
        $request->validated();
        $auth = Auth::user();
        $mentoring = Mentoring::find($request->id);
        if ($auth->role_id != 1) {
            return response()->json([
                'errors' => [
                    'role' => [
                        'non-admin role'
                    ]
                ]
            ], 400);
        }


        $mentor = User::find($mentoring->mentor_id);
        $participant = user::find($mentoring->participant_id);

        if (isset($request->mentor_id)) {
            $mentor = User::find($request->mentor_id);
            $mentoring->mentor_id = $request->mentor_id;
        }
        if (isset($request->participant_id)) {
            $participant = User::find($request->participant_id);
            $mentoring->participant_id = $request->participant_id;
        }

        if ($mentor->division_id != $participant->division_id) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'division data is not the same'
                    ]
                ]
            ], 400);
        }

        try {
            $mentoring->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return new MentoringResource($mentoring);
    }

    public function deleteMentoring(Request $request)
    {
        $auth = Auth::user();
        if ($auth->role_id != 1) {
            return response()->json([
                'errors' => [
                    'role' => [
                        'non-admin role'
                    ]
                ]
            ], 400);
        }

        $mentoring = Mentoring::find($request->id);
        try {
            $mentoring->delete();
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
