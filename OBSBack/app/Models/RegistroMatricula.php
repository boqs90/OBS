<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroMatricula extends Model
{
    use HasFactory;

    protected $table = 'registro_matricula';

    protected $fillable = [
        'student_id',
        'grade_id',
        'gradeCourse',
        'enrollmentStatus',
        'enrollmentYear',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }
}

