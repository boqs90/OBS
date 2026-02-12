<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\RegistroMatricula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:200',
            'gradeCourse' => 'required|string|max:200',
            'birthDate' => 'required|date',
            'enrollmentStatus' => 'required|string|in:Activo,Inactivo,Pendiente',
            'enrollmentYear' => 'nullable|integer|min:2000|max:2100',
            'photo' => 'nullable|image|max:2048', // 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students', 'public');
        }

        $student = null;
        DB::transaction(function () use ($validator, &$student, $photoPath) {
            $data = $validator->validated();
            if ($photoPath) $data['photo_path'] = $photoPath;
            $student = Student::create($data);

            RegistroMatricula::create([
                'student_id' => $student->id,
                'gradeCourse' => $data['gradeCourse'],
                'enrollmentStatus' => $data['enrollmentStatus'],
                'enrollmentYear' => $data['enrollmentYear'] ?? (int) date('Y'),
                'enrolled_at' => now()->toDateString(),
            ]);
        });

        if ($student) {
            AuditLog::record(
                $request,
                'student_created',
                "Estudiante creado: {$student->fullName}",
                [
                    'student_id' => $student->id,
                    'fullName' => $student->fullName,
                    'gradeCourse' => $student->gradeCourse,
                    'enrollmentStatus' => $student->enrollmentStatus,
                    'enrollmentYear' => $student->enrollmentYear,
                ],
                (int) $student->id,
                'Student'
            );
        }

        return response()->json(['message' => 'Estudiante creado con éxito', 'student' => $student], 201);
    }

    public function index()
    {
        $students = Student::all();
        return response()->json($students);
    }

    public function update(Request $request, Student $student)
    {
        $before = [
            'fullName' => $student->fullName,
            'gradeCourse' => $student->gradeCourse,
            'enrollmentStatus' => $student->enrollmentStatus,
            'enrollmentYear' => $student->enrollmentYear,
            'photo_path' => $student->photo_path ?? null,
        ];

        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:200',
            'gradeCourse' => 'required|string|max:200',
            'birthDate' => 'required|date',
            'enrollmentStatus' => 'required|string|in:Activo,Inactivo,Pendiente',
            'enrollmentYear' => 'nullable|integer|min:2000|max:2100',
            'photo' => 'nullable|image|max:2048', // 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students', 'public');
        }

        DB::transaction(function () use ($validator, $student, $photoPath) {
            $data = $validator->validated();

            if ($photoPath) {
                // Borrar foto anterior si existía
                if ($student->photo_path) {
                    Storage::disk('public')->delete($student->photo_path);
                }
                $data['photo_path'] = $photoPath;
            }

            $student->update($data);

            // Mantener sincronizado el registro de matrícula "actual" del alumno.
            // Usamos (student_id, enrollmentYear) para no duplicar el mismo año.
            $year = $data['enrollmentYear'] ?? $student->enrollmentYear ?? (int) date('Y');
            RegistroMatricula::updateOrCreate(
                ['student_id' => $student->id, 'enrollmentYear' => $year],
                [
                    'gradeCourse' => $data['gradeCourse'],
                    'enrollmentStatus' => $data['enrollmentStatus'],
                    'enrolled_at' => now()->toDateString(),
                ]
            );
        });

        AuditLog::record(
            $request,
            'student_updated',
            "Estudiante actualizado: {$student->fullName}",
            [
                'student_id' => $student->id,
                'before' => $before,
                'after' => [
                    'fullName' => $student->fullName,
                    'gradeCourse' => $student->gradeCourse,
                    'enrollmentStatus' => $student->enrollmentStatus,
                    'enrollmentYear' => $student->enrollmentYear,
                    'photo_path' => $student->photo_path ?? null,
                ],
            ],
            (int) $student->id,
            'Student'
        );

        return response()->json(['message' => 'Estudiante actualizado con éxito', 'student' => $student]);
    }

    public function destroy(Request $request, Student $student)
    {
        $meta = [
            'student_id' => $student->id,
            'fullName' => $student->fullName,
            'gradeCourse' => $student->gradeCourse,
            'enrollmentStatus' => $student->enrollmentStatus,
            'enrollmentYear' => $student->enrollmentYear,
        ];
        $student->delete();

        AuditLog::record(
            $request,
            'student_deleted',
            "Estudiante eliminado: {$meta['fullName']}",
            $meta,
            (int) $meta['student_id'],
            'Student'
        );
        return response()->json(['message' => 'Estudiante eliminado con éxito']);
    }
}
