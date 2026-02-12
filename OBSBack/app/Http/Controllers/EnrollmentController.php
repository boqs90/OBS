<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\RegistroMatricula;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    private function syncStudentFromLatest(int $studentId): void
    {
        $student = Student::find($studentId);
        if (!$student) return;

        // Tomar la última matrícula NO anulada como “estado actual” del alumno.
        $latest = RegistroMatricula::where('student_id', $studentId)
            ->where('enrollmentStatus', '!=', 'Anulado')
            ->orderByDesc('enrollmentYear')
            ->orderByDesc('enrolled_at')
            ->first();

        if ($latest) {
            $student->update([
                'gradeCourse' => $latest->gradeCourse,
                'enrollmentStatus' => $latest->enrollmentStatus,
                'enrollmentYear' => $latest->enrollmentYear,
            ]);
            return;
        }

        // Si no hay matrícula vigente, dejamos el curso tal cual pero marcamos inactivo y limpiamos año.
        $student->update([
            'enrollmentStatus' => 'Inactivo',
            'enrollmentYear' => null,
        ]);
    }

    public function index(Request $request)
    {
        $enrollments = RegistroMatricula::with('student')
            ->orderByDesc('enrollmentYear')
            ->orderByDesc('enrolled_at')
            ->get();

        return response()->json($enrollments);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:students,id',
            'gradeCourse' => 'required|string|max:200',
            'enrollmentStatus' => 'required|string|in:Activo,Inactivo,Pendiente',
            'enrollmentYear' => 'required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Validación: no permitir 2 matrículas del mismo estudiante en el mismo año
        $exists = RegistroMatricula::where('student_id', $data['student_id'])
            ->where('enrollmentYear', $data['enrollmentYear'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe una matrícula registrada para este estudiante en el año ' . $data['enrollmentYear'] . '.',
            ], 409);
        }

        $enrollment = RegistroMatricula::create([
            'student_id' => $data['student_id'],
            'gradeCourse' => $data['gradeCourse'],
            'enrollmentStatus' => $data['enrollmentStatus'],
            'enrollmentYear' => $data['enrollmentYear'],
            'enrolled_at' => now()->toDateString(),
        ]);

        AuditLog::record(
            $request,
            'enrollment_created',
            'Matrícula registrada',
            [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'gradeCourse' => $enrollment->gradeCourse,
                'enrollmentStatus' => $enrollment->enrollmentStatus,
                'enrollmentYear' => $enrollment->enrollmentYear,
            ],
            (int) $enrollment->id,
            'RegistroMatricula'
        );

        // Mantener sincronizado el "estado actual" del alumno
        $this->syncStudentFromLatest((int) $data['student_id']);

        return response()->json([
            'message' => 'Matrícula registrada con éxito',
            'enrollment' => $enrollment->load('student'),
        ], 201);
    }

    public function update(Request $request, RegistroMatricula $enrollment)
    {
        $before = [
            'gradeCourse' => $enrollment->gradeCourse,
            'enrollmentStatus' => $enrollment->enrollmentStatus,
            'enrollmentYear' => $enrollment->enrollmentYear,
        ];

        $validator = Validator::make($request->all(), [
            'gradeCourse' => 'required|string|max:200',
            'enrollmentStatus' => 'required|string|in:Activo,Inactivo,Pendiente,Anulado',
            'enrollmentYear' => 'required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Validación: no permitir 2 matrículas del mismo estudiante en el mismo año
        $exists = RegistroMatricula::where('student_id', $enrollment->student_id)
            ->where('enrollmentYear', $data['enrollmentYear'])
            ->where('id', '!=', $enrollment->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe una matrícula registrada para este estudiante en el año ' . $data['enrollmentYear'] . '.',
            ], 409);
        }

        try {
            $enrollment->update([
                'gradeCourse' => $data['gradeCourse'],
                'enrollmentStatus' => $data['enrollmentStatus'],
                'enrollmentYear' => $data['enrollmentYear'],
            ]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'No se pudo actualizar la matrícula.'], 409);
        }

        $this->syncStudentFromLatest((int) $enrollment->student_id);

        AuditLog::record(
            $request,
            'enrollment_updated',
            'Matrícula actualizada',
            [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'before' => $before,
                'after' => [
                    'gradeCourse' => $enrollment->gradeCourse,
                    'enrollmentStatus' => $enrollment->enrollmentStatus,
                    'enrollmentYear' => $enrollment->enrollmentYear,
                ],
            ],
            (int) $enrollment->id,
            'RegistroMatricula'
        );

        return response()->json([
            'message' => 'Matrícula actualizada con éxito',
            'enrollment' => $enrollment->fresh()->load('student'),
        ]);
    }

    public function cancel(Request $request, RegistroMatricula $enrollment)
    {
        if ($enrollment->enrollmentStatus === 'Anulado') {
            return response()->json(['message' => 'La matrícula ya está anulada.'], 409);
        }

        $enrollment->update([
            'enrollmentStatus' => 'Anulado',
        ]);

        $this->syncStudentFromLatest((int) $enrollment->student_id);

        AuditLog::record(
            $request,
            'enrollment_cancelled',
            'Matrícula anulada',
            [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'enrollmentYear' => $enrollment->enrollmentYear,
            ],
            (int) $enrollment->id,
            'RegistroMatricula'
        );

        return response()->json([
            'message' => 'Matrícula anulada con éxito',
        ]);
    }
}

