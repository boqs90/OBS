<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User; // Asegúrate de que el modelo se llama "User"
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use App\Notifications\WelcomeNotification;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
   private const ADMIN_ROLE = 'Administrador';

   private function canRemoveUser(User $user): ?string
   {
      // Solo consideramos usuarios NO eliminados (soft delete)
      $aliveCount = User::query()->count();

      // No permitir eliminar el último usuario
      if ($aliveCount <= 1) {
         return 'No se puede eliminar el último usuario del sistema.';
      }

      // No permitir eliminar el último Administrador
      $adminCount = User::query()->where('role', self::ADMIN_ROLE)->count();
      if (trim((string) ($user->role ?? '')) === self::ADMIN_ROLE && $adminCount <= 1) {
         return 'No se puede eliminar el último usuario Administrador del sistema.';
      }

      // Si al eliminar quedaría solo 1 usuario, ese usuario debe ser Administrador
      if ($aliveCount === 2) {
         $other = User::query()->where('id', '!=', $user->id)->first();
         if ($other && trim((string) ($other->role ?? '')) !== self::ADMIN_ROLE) {
            return 'No se puede eliminar este usuario porque el último usuario restante debe ser Administrador.';
         }
      }

      return null;
   }
   public function index() 
   {
      // Incluye eliminados (soft delete) para poder filtrarlos en frontend
      return response()->json(User::withTrashed()->get());
   }

   public function obtener_usuario(User $user){
      return response()->json($user);
   }

   public function ingresar_usuarios(Request $request)
{
    // Validación
    $validacion = Validator::make($request->all(), [
        'name' => 'required|string|max:200|unique:users,name',
        'email' => 'required|email|max:200|unique:users,email',
        'role' => 'required|string|exists:roles,name',
        'status' => 'required|string|in:Activo,Inactivo',
        'password' => 'required|string|min:6'
    ]);

    if ($validacion->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validacion->errors()
        ], 422);
    }

    // Crear usuario
    $usuario = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'role' => $request->role,
        'status' => $request->status,
        'password' => Hash::make($request->password),
    ]);

    AuditLog::record(
        $request,
        'user_created',
        "Usuario creado: {$usuario->name} ({$usuario->email})",
        ['target_user_id' => $usuario->id, 'target_user_email' => $usuario->email],
        (int) $usuario->id,
        'User'
    );

    // Notificación de bienvenida al usuario recién creado (si la tabla `notifications` existe).
    try {
        $usuario->notify(new WelcomeNotification());
    } catch (QueryException $e) {
        // Si faltan migraciones, no romper la creación.
    }

    $this->notifyAdmins(
        $request,
        'Nuevo usuario creado',
        "Se creó el usuario '{$usuario->name}' ({$usuario->email}).",
        'user_created',
        ['user_id' => $usuario->id, 'user_email' => $usuario->email]
    );

    return response()->json([
        'success' => true,
        'message' => 'Usuario registrado correctamente',
        'data' => $usuario
    ], 201);
}

   public function update(Request $request, User $user)
   {
      // Si está eliminado (soft delete), no permitir cambios: queda como registro
      if (!empty($user->deleted_at)) {
         return response()->json([
            'success' => false,
            'message' => 'Este usuario está eliminado y solo se mantiene como registro.',
         ], 403);
      }

      // Si está desactivado, no permitir cambios: solo se puede volver a activar
      if (($user->status ?? null) === 'Inactivo') {
         return response()->json([
            'success' => false,
            'message' => 'Este usuario está desactivado. Solo se permite volver a activarlo.',
         ], 403);
      }

      $validacion = Validator::make($request->all(), [
         'name' => 'required|string|max:200|unique:users,name,' . $user->id,
         'role' => 'required|string|exists:roles,name',
         'status' => 'required|string|in:Activo,Inactivo',
         'password' => 'nullable|string|min:6',
      ]);

      if ($validacion->fails()) {
         return response()->json([
            'success' => false,
            'errors' => $validacion->errors()
         ], 422);
      }

      // En edición: permitir actualizar nombre/rol/estado. No permitir cambiar correo.
      $data = $validacion->validated();
      $updateData = [
         'name' => $data['name'],
         'role' => $data['role'],
         'status' => $data['status'],
      ];

      // Si viene password, se actualiza SIEMPRE hasheada.
      if (array_key_exists('password', $data) && $data['password'] !== null && $data['password'] !== '') {
         error_log('USER UPDATE: Setting password and is_temp_password=true for user ' . $user->email);
         $updateData['password'] = Hash::make($data['password']);
         $updateData['is_temp_password'] = true; // Marcar como contraseña temporal
      } else {
         error_log('USER UPDATE: No password provided for user ' . $user->email);
      }

      $user->update($updateData);

      AuditLog::record(
         $request,
         'user_updated',
         "Usuario actualizado: {$user->name}",
         ['target_user_id' => $user->id, 'target_user_email' => $user->email],
         (int) $user->id,
         'User'
      );

      return response()->json([
         'success' => true,
         'message' => 'Usuario actualizado correctamente',
         'data' => $user
      ]);
   }

   public function destroy(Request $request, User $user)
   {
      // Si está eliminado (soft delete), no permitir acciones: queda como registro
      if (!empty($user->deleted_at)) {
         return response()->json([
            'success' => false,
            'message' => 'Este usuario está eliminado y solo se mantiene como registro.',
         ], 403);
      }

      // Si está desactivado, no permitir eliminar: solo se puede volver a activar
      if (($user->status ?? null) === 'Inactivo') {
         return response()->json([
            'success' => false,
            'message' => 'Este usuario está desactivado. Solo se permite volver a activarlo.',
         ], 403);
      }

      $blockReason = $this->canRemoveUser($user);
      if ($blockReason) {
         return response()->json([
            'success' => false,
            'message' => $blockReason,
         ], 403);
      }

      // Eliminar (soft delete)
      // Nota: el listado usa withTrashed() para poder ver "Eliminados" en frontend.
      // Guardar fecha de finalización
      if (empty($user->ended_at)) {
         $user->forceFill(['ended_at' => now()])->save();
      }
      $user->delete();

      AuditLog::record(
         $request,
         'user_deleted',
         "Usuario eliminado: {$user->name}",
         ['target_user_id' => $user->id, 'target_user_email' => $user->email, 'ended_at' => optional($user->ended_at)->toDateTimeString()],
         (int) $user->id,
         'User'
      );

      return response()->json([
         'success' => true,
         'message' => 'Usuario eliminado correctamente',
      ]);
   }

   /**
    * Guardar permisos individuales del usuario (overrides)
    */
   public function savePermissions(Request $request, User $user)
   {
      // Validar que el usuario exista y no esté eliminado
      if (!empty($user->deleted_at)) {
         return response()->json([
            'success' => false,
            'message' => 'Este usuario está eliminado y no se pueden modificar sus permisos.',
         ], 403);
      }

      // Validar los datos de entrada
      $validated = $request->validate([
         'screen_permissions' => 'required|array',
         'screen_permissions.*.can_create' => 'required|boolean',
         'screen_permissions.*.can_edit' => 'required|boolean',
         'screen_permissions.*.can_delete' => 'required|boolean',
      ]);

      try {
         // Eliminar permisos anteriores del usuario
         DB::table('user_screen_overrides')
            ->where('user_id', $user->id)
            ->delete();

         // Insertar nuevos permisos individuales
         $now = now();
         $insertData = [];
         
         foreach ($validated['screen_permissions'] as $screenKey => $permissions) {
            // Solo guardar si al menos un permiso está activo
            if ($permissions['can_create'] || $permissions['can_edit'] || $permissions['can_delete']) {
               $insertData[] = [
                  'user_id' => $user->id,
                  'screen_key' => $screenKey,
                  'can_create' => $permissions['can_create'],
                  'can_edit' => $permissions['can_edit'],
                  'can_delete' => $permissions['can_delete'],
                  'created_at' => $now,
                  'updated_at' => $now,
               ];
            }
         }

         if (!empty($insertData)) {
            DB::table('user_screen_overrides')->insert($insertData);
         }

         // Registrar en auditoría
         AuditLog::record(
            $request,
            'user_permissions_updated',
            "Permisos individuales actualizados para usuario: {$user->name}",
            [
               'target_user_id' => $user->id,
               'target_user_email' => $user->email,
               'permissions_count' => count($insertData),
            ],
            (int) $user->id,
            'User'
         );

         return response()->json([
            'success' => true,
            'message' => 'Permisos individuales guardados correctamente',
            'permissions_saved' => count($insertData),
         ]);

      } catch (\Exception $e) {
         Log::error('Error guardando permisos de usuario: ' . $e->getMessage());
         
         return response()->json([
            'success' => false,
            'message' => 'Error al guardar los permisos individuales: ' . $e->getMessage(),
         ], 500);
      }
   }

   /**
    * Obtener permisos individuales del usuario (overrides)
    */
   public function getPermissions(User $user)
   {
      // Validar que el usuario exista y no esté eliminado
      if (!empty($user->deleted_at)) {
         return response()->json([
            'success' => false,
            'message' => 'Este usuario está eliminado.',
         ], 403);
      }

      try {
         // Obtener permisos individuales del usuario
         $userPermissions = DB::table('user_screen_overrides')
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('screen_key');

         // Formatear respuesta
         $formattedPermissions = [];
         foreach ($userPermissions as $screenKey => $permission) {
            $formattedPermissions[$screenKey] = [
               'can_create' => (bool) $permission->can_create,
               'can_edit' => (bool) $permission->can_edit,
               'can_delete' => (bool) $permission->can_delete,
            ];
         }

         return response()->json($formattedPermissions);

      } catch (\Exception $e) {
         Log::error('Error obteniendo permisos de usuario: ' . $e->getMessage());
         
         return response()->json([
            'success' => false,
            'message' => 'Error al obtener los permisos individuales',
         ], 500);
      }
   }

   public function deactivate(Request $request, User $user)
   {
      // Si está eliminado (soft delete), no permitir cambios: queda como registro
      if (!empty($user->deleted_at)) {
         return response()->json([
            'success' => false,
            'message' => 'Este usuario está eliminado y solo se mantiene como registro.',
         ], 403);
      }

      // Si ya está desactivado, no hacer nada
      if (($user->status ?? null) === 'Inactivo') {
         return response()->json([
            'success' => true,
            'message' => 'El usuario ya está desactivado.',
         ]);
      }

      // Evitar desactivar el último usuario o el último Administrador por seguridad
      // (mismas reglas que eliminar, pero sin soft delete)
      $aliveCount = User::query()->count();
      if ($aliveCount <= 1) {
         return response()->json([
            'success' => false,
            'message' => 'No se puede desactivar el último usuario del sistema.',
         ], 403);
      }

      $adminCount = User::query()->where('role', self::ADMIN_ROLE)->count();
      if (trim((string) ($user->role ?? '')) === self::ADMIN_ROLE && $adminCount <= 1) {
         return response()->json([
            'success' => false,
            'message' => 'No se puede desactivar el último usuario Administrador del sistema.',
         ], 403);
      }

      // Desactivar (sin eliminar)
      if ($user->status !== 'Inactivo') {
         $user->update(['status' => 'Inactivo']);
      }

      AuditLog::record(
         $request,
         'user_deactivated',
         "Usuario desactivado: {$user->name}",
         ['target_user_id' => $user->id, 'target_user_email' => $user->email],
         (int) $user->id,
         'User'
      );

      return response()->json([
         'success' => true,
         'message' => 'Usuario desactivado correctamente',
      ]);
   }

   public function activate(Request $request, User $user)
   {
      // Si está eliminado (soft delete), no permitir cambios: queda como registro
      if (!empty($user->deleted_at)) {
         return response()->json([
            'success' => false,
            'message' => 'Este usuario está eliminado y solo se mantiene como registro.',
         ], 403);
      }

      // Activar (sin eliminar)
      if (($user->status ?? null) !== 'Activo') {
         $user->update(['status' => 'Activo']);
      }

      AuditLog::record(
         $request,
         'user_activated',
         "Usuario activado: {$user->name}",
         ['target_user_id' => $user->id, 'target_user_email' => $user->email],
         (int) $user->id,
         'User'
      );

      return response()->json([
         'success' => true,
         'message' => 'Usuario activado correctamente',
      ]);
   }
}