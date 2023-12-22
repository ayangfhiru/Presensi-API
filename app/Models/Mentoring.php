<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mentoring extends Model
{
    protected $table = "mentorings";
    protected $primaryKey = "id";
    protected $keyType = "int";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'mentor_id',
        'participant_id'
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id', 'id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_id', 'id');
    }

    public function project(): HasMany
    {
        return $this->hasMany(Project::class, 'mentoring_id', 'id');
    }

    public function task(): HasMany
    {
        return $this->hasMany(Task::class, 'mentoring_id', 'id');
    }

    public function presence(): HasMany
    {
        return $this->hasMany(Presence::class, 'mentoring_id', 'id');
    }
}
