<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Screen;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    private const USER_TYPES = ['Sistema', 'Usuario normal', 'Super usuario'];
    private const SUPER_USER_ROLE_NAME = 'Super usuario';
    private const ADMIN_ROLE_NAME = 'Administrador';

    private function grantAllScreens(Role $role): void
    {
        try {
            $screenIds = Screen::query()->pluck('id');
            $role->screens()->sync($screenIds);
        } catch (\Throwable $e) {
            // No romper flujos si faltan migraciones/tablas.
        }
    }

    private function normalizeRoleName(?string $name): string
    {
        // Trim + colapsar espacios múltiples
        $name = trim((string) $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        return $name;
    }

    public function index()
    {
        return response()->json(Role::orderBy('is_system', 'desc')->orderBy('name')->get());
    }

    public function screensHierarchy()
    {
        try {
            $hierarchy = Screen::getHierarchy();
            return response()->json($hierarchy);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Intenta nuevamente, no hay conexión en este momento. Te agradecemos por tu paciencia.',
                'error' => 'database_connection_error'
            ], 503);
        }
    }

    public function store(Request $request)
    {
        $name = $this->normalizeRoleName($request->input('name'));
        $payload = [
            'name' => $name,
            'user_type' => $request->input('user_type'),
        ];

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:200|unique:roles,name',
            'user_type' => 'required|string|in:' . implode(',', self::USER_TYPES),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Evitar duplicados por mayúsculas/minúsculas (y espacios ya normalizados arriba)
        $exists = Role::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un rol con ese nombre.',
                'errors' => ['name' => ['Ya existe un rol con ese nombre.']],
            ], 422);
        }

        // Solo el rol "Super usuario" puede tener el tipo "Super usuario"
        if ($validated['user_type'] === 'Super usuario' && $validated['name'] !== self::SUPER_USER_ROLE_NAME) {
            return response()->json([
                'message' => 'Solo el rol Super usuario puede ser del tipo Super usuario.',
                'errors' => ['user_type' => ['Solo el rol Super usuario puede ser del tipo Super usuario.']],
            ], 422);
        }

        $role = Role::create([
            'name' => $validated['name'],
            'user_type' => $validated['user_type'],
            // Solo "Super usuario" queda fijo (no editable/borrable).
            'is_system' => ($validated['name'] === self::SUPER_USER_ROLE_NAME),
        ]);

        // Por defecto: Super usuario y Administrador nacen con todas las pantallas (Administrador luego se puede ajustar).
        if ($role->name === self::SUPER_USER_ROLE_NAME || $role->name === self::ADMIN_ROLE_NAME) {
            $this->grantAllScreens($role);
        }

        AuditLog::record(
            $request,
            'role_created',
            "Rol creado: {$role->name}",
            ['role_id' => $role->id, 'user_type' => $role->user_type],
            (int) $role->id,
            'Role'
        );

        $this->notifyAdmins(
            $request,
            'Nuevo rol creado',
            "Se creó el rol '{$role->name}'.",
            'role_created',
            ['role_id' => $role->id, 'role_name' => $role->name]
        );

        return response()->json(['message' => 'Rol creado con éxito', 'role' => $role], 201);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === self::SUPER_USER_ROLE_NAME || (string) ($role->user_type ?? '') === 'Super usuario') {
            return response()->json(['message' => 'El rol Super usuario no se puede modificar.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_type' => 'required|string|in:' . implode(',', self::USER_TYPES),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // No permitir que otros roles se vuelvan "Super usuario"
        if ($validated['user_type'] === 'Super usuario' && $role->name !== self::SUPER_USER_ROLE_NAME) {
            return response()->json([
                'message' => 'Solo el rol Super usuario puede ser del tipo Super usuario.',
                'errors' => ['user_type' => ['Solo el rol Super usuario puede ser del tipo Super usuario.']],
            ], 422);
        }

        $before = [
            'user_type' => $role->user_type,
            'is_system' => $role->is_system,
        ];

        $role->update([
            'user_type' => $validated['user_type'],
            // Mantener consistencia: solo Super usuario es fijo.
            'is_system' => ($role->name === self::SUPER_USER_ROLE_NAME),
        ]);

        AuditLog::record(
            $request,
            'role_updated',
            "Rol actualizado: {$role->name}",
            [
                'role_id' => $role->id,
                'before' => $before,
                'after' => ['user_type' => $role->user_type, 'is_system' => $role->is_system],
            ],
            (int) $role->id,
            'Role'
        );

        return response()->json(['message' => 'Rol actualizado con éxito', 'role' => $role]);
    }

    public function destroy(Request $request, Role $role)
    {
        // Super usuario no se puede borrar.
        if ($role->name === self::SUPER_USER_ROLE_NAME || (string) ($role->user_type ?? '') === 'Super usuario') {
            return response()->json(['message' => 'El rol Super usuario no se puede borrar.'], 403);
        }

        try {
            $meta = ['role_id' => $role->id, 'role_name' => $role->name, 'user_type' => $role->user_type];
            $role->delete();

            AuditLog::record(
                $request,
                'role_deleted',
                "Rol eliminado: {$meta['role_name']}",
                $meta,
                (int) $meta['role_id'],
                'Role'
            );
            return response()->json(['message' => 'Rol eliminado con éxito']);
        } catch (QueryException $e) {
            // Integridad referencial (rol en uso)
            // MySQL: 1451 / SQLSTATE 23000
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            if ($sqlState === '23000' || $driverCode === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar este rol porque está asociado a registros (por ejemplo, usuarios).'
                ], 409);
            }
            throw $e;
        }
    }
}

