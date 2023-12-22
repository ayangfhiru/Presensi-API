<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Presence extends Model
{
    protected $table = "presences";
    protected $primaryKey = "id";
    protected $keyType = "int";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'date',
        'entry_time',
        'exit_time',
        'status',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function mentoring(): BelongsTo
    {
        return $this->belongsTo(Mentoring::class, 'mentoring_id', 'id');
    }
}
