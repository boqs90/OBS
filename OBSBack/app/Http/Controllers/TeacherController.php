<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    public function index()
    {
        return response()->json(Teacher::all());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:200',
            'email' => 'required|email|max:200|unique:teachers,email',
            'phone' => 'nullable|string|max:200',
            'specialty' => 'nullable|string|max:200',
            'birthDate' => 'required|date',
            'position' => 'required|string|max:200',
            'identityNumber' => 'required|string|max:200|unique:teachers,identityNumber',
            'entryDate' => 'required|date',
            'exitDate' => 'nullable|date|after_or_equal:entryDate',
            'status' => 'required|string|in:Activo,Inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $teacher = Teacher::create($validator->validated());

        AuditLog::record(
            $request,
            'teacher_created',
            "Maestro registrado: {$teacher->fullName}",
            ['teacher_id' => $teacher->id, 'fullName' => $teacher->fullName, 'email' => $teacher->email, 'status' => $teacher->status],
            (int) $teacher->id,
            'Teacher'
        );

        $this->notifyAdmins(
            $request,
            'Nuevo maestro registrado',
            "Se registró el maestro '{$teacher->fullName}'.",
            'teacher_created',
            ['teacher_id' => $teacher->id]
        );

        return response()->json(['message' => 'Maestro creado con éxito', 'teacher' => $teacher], 201);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $before = [
            'fullName' => $teacher->fullName,
            'email' => $teacher->email,
            'status' => $teacher->status,
        ];

        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:200',
            'email' => "required|email|max:200|unique:teachers,email,{$teacher->id}",
            'phone' => 'nullable|string|max:200',
            'specialty' => 'nullable|string|max:200',
            'birthDate' => 'required|date',
            'position' => 'required|string|max:200',
            'identityNumber' => "required|string|max:200|unique:teachers,identityNumber,{$teacher->id}",
            'entryDate' => 'required|date',
            'exitDate' => 'nullable|date|after_or_equal:entryDate',
            'status' => 'required|string|in:Activo,Inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $teacher->update($validator->validated());

        AuditLog::record(
            $request,
            'teacher_updated',
            "Maestro actualizado: {$teacher->fullName}",
            ['teacher_id' => $teacher->id, 'before' => $before, 'after' => ['fullName' => $teacher->fullName, 'email' => $teacher->email, 'status' => $teacher->status]],
            (int) $teacher->id,
            'Teacher'
        );

        return response()->json(['message' => 'Maestro actualizado con éxito', 'teacher' => $teacher]);
    }

    public function destroy(Request $request, Teacher $teacher)
    {
        $meta = ['teacher_id' => $teacher->id, 'fullName' => $teacher->fullName, 'email' => $teacher->email, 'status' => $teacher->status];
        $teacher->delete();

        AuditLog::record(
            $request,
            'teacher_deleted',
            "Maestro eliminado: {$meta['fullName']}",
            $meta,
            (int) $meta['teacher_id'],
            'Teacher'
        );
        return response()->json(['message' => 'Maestro eliminado con éxito']);
    }
}

