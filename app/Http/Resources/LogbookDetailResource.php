<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;

class LogbookDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'date' => $this->date,
            'image' => $this->image ? URL::to('/storage') . '/' . $this->image : null,
            'status' => $this->status,
            'project' => $this->project->project,
            'projectDate' => $this->project->date,
            'participant' => $this->project->mentoring->participant->name,
            'mentor' => $this->project->mentoring->mentor->name,
        ];
    }
}
