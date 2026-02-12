<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullName',
        'email',
        'phone',
        'identityNumber',
        'position_id',
        'entryDate',
        'exitDate',
        'status',
    ];

    protected $casts = [
        'entryDate' => 'date',
        'exitDate' => 'date',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
}

