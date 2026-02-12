<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fullName',
        'gradeCourse',
        'birthDate',
        'enrollmentStatus',
        'enrollmentYear',
        'photo_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birthDate' => 'date',
    ];

    protected $appends = [
        'photo_url',
    ];

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo_path) return null;
        // Devuelve URL absoluta (ej: http://localhost:8000/storage/...)
        return url(Storage::disk('public')->url($this->photo_path));
    }

    public function registroMatricula()
    {
        return $this->hasOne(RegistroMatricula::class, 'student_id');
    }
}
