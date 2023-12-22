<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Division extends Model
{
    use HasFactory;
    protected $table = "divisions";
    protected $primaryKey = "id";
    protected $keyType = "int";
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        "name"
    ];

    public function user(): HasMany
    {
        return $this->hasMany(User::class, 'division_id', 'id');
    }
}
