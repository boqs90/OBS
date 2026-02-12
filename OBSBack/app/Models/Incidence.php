<?php

namespace App\Models;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidence extends Model
{
    protected $fillable = [
        'type',
        'teacher_id',
        'reported_by_user_id',
        'title',
        'description',
        'severity',
        'status',
        'occurred_at',
        'due_date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }
}
