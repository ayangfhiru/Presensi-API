<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $table = "tasks";
    protected $primaryKey = "id";
    protected $keytype = "int";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'note',
        'image',
        'date',
        'project_id'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
