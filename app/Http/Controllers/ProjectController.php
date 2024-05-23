<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Mentoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProjectResource;
use Illuminate\Database\QueryException;
use App\Http\Requests\ProjectAddRequest;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Resources\ParticipantMentorResource;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProjectController extends Controller
{
    public function getParticipantMentor(Request $request)
    {
        $user = Auth::user();
        if ($user->role_id != 2) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-mentor role'
                    ]
                ]
            ], 404);
        }

        $mentoring = Mentoring::with('participant:id,username')->where('mentorings.mentor_id', '=', $user->id);
        $mentoring->whereHas('participant', function (Builder $builder) {
            $builder->where('status', '=', true);
        });

        try {
            $mentoring = $mentoring->get();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404));
        }
        return ParticipantMentorResource::collection($mentoring);
    }

    public function addProject(ProjectAddRequest $request)
    {
        $request->validated();
        $user = Auth::user();
        if ($user->role_id != 2) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-mentor role'
                    ]
                ]
            ], 404);
        }

        $mentoring = Mentoring::where('id', '=', $request->mentoring_id)->first();
        if ($user->id != $mentoring->mentor_id) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'participant not failed'
                    ]
                ]
            ]);
        }

        // convert date to milliseconds
        $timeStamp = strtotime($request->date);
        $milliseconds = round($timeStamp * 1000);

        $data = $request->all();
        $data['status'] = false;
        $data['date'] = $milliseconds;
        $project = new Project($data);
        try {
            $project->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404));
        }

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    public function getProject(Request $request)
    {
        $page = 10;
        $user = Auth::user();

        $project = Project::where(function (Builder $builder) use ($request) {
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
            if ($mentor) {
                $project = $project->whereHas('mentoring.mentor', function (Builder $builder) use ($mentor) {
                    $builder->where(function (Builder $builder) use ($mentor) {
                        $builder->orWhere('name', 'like', $mentor . '%');
                    });
                });
            }
        }

        if ($user->role_id == 2) {
            $project = $project->whereHas('mentoring.mentor', function ($query) use ($user) {
                $query->where('id', '=', $user->id);
            });
        }

        if ($user->role_id == 3) {
            $project = $project->whereHas('mentoring.participant', function ($query) use ($user) {
                $query->where('id', '=', $user->id);
            });
            if ($project == null) {
                return response()->json([
                    'errors' => [
                        'message' => [
                            'project not found'
                        ]
                    ]
                ], 404);
            }
        }

        try {
            $project = $project->orderBy('id', 'desc')->paginate($page);
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ]));
        }

        return ProjectResource::collection($project);
    }

    public function updateProject(ProjectUpdateRequest $request)
    {
        $request->validated();
        if ($request->validated() == null) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'enter the data you want to update!'
                    ]
                ]
            ], 404);
        }
        $user = Auth::user();
        if ($user->role_id != 2) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-mentor role'
                    ]
                ]
            ], 404);
        }

        $project = Project::with(['mentoring.mentor'])
            ->find($request->id);
        $mentorId = $project->mentoring->mentor->id;
        $data = $request->all();

        if ($user->id == $mentorId) {
            if (isset($request->project)) {
                $project->project = $request->project;
            }
            if (isset($request->status)) {
                $project->status = $request->status;
            }
            if (isset($request->date)) {
                $timeStamp = strtotime($request->date);
                $milliseconds = round($timeStamp * 1000);
                $data['date'] = $milliseconds;
                $project->date = $data['date'];
            }
        } else {
            return response()->json([
                'errors' => [
                    'message' => [
                        'failed to update'
                    ]
                ]
            ], 404);
        }

        try {
            $project->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 404));
        }

        return new ProjectResource($project);
    }

    public function deleteProject(Request $request)
    {
        $user = Auth::user();
        $project = Project::with(['mentoring.mentor'])
            ->find($request->id);
        $mentorId = $project->mentoring->mentor->id;

        if ($user->role_id != 2) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-mentor role'
                    ]
                ]
            ], 404);
        }

        if ($user->id == $mentorId) {
            try {
                $project->delete();
            } catch (QueryException $err) {
                throw new HttpResponseException(response([
                    'errors' => [
                        'message' => [
                            $err->errorInfo[2]
                        ]
                    ]
                ], 404));
            }
        } else {
            return response()->json([
                'errors' => [
                    'message' => [
                        'failed to delete'
                    ]
                ]
            ], 404);
        }

        return response()->json([
            'data' => true
        ]);
    }
}
