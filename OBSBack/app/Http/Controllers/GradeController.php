<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class GradeController extends Controller
{
    private function isProtectedGrade(Grade $grade): bool
    {
        $name = strtolower(trim((string) $grade->name));
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        $protected = [
            'prekinder',
            'kinder',
            '1er grado',
            '2do grado',
            '3er grado',
            '4to grado',
            '5to grado',
            '6to grado',
            '7mo grado',
            '8vo grado',
            '9no grado',
            '10mo grado',
            '11mo grado',
        ];
        if (in_array($name, $protected, true)) return true;

        // Fallback flexible
        if (str_contains($name, 'prekinder') || str_contains($name, 'kinder')) return true;
        if (preg_match('/\b(1|2|3)\s*(er|ro|ero)\b/u', $name)) return true;
        if (preg_match('/\b(4|5|6)\s*(to|t[oó])\b/u', $name)) return true;
        if (preg_match('/\b(7|8)\s*(mo|vo)\b/u', $name)) return true;
        if (preg_match('/\b9\s*(no|vo)\b/u', $name)) return true;
        if (preg_match('/\b10\s*(mo)\b/u', $name)) return true;
        if (preg_match('/\b11\s*(mo)\b/u', $name)) return true;

        return false;
    }
    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        // Colapsar espacios múltiples para evitar "duplicados" visuales.
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        return $name;
    }

    public function index()
    {
        $grades = Grade::orderBy('name')->get();
        
        // Agregar información de protección a cada grado
        $gradesWithProtection = $grades->map(function ($grade) {
            return [
                'id' => $grade->id,
                'name' => $grade->name,
                'description' => $grade->description,
                'status' => $grade->status,
                'created_at' => $grade->created_at,
                'updated_at' => $grade->updated_at,
                'is_protected' => $this->isProtectedGrade($grade),
                'can_delete' => !$this->isProtectedGrade($grade),
            ];
        });

        return response()->json($gradesWithProtection);
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => $this->normalizeName($request->input('name')),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200|unique:grades,name',
            'description' => 'nullable|string|max:200',
            'status' => 'required|string|in:Activo,Inactivo',
        ], [
            'name.unique' => 'Ya existe un grado con ese nombre.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Evitar duplicados por mayúsculas/minúsculas (y espacios ya normalizados arriba)
        $exists = Grade::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un grado con ese nombre.',
                'errors' => ['name' => ['Ya existe un grado con ese nombre.']],
            ], 422);
        }

        $grade = Grade::create($validated);

        AuditLog::record(
            $request,
            'grade_created',
            "Grado creado: {$grade->name}",
            ['grade_id' => $grade->id, 'grade_name' => $grade->name, 'status' => $grade->status],
            (int) $grade->id,
            'Grade'
        );

        $this->notifyAdmins(
            $request,
            'Nuevo grado creado',
            "Se creó el grado '{$grade->name}'.",
            'grade_created',
            ['grade_id' => $grade->id, 'grade_name' => $grade->name]
        );
        return response()->json(['message' => 'Grado creado con éxito', 'grade' => $grade], 201);
    }

    public function update(Request $request, Grade $grade)
    {
        $request->merge([
            'name' => $this->normalizeName($request->input('name')),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => "required|string|max:200|unique:grades,name,{$grade->id}",
            'description' => 'nullable|string|max:200',
            'status' => 'required|string|in:Activo,Inactivo',
        ], [
            'name.unique' => 'Ya existe un grado con ese nombre.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $before = [
            'name' => $grade->name,
            'description' => $grade->description ?? null,
            'status' => $grade->status,
        ];

        $validated = $validator->validated();

        // Evitar duplicados por mayúsculas/minúsculas (excluyendo el actual)
        $exists = Grade::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('id', '!=', $grade->id)
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un grado con ese nombre.',
                'errors' => ['name' => ['Ya existe un grado con ese nombre.']],
            ], 422);
        }

        $grade->update($validated);

        AuditLog::record(
            $request,
            'grade_updated',
            "Grado actualizado: {$grade->name}",
            [
                'grade_id' => $grade->id,
                'before' => $before,
                'after' => ['name' => $grade->name, 'description' => $grade->description ?? null, 'status' => $grade->status],
            ],
            (int) $grade->id,
            'Grade'
        );
        return response()->json(['message' => 'Grado actualizado con éxito', 'grade' => $grade]);
    }

    public function destroy(Request $request, Grade $grade)
    {
        if ($this->isProtectedGrade($grade)) {
            return response()->json([
                'message' => 'Este grado está protegido y no se puede eliminar.',
            ], 403);
        }

        try {
            $meta = ['grade_id' => $grade->id, 'grade_name' => $grade->name, 'status' => $grade->status];
            $grade->delete();

            AuditLog::record(
                $request,
                'grade_deleted',
                "Grado eliminado: {$meta['grade_name']}",
                $meta,
                (int) $meta['grade_id'],
                'Grade'
            );
            return response()->json(['message' => 'Grado eliminado con éxito']);
        } catch (QueryException $e) {
            // MySQL: 1451 Cannot delete or update a parent row (FK restrict)
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar este grado porque actualmente se está utilizando para el control de matrícula y está activo.',
                ], 409);
            }
            throw $e;
        }
    }
}
