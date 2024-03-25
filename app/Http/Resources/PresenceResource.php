<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresenceResource extends JsonResource
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
            'date' => $this->date,
            'entry_time' => $this->entry_time,
            'exit_time' => $this->whenNotNull($this->exit_time),
            'status' => $this->status,
            'participant' => $this->user->name,
            'mentor' => $this->mentoring->mentor->name
        ];
    }
}
