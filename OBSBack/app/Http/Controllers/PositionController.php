<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    private const MANDATORY_POSITION_NAME = 'Directora';

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        // Colapsar espacios múltiples para evitar "duplicados" visuales.
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        return $name;
    }

    private function isMandatoryPositionName(?string $name): bool
    {
        $n = $this->normalizeName($name);
        return mb_strtolower($n, 'UTF-8') === mb_strtolower(self::MANDATORY_POSITION_NAME, 'UTF-8');
    }

    public function index()
    {
        // Evita que el frontend "se descomponga" si aún no se han corrido migraciones.
        // Idealmente: ejecutar `php artisan migrate`.
        try {
            // Orden: primero los NO sistema (nuevos), luego los del sistema; dentro de cada grupo alfabético.
            return response()->json(
                Position::query()
                    ->orderBy('is_system')   // false (0) primero
                    ->orderBy('name')
                    ->get()
            );
        } catch (QueryException $e) {
            return response()->json([]);
        }
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => $this->normalizeName($request->input('name')),
        ]);

        // "Directora" es obligatorio y protegido: no permitir crear/duplicar manualmente.
        if ($this->isMandatoryPositionName($request->input('name'))) {
            return response()->json([
                'message' => 'El cargo "Directora" es obligatorio y no se puede crear manualmente.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200|unique:positions,name',
            'status' => 'required|string|in:Activo,Inactivo',
        ], [
            'name.unique' => 'Ya existe un cargo con ese nombre.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $position = Position::create($validator->validated());

        AuditLog::record(
            $request,
            'position_created',
            "Cargo creado: {$position->name}",
            ['position_id' => $position->id, 'position_name' => $position->name, 'status' => $position->status],
            (int) $position->id,
            'Position'
        );

        $this->notifyAdmins(
            $request,
            'Nuevo cargo creado',
            "Se creó el cargo '{$position->name}'.",
            'position_created',
            ['position_id' => $position->id, 'position_name' => $position->name]
        );

        return response()->json(['message' => 'Cargo creado con éxito', 'position' => $position], 201);
    }

    public function update(Request $request, Position $position)
    {
        // El cargo "Directora" es obligatorio y no debe modificarse (evita renombrarlo o inactivarlo).
        if ($this->isMandatoryPositionName($position->name)) {
            return response()->json([
                'message' => 'El cargo "Directora" es obligatorio y no se puede modificar.',
            ], 403);
        }

        $request->merge([
            'name' => $this->normalizeName($request->input('name')),
        ]);

        // No permitir renombrar otros cargos a "Directora"
        if ($this->isMandatoryPositionName($request->input('name'))) {
            return response()->json([
                'message' => 'El cargo "Directora" es obligatorio y no se puede asignar a otro registro.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => "required|string|max:200|unique:positions,name,{$position->id}",
            'status' => 'required|string|in:Activo,Inactivo',
        ], [
            'name.unique' => 'Ya existe un cargo con ese nombre.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $before = [
            'name' => $position->name,
            'status' => $position->status,
        ];

        $position->update($validator->validated());

        AuditLog::record(
            $request,
            'position_updated',
            "Cargo actualizado: {$position->name}",
            ['position_id' => $position->id, 'before' => $before, 'after' => ['name' => $position->name, 'status' => $position->status]],
            (int) $position->id,
            'Position'
        );
        return response()->json(['message' => 'Cargo actualizado con éxito', 'position' => $position]);
    }

    public function setStatus(Request $request, Position $position)
    {
        // Directora siempre activa
        if ($this->isMandatoryPositionName($position->name)) {
            return response()->json([
                'message' => 'El cargo "Directora" es obligatorio y no se puede desactivar.',
            ], 403);
        }

        $validated = Validator::make($request->all(), [
            'status' => 'required|string|in:Activo,Inactivo',
        ])->validate();

        $before = ['status' => $position->status];
        $position->update(['status' => $validated['status']]);

        AuditLog::record(
            $request,
            $validated['status'] === 'Activo' ? 'position_activated' : 'position_deactivated',
            ($validated['status'] === 'Activo')
                ? "Cargo activado: {$position->name}"
                : "Cargo desactivado: {$position->name}",
            [
                'position_id' => $position->id,
                'position_name' => $position->name,
                'before' => $before,
                'after' => ['status' => $position->status],
            ],
            (int) $position->id,
            'Position'
        );

        return response()->json([
            'message' => 'Estado actualizado.',
            'position' => $position,
        ]);
    }

    public function destroy(Request $request, Position $position)
    {
        // El cargo "Directora" es obligatorio y no se puede eliminar.
        if ($this->isMandatoryPositionName($position->name)) {
            return response()->json([
                'message' => 'El cargo "Directora" es obligatorio y no se puede eliminar.',
            ], 403);
        }

        // Los cargos que forman parte del código (sistema/seed) no se pueden eliminar.
        if (!empty($position->is_system)) {
            return response()->json([
                'message' => 'Este cargo es parte del sistema y no se puede eliminar.',
            ], 403);
        }

        $meta = ['position_id' => $position->id, 'position_name' => $position->name, 'status' => $position->status];
        $position->delete();

        AuditLog::record(
            $request,
            'position_deleted',
            "Cargo eliminado: {$meta['position_name']}",
            $meta,
            (int) $meta['position_id'],
            'Position'
        );
        return response()->json(['message' => 'Cargo eliminado con éxito']);
    }
}

