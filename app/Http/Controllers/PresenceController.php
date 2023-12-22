<?php

namespace App\Http\Controllers;

use App\Models\Mentoring;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\PresenceRequest;
use Illuminate\Database\QueryException;
use App\Http\Resources\PresenceResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;

class PresenceController extends Controller
{
    public function addPresence(PresenceRequest $request)
    {
        $request->validated();
        $user = Auth::user();
        $mentoring = Mentoring::where('participant_id', '=', $user->id)->first();
        $entry = date('H:i:s', ($request->entry_time / 1000));
        $status = false;
        if ($entry <= '08:30:00') {
            $status = true;
        }

        $presence = new Presence($request->all());
        $presence->user_id = $user->id;
        $presence->mentoring_id = $mentoring->id;
        $presence->status = $status;
        try {
            $presence->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ]));
        }
        return (new PresenceResource($presence))->response()->setStatusCode(201);
    }

    public function getPresence()
    {
        $page = 10;
        $auth = Auth::user();
        if ($auth->role_id == 1) {
            $presence = Presence::paginate($page);
        }
        if ($auth->role_id == 2) {
            $presence = Presence::with(['mentoring'])
                ->whereHas('mentoring', function ($query) use ($auth) {
                    $query->where('mentor_id', '=', $auth->id);
                })->paginate($page);
        }

        try {
            $presence;
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return PresenceResource::collection($presence);
    }

    public function getDetailPresence(Request $request)
    {
        $page = 10;
        $auth = Auth::user();
        if ($auth->role_id == 1 || $auth->role_id == 2) {
            try {
                $presence = Presence::find($request->id)->paginate($page);
            } catch (QueryException $err) {
                throw new HttpResponseException(response([
                    'errors' => [
                        'message' => [
                            $err->errorInfo[2]
                        ]
                    ]
                ], 400));
            }
        } else {
            throw new HttpResponseException(response([
                'errors' => [
                    'role' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 400));
        }
        return PresenceResource::collection($presence);
    }

    public function updatePresence(PresenceRequest $request)
    {
        $request->validated();
        $auth = Auth::user();
        $presence = Presence::find($request->id);

        if ($auth->role_id == 1 || $auth->role_id == 2) {
            if (isset($request->date)) {
                $presence->date = $request->date;
            }
            if (isset($request->entry_time)) {
                $presence->entry_time = $request->entry_time;
            }
        } else {
            throw new HttpResponseException(response([
                'errors' => [
                    'role' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 400));
        }

        try {
            $presence->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return new PresenceResource($presence);
    }

    public function deletePresence(Request $request)
    {
        $auth = Auth::user();
        $presence = Presence::find($request->id);

        if ($auth->role_id == 1 || $auth->role_id == 2) {
            try {
                try {
                    $presence->delete();
                } catch (QueryException $err) {
                    throw new HttpResponseException(response([
                        'errors' => [
                            'message' => [
                                $err->errorInfo[2]
                            ]
                        ]
                    ], 400));
                }
            } catch (QueryException $err) {
                return response()->json([
                    'errors' => [
                        'message' => [
                            $err->errorInfo[2]
                        ]
                    ]
                ], 400);
            }
        } else {
            throw new HttpResponseException(response([
                'errors' => [
                    'role' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 400));
        }

        return response()->json([
            'data' => true
        ]);
    }

    public function searchPresence(Request $request)
    {
        $page = $request->input('page', 10);
        $size = $request->input('size', 10);

        $presence = Presence::query();
        $presence->where(function (Builder $builder) use ($request) {
            $id = $request->id;
            if (isset($id)) {
                $builder->where('id', '=', $id);
            }

            $username = $request->username;
            if (isset($username)) {
                $builder->where('username', 'like', '%' . $username . '%');
            }

            $startDate = $request->start;
            $endDate = $request->end;
            $between = $builder->where('date', 'between', $startDate, $endDate);
            if (isset($startDate) && isset($endDate)) {
                $between;
            } else {
                $builder->where('date', '>=', $startDate);
            }

            $status = $request->status;
            if (isset($status)) {
                $builder->where('status', '=', $status);
            }
        });
        $presence = $presence->paginate(perPage: $size, page: $page);
    }
}
