<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens; // o Passport si usas Passport
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\UserScreenOverride;



class User extends Authenticatable
{
    use HasFactory;
    use HasApiTokens, Notifiable;
    use SoftDeletes;

    protected $user = 'user';

    

    protected $fillable = [
        'name',
        'email',
        'role',
        'status',
        'last_login_at',
        'ended_at',
        'password',
        'is_temp_password',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_temp_password' => 'boolean',
    ];

    public function screenOverrides(): HasMany
    {
        return $this->hasMany(UserScreenOverride::class, 'user_id');
    }

}
