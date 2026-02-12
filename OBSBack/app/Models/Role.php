<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Screen;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_system',
        'user_type',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function screens(): BelongsToMany
    {
        return $this->belongsToMany(Screen::class, 'role_screen')
            ->withPivot(['can_create', 'can_edit', 'can_delete'])
            ->withTimestamps();
    }
}

