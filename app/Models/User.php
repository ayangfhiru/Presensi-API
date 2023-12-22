<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Logbook;
use App\Models\Division;
use App\Models\Presence;
use App\Models\Mentoring;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, HasFactory;
    protected $table = "users";
    protected $primaryKey = "id";
    protected $keyType = "int";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'username',
        'email',
        'phone',
        'password',
        'role_id',
        'division_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }

    public function presence(): HasMany
    {
        return $this->hasMany(Presence::class, 'user_id', 'id');
    }

    public function mentor(): HasMany
    {
        return $this->hasMany(Mentoring::class, 'mentor_id', 'id');
    }

    public function participant(): HasOne
    {
        return $this->hasOne(Mentoring::class, 'participant_id', 'id');
    }

    public function projectMentor(): HasMany
    {
        return $this->hasMany(Project::class, 'mentor_id', 'id');
    }

    public function projectParticipant(): HasMany
    {
        return $this->hasMany(Project::class, 'participant_id', 'id');
    }
}
