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

    public function getPresence(Request $request)
    {
        $page = 10;
        $auth = Auth::user();
        $presence = Presence::where(function (Builder $builder) use ($request) {
            $status = $request->status;
            if (isset($status)) {
                $builder->where(function (Builder $builder) use ($status) {
                    $builder->orWhere('status', '=', $status);
                });
            }

            $dateStart = $request->dateStart;
            if ($dateStart) {
                $timeStamp = strtotime($dateStart);
                $start = round($timeStamp * 1000);
                $builder->where(function (Builder $builder) use ($start) {
                    $builder->orWhere('date', '>=', $start);
                });
            }

            $dateEnd = $request->dateEnd;
            if ($dateEnd) {
                $timeStamp = strtotime($dateEnd);
                $end = round($timeStamp * 1000);
                $builder->where(function (Builder $builder) use ($end) {
                    $builder->orWhere('date', '<=', $end);
                });
            }

            $participant = $request->participant;
            if ($participant) {
                $builder->whereHas('mentoring.participant', function (Builder $builder) use ($participant) {
                    $builder->where(function (Builder $builder) use ($participant) {
                        $builder->orWhere('username', 'like', $participant . '%');
                    });
                });
            }
        });

        if ($auth->role_id == 1) {
            $mentor = $request->mentor;
            if ($mentor) {
                $presence = $presence->whereHas('mentoring.mentor', function (Builder $builder) use ($mentor) {
                    $builder->where(function (Builder $builder) use ($mentor) {
                        $builder->orWhere('username', 'like', $mentor . '%');
                    });
                });
            }
        }

        if ($auth->role_id == 2) {
            $presence = $presence->whereHas('mentoring', function (Builder $builder) use ($auth) {
                $builder->where('mentor_id', '=', $auth->id);
            });
        }

        try {
            $presence = $presence->paginate($page);
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
}
