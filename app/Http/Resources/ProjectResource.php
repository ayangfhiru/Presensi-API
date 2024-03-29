<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'project' => $this->project,
            'status' => $this->status ? true : false,
            'date' => $this->date,
            'mentor' => $this->whenNotNull($this->mentoring->mentor->name),
            'participant' => $this->whenNotNull($this->mentoring->participant->name)
        ];
    }
}
