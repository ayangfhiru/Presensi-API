<?php

namespace App\Http\Controllers;

use App\Http\Requests\PresenceUpdateRequest;
use App\Models\Presence;
use App\Models\Mentoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Http\Resources\PresenceResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;

class PresenceController extends Controller
{
    public function dateToEpoch($request)
    {
        $timeStamp = strtotime($request);
        $timeToEpoch = round($timeStamp * 1000);
        return $timeToEpoch;
    }

    public function epochToDate($request)
    {
        $epoch = $request / 1000;
        $convert = new \DateTime("@$epoch");
        return $convert;
    }

    public function setStatus($request)
    {
        $maxEntry = "08.30.00";
        $time = date('H.i.s', strtotime($request));
        $status = false;
        if ($time <= $maxEntry) {
            $status = true;
        }
        return $status;
    }

    public function addPresence(Request $request)
    {
        $user = Auth::user();
        if ($user->role_id !== 3) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-participant role'
                    ]
                ]
            ], 400);
        }
        $today = date('Y-m-d');
        $epochToday = $this->dateToEpoch($today);

        $presence = new Presence();
        $data = $presence->where([
            ['user_id', '=', $user->id],
            ['date', '=', $epochToday]
        ])->get();

        if (count($data) > 0) {
            $validator = Validator::make($request->all(), [
                'exit_time' => ['required']
            ]);

            if ($validator->fails()) {
                throw new HttpResponseException(response([
                    "errors" => $validator->getMessageBag()
                ], 400));
            }

            $epochExit = $this->dateToEpoch($request->exit_time);
            $presenceToday = $presence->where([
                ['user_id', '=', $user->id],
                ['date', '=', $epochToday]
            ])->first();
            $exit = Presence::find($presenceToday->id);
            $exit->exit_time = $epochExit;
            try {
                $exit->save();
            } catch (QueryException $err) {
                throw new HttpResponseException(response([
                    'errors' => [
                        'message' => [
                            $err->errorInfo[2]
                        ]
                    ]
                ]));
            }

            return new PresenceResource($exit);

        } else {
            $validator = Validator::make($request->all(), [
                'date' => ['required'],
                'entry_time' => ['required']
            ]);

            if ($validator->fails()) {
                throw new HttpResponseException(response([
                    "errors" => $validator->getMessageBag()
                ], 400));
            }

            $mentoring = Mentoring::where('participant_id', '=', $user->id)->first();

            $epochDate = $this->dateToEpoch($request->date);
            $epochEntry = $this->dateToEpoch($request->entry_time);

            $presence->date = $epochDate;
            $presence->entry_time = $epochEntry;
            $presence->user_id = $user->id;
            $presence->mentoring_id = $mentoring->id;
            $presence->status = $this->setStatus($request->entry_time);

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
    }

    public function getPresence(Request $request)
    {
        $page = 25;
        $user = Auth::user();
        $epochToday = $this->dateToEpoch(date('Y-m-d'));

        $presence = Presence::where(function (Builder $builder) use ($request, $epochToday) {
            $all = $request->all;
            if (isset ($all)) {
                $builder;
            } else {
                $builder->where(function (Builder $builder) use ($epochToday) {
                    $builder->orWhere('date', '=', $epochToday);
                });
            }

            $status = $request->status;
            if (isset ($status)) {
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
                        $builder->orWhere('name', 'like', $participant . '%');
                    });
                });
            }
        });

        if ($user->role_id == 1) {
            $mentor = $request->mentor;
            if (isset($mentor)) {
                $presence = $presence->whereHas('mentoring.mentor', function (Builder $builder) use ($mentor) {
                    $builder->where(function (Builder $builder) use ($mentor) {
                        $builder->orWhere('name', 'like', $mentor . '%');
                    });
                });
            }
        }

        if ($user->role_id == 2) {
            $presence = $presence->whereHas('mentoring', function (Builder $builder) use ($user) {
                $builder->where('mentor_id', '=', $user->id);
            });
        }

        if ($user->role_id == 3) {
            $presence = $presence->whereHas('mentoring', function (Builder $builder) use ($user) {
                $builder->where('participant_id', '=', $user->id);
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

    public function updatePresence(PresenceUpdateRequest $request)
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
        if ($user->role_id == 3) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 400);
        }

        $presence = Presence::where('id', '=', $request->id)->first();
        $mentor = new Mentoring();
        if ($user->role_id == 2) {
            $mentor = $mentor->where([
                ['mentor_id', '=', $user->id],
                ['participant_id', '=', $presence->user_id]
            ])->first();
        }

        if ($mentor == null) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'id presence is wrong'
                    ]
                ]
            ], 400);
        }

        if (isset($request->date)) {
            $presence->date = $this->dateToEpoch($request->date);
        }
        if (isset($request->entry_time)) {
            $presence->entry_time = $this->dateToEpoch($request->entry_time);
            $presence->status = $this->setStatus($request->entry_time);
        }
        if (isset($request->exit_time)) {
            $presence->exit_time = $this->dateToEpoch($request->exit_time);
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
        $user = Auth::user();
        if ($user->role_id == 3) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 400);
        }

        $presence = Presence::find($request->id);
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
        return response()->json([
            'data' => true
        ]);
    }
}