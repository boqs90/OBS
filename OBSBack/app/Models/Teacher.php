<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_no',
        'fullName',
        'email',
        'phone',
        'specialty',
        'birthDate',
        'position',
        'identityNumber',
        'entryDate',
        'exitDate',
        'observations',
        'status',
    ];
}

