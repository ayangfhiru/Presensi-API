<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Mentoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProjectResource;
use Illuminate\Database\QueryException;
use App\Http\Requests\ProjectAddRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Resources\ParticipantMentorResource;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProjectController extends Controller
{
    public function getParticipantMentor(Request $request)
    {
        $auth = Auth::user();
        if ($auth->role_id != 2) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'non-mentor role'
                    ]
                ]
            ], 400);
        }
        try {
            $mentoring = Mentoring::with('participant:id,username')->where('mentorings.mentor_id', '=', $auth->id)->get();
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ], 400));
        }
        return ParticipantMentorResource::collection($mentoring);
    }
    public function addProject(ProjectAddRequest $request)
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
            ], 400);
        }

        $mentoring = Mentoring::where('id', '=', $request->mentoring_id)->first();
        if ($auth->id != $mentoring->mentor_id) {
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
            ], 400));
        }

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    public function getProject(Request $request)
    {
        $page = 10;
        $participant = false;
        $auth = Auth::user();
        if ($auth->role_id == 1) {
            $project = Project::with(['mentoring.participant'])->paginate($page);
        }
        if ($auth->role_id == 2) {
            $project = Project::with(['mentoring.participant'])
                ->whereHas('mentoring.mentor', function ($query) use ($auth) {
                    $query->where('id', '=', $auth->id);
                })
                ->paginate($page);
        }
        if ($auth->role_id == 3) {
            $project = Project::with(['mentoring.participant'])
                ->whereHas('mentoring.participant', function ($query) use ($auth) {
                    $query->where('id', '=', $auth->id);
                })
                ->latest()->first();
            if ($project == null) {
                return response()->json(['errors' => [
                    'message' => [
                        'Project not found'
                    ]
                ]], 404);
            }
            $participant = true;
        }

        try {
            $project;
        } catch (QueryException $err) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        $err->errorInfo[2]
                    ]
                ]
            ]));
        }

        if ($participant == true) {
            return new ProjectResource($project);
        }
        return ProjectResource::collection($project);
    }
    public function updateProject(ProjectUpdateRequest $request)
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
            ], 400);
        }

        $project = Project::with(['mentoring.mentor'])
            ->find($request->id);
        $mentorId = $project->mentoring->mentor->id;
        $data = $request->all();

        if ($auth->id == $mentorId || $auth->role_id == 1) {
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
            ], 400);
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
            ], 400));
        }

        return new ProjectResource($project);
    }

    public function deleteProject(Request $request)
    {
        $auth = Auth::user();
        $project = Project::with(['mentoring.mentor'])
            ->find($request->id);
        $mentorId = $project->mentoring->mentor->id;

        if ($auth->id == $mentorId || $auth->role_id == 1) {
            try {
                $project->delete();
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
