<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = "projects";
    protected $primaryKey = "id";
    protected $keyType = "int";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'project',
        'status',
        'date',
        'mentoring_id'
    ];

    public function mentoring(): BelongsTo
    {
        return $this->belongsTo(Mentoring::class, 'mentoring_id', 'id');
    }

    public function task(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id', 'id');
    }
}
