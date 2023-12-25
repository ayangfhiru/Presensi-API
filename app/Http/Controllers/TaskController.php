<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Mail\TestEmail;
use App\Models\Project;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\TaskAddRequest;
use Illuminate\Database\QueryException;
use App\Http\Requests\TaskUpdateRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TaskController extends Controller
{
    public function addTask(TaskAddRequest $request)
    {
        $request->validated();
        $auth = Auth::user();
        if ($auth->role_id != 3) {
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
        if ($auth->id != $participant_id) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'failed add task'
                    ]
                ]
            ], 400);
        }

        // upload image
        $image = null;
        if ($request->file('image')) {
            $file = $request->file('image');
            $fileName = date('YmdHi') . $file->getClientOriginalName();
            $image = $file->storeAs('task-images', $fileName);
        }

        // convert date to milliseconds
        $timeStamp = strtotime($request->date);
        $milliseconds = round($timeStamp * 1000);

        $data = $request->all();
        $data['image'] = $image;
        $data['date'] = $milliseconds;
        $task = new Task($data);
        try {
            $task->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return (new TaskResource($task))->response()->setStatusCode(201);
    }
    public function getTask(Request $request)
    {
        $page = 10;
        $auth = Auth::user();

        $task = Task::where(function (Builder $builder) use ($request) {
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

            $builder->whereHas('project.mentoring.participant', function (Builder $builder) use ($request) {
                $participant = $request->participant;
                if ($participant) {
                    $builder->where(function (Builder $builder) use ($participant) {
                        $builder->orWhere('username', 'like', $participant . '%');
                    });
                }
            });
        });

        if ($auth->role_id == 1) {
            $task = $task->whereHas('project.mentoring.mentor', function (Builder $builder) use ($request) {
                $mentor = $request->mentor;
                if ($mentor) {
                    $builder->where(function (Builder $builder) use ($mentor) {
                        $builder->orWhere('username', 'like', $mentor.'%');
                    });
                }
            });
        }

        if ($auth->role_id == 2) {
            $task = $task->whereHas('project.mentoring.mentor', function ($query) use ($auth) {
                $query->where('id', '=', $auth->id);
            });
        }

        if ($auth->role_id == 3) {
            $task = Task::whereHas('project.mentoring.participant', function ($query) use ($auth) {
                $query->where('id', '=', $auth->id);
            });
        }

        try {
            $task = $task->paginate($page);
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        return TaskResource::collection($task);
    }

    public function detailTask(Request $request)
    {
        $auth = Auth::user();
        $task = Task::where('id', '=', $request->id);

        if ($auth->role_id == 2) {
            $task = $task->where(function (Builder $builder) use ($auth) {
                $builder->whereHas('project.mentoring.mentor', function ($query) use ($auth) {
                    $query->where('id', '=', $auth->id);
                });
            });
        }

        if ($auth->role_id == 3) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-admin or mentor role'
                    ]
                ]
            ]);
        }

        try {
            $task = $task->first();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }

        if ($task == null) {
            return response()->json(['errors' => [
                'message' => [
                    'id task wrong'
                ]
            ]]);
        }

        return new TaskResource($task);
    }

    public function updateTask(TaskUpdateRequest $request)
    {
        $request->validated();
        $auth = Auth::user();

        if ($auth->role_id != 2) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-mentor role'
                    ]
                ]
            ]);
        }

        $task = Task::with(['project.mentoring.mentor'])
            ->where('id', '=', $request->id)
            ->first();
        if (!$task) {
            return response()->json(['errors' => [
                'message' => [
                    'data null'
                ]
            ]], 400);
        }

        $mentorId = $task->project->mentoring->mentor->id;
        if ($mentorId != $auth->id) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'participant failed'
                    ]
                ]
            ]);
        }

        if (isset($request->note)) {
            $task->note = $request->note;
        }
        if ($request->file('image')) {
            $file = $request->file('image');
            $fileName = date('YmdHi') . $file->getClientOriginalName();
            $image = $file->storeAs('task-images', $fileName);
            $task->image = $image;
        }

        try {
            $task->save();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
    }

    public function deleteTask(Request $request)
    {
        $auth = Auth::user();
        $task = Task::with(['project.mentoring.mentor'])
            ->find($request->id);
        $mentorId = $task->project->mentoring->mentor->id;

        if ($auth->id == $mentorId) {
            try {
                $task->delete();
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
            return response()->json([
                'errors' => [
                    'message' => [
                        'failed to delete'
                    ]
                ]
            ], 400);
        }

        return response()->json([
            'data' => true
        ]);
    }
}
