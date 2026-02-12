<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserScreenOverride extends Model
{
    use HasFactory;

    protected $table = 'user_screen_overrides';

    protected $fillable = [
        'user_id',
        'screen_key',
        'allowed',
    ];

    protected $casts = [
        'allowed' => 'boolean',
    ];
}

