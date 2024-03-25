<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status ? true : false,
            'role' => $this->role->name,
            'division' => $this->whenNotNull($this->division ? $this->division->name : null),
            'token' => $this->whenNotNull($this->token)
        ];
    }
}
