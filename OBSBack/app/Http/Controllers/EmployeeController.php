<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(
            Employee::with('position')
                ->orderBy('fullName')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:200',
            'email' => 'nullable|email|max:200|unique:employees,email',
            'phone' => 'nullable|string|max:50',
            'identityNumber' => 'nullable|string|max:50',
            'position_id' => 'nullable|integer|exists:positions,id',
            'entryDate' => 'nullable|date',
            'exitDate' => 'nullable|date|after_or_equal:entryDate',
            'status' => 'required|string|in:Activo,Inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::create($validator->validated());

        AuditLog::record(
            $request,
            'employee_created',
            "Empleado registrado: {$employee->fullName}",
            ['employee_id' => $employee->id, 'fullName' => $employee->fullName, 'email' => $employee->email, 'status' => $employee->status],
            (int) $employee->id,
            'Employee'
        );

        $this->notifyAdmins(
            $request,
            'Nuevo empleado registrado',
            "Se registró el empleado '{$employee->fullName}'.",
            'employee_created',
            ['employee_id' => $employee->id]
        );

        return response()->json([
            'message' => 'Empleado creado con éxito',
            'employee' => $employee->load('position'),
        ], 201);
    }

    public function update(Request $request, Employee $employee)
    {
        $before = [
            'fullName' => $employee->fullName,
            'email' => $employee->email,
            'status' => $employee->status,
            'position_id' => $employee->position_id,
        ];

        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:200',
            'email' => "nullable|email|max:200|unique:employees,email,{$employee->id}",
            'phone' => 'nullable|string|max:50',
            'identityNumber' => 'nullable|string|max:50',
            'position_id' => 'nullable|integer|exists:positions,id',
            'entryDate' => 'nullable|date',
            'exitDate' => 'nullable|date|after_or_equal:entryDate',
            'status' => 'required|string|in:Activo,Inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee->update($validator->validated());

        AuditLog::record(
            $request,
            'employee_updated',
            "Empleado actualizado: {$employee->fullName}",
            [
                'employee_id' => $employee->id,
                'before' => $before,
                'after' => [
                    'fullName' => $employee->fullName,
                    'email' => $employee->email,
                    'status' => $employee->status,
                    'position_id' => $employee->position_id,
                ],
            ],
            (int) $employee->id,
            'Employee'
        );

        return response()->json([
            'message' => 'Empleado actualizado con éxito',
            'employee' => $employee->load('position'),
        ]);
    }

    public function destroy(Request $request, Employee $employee)
    {
        $meta = [
            'employee_id' => $employee->id,
            'fullName' => $employee->fullName,
            'email' => $employee->email,
            'status' => $employee->status,
            'position_id' => $employee->position_id,
        ];
        $employee->delete();

        AuditLog::record(
            $request,
            'employee_deleted',
            "Empleado eliminado: {$meta['fullName']}",
            $meta,
            (int) $meta['employee_id'],
            'Employee'
        );

        return response()->json([
            'message' => 'Empleado eliminado con éxito',
        ]);
    }
}

