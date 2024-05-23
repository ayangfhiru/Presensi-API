<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Project;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Resources\LogbookResource;
use App\Http\Resources\LogbookDetailResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LogbookAddRequest;
use Illuminate\Database\QueryException;
use App\Http\Requests\LogbookUpdateRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class LogbookController extends Controller
{
    public function addLogbook(LogbookAddRequest $request)
    {
        $request->validated();
        $user = Auth::user();
        if ($user->role_id != 3) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-participant role'
                    ]
                ]
            ], 400);
        }

        $project = Project::with(['mentoring.participant'])->find($request->project_id);
        $participant_id = $project->mentoring->participant->id;
        if ($user->id != $participant_id) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'failed add logbook'
                    ]
                ]
            ], 400);
        }

        // upload image
        $image = null;
        if ($request->file('image')) {
            $file = $request->file('image');
            $fileName = date('YmdHi') . $file->getClientOriginalName();
            $image = $file->storeAs('logbook-images', $fileName);
        }

        // convert date to milliseconds
        $timeStamp = strtotime($request->date);
        $milliseconds = round($timeStamp * 1000);

        $data = $request->all();
        $data['image'] = $image;
        $data['date'] = $milliseconds;
        $logbook = new Logbook($data);
        try {
            $logbook->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return (new LogbookResource($logbook))->response()->setStatusCode(201);
    }

    public function getLogbook(Request $request)
    {
        $page = 10;
        $user = Auth::user();

        $logbook = Logbook::where(function (Builder $builder) use ($request) {
            $status = $request->status;
            if (isset ($status)) {
                $builder->where(function (Builder $builder) use ($status) {
                    $builder->orWhere('status', '=', $status);
                });
            }

            $dateStart = $request->dateStart;
            if (isset ($dateStart)) {
                $timeStamp = strtotime($dateStart);
                $start = round($timeStamp * 1000);
                $builder->where(function (Builder $builder) use ($start) {
                    $builder->orWhere('date', '>=', $start);
                });
            }

            $dateEnd = $request->dateEnd;
            if (isset ($dateEnd)) {
                $timeStamp = strtotime($dateEnd);
                $end = round($timeStamp * 1000);
                $builder->where(function (Builder $builder) use ($end) {
                    $builder->orWhere('date', '<=', $end);
                });
            }

            $builder->whereHas('project.mentoring.participant', function (Builder $builder) use ($request) {
                $participant = $request->participant;
                if (isset ($participant)) {
                    $builder->where(function (Builder $builder) use ($participant) {
                        $builder->orWhere('name', 'like', $participant . '%');
                    });
                }
            });
        });

        if ($user->role_id == 1) {
            $mentor = $request->mentor;
            if (isset($mentor)) {
                $logbook = $logbook->whereHas('project.mentoring.mentor', function (Builder $builder) use ($mentor) {
                    $builder->where(function (Builder $builder) use ($mentor) {
                        $builder->orWhere('name', 'like', $mentor . '%');
                    });
                });
            }
        }

        if ($user->role_id == 2) {
            $logbook = $logbook->whereHas('project.mentoring.mentor', function ($query) use ($user) {
                $query->where('id', '=', $user->id);
            });
        }

        if ($user->role_id == 3) {
            $logbook = Logbook::whereHas('project.mentoring.participant', function ($query) use ($user) {
                $query->where('id', '=', $user->id);
            });
        }

        try {
            $logbook = $logbook->orderBy('id', 'desc')->paginate($page);
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return LogbookResource::collection($logbook);
    }

    public function detailLogbook(Request $request)
    {
        $user = Auth::user();
        $logbook = Logbook::where('id', '=', $request->id);

        if ($user->role_id == 2) {
            $logbook = $logbook->where(function (Builder $builder) use ($user) {
                $builder->whereHas('project.mentoring.mentor', function ($query) use ($user) {
                    $query->where('id', '=', $user->id);
                });
            });
        }

        if ($user->role_id == 3) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-admin or mentor role'
                    ]
                ]
            ], 400);
        }

        try {
            $logbook = $logbook->first();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        if ($logbook == null) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'id logbook wrong'
                    ]
                ]
            ], 400);
        }

        return new LogbookDetailResource($logbook);
    }

    public function updateLogbook(LogbookUpdateRequest $request)
    {
        $request->validated();
        // if ($request->validated() == null) {
        //     return response()->json([
        //         'errors' => [
        //             'message' => [
        //                 'enter the data you want to update!'
        //             ]
        //         ]
        //     ], 400);
        // }

        $user = Auth::user();
        if ($user->role_id == 1) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-mentor or participant role'
                    ]
                ]
            ], 400);
        }

        $logbook = Logbook::with(['project.mentoring.mentor'])
            ->where('id', '=', $request->id)
            ->first();
        if (!$logbook) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'data from id not found'
                    ]
                ]
            ], 400);
        }

        $mentoring = $logbook->project->mentoring;
        $mentorId = $mentoring->mentor->id;
        $participantId = $mentoring->participant->id;

        if ($user->role_id == 2) {
            if ($mentorId == $user->id) {
                if (isset($request->status)) {
                    $logbook->status = $request->status;
                }
                if (isset($request->date)) {
                    $timeStamp = strtotime($request->date);
                    $milliseconds = round($timeStamp * 1000);
                    $logbook->date = $milliseconds;
                }
                if (isset($request->project_id)) {
                    $logbook->project_id = $request->project_id;
                }
            } else {
                return response()->json([
                    'errors' => [
                        'message' => [
                            'id entered is not the participant you are mentoring'
                        ]
                    ]
                ], 400);
            }
        }

        if ($user->role_id == 3) {
            if ($participantId == $user->id && $logbook->status == 0) {
                if (isset($request->note)) {
                    $logbook->note = $request->note;
                }
                if ($request->file('image')) {
                    $file = $request->file('image');
                    $fileName = date('YmdHi') . $file->getClientOriginalName();
                    $image = $file->storeAs('logbook-images', $fileName);
                    $logbook->image = $image;
                }
            } else {
                return response()->json([
                    'errors' => [
                        'message' => [
                            'can not update'
                        ]
                    ]
                ], 400);
            }
        }

        try {
            $logbook->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return new LogbookDetailResource($logbook);
    }

    public function deleteLogbook(Request $request)
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

        $logbook = new Logbook();
        if ($user->role_id == 1) {
            $logbook->find($request->id);
        } else {
            $logbook->with(['project.mentoring.mentor'])
                ->find($request->id);
            $mentorId = $logbook->project->mentoring->mentor->id;
            if ($user->id != $mentorId) {
                return response()->json([
                    'errors' => [
                        'message' => [
                            'id logbook failed'
                        ]
                    ]
                ], 400);
            }
        }

        try {
            $logbook->delete();
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