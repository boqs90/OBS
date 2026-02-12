<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\WelcomeNotification;
use App\Models\AuditLog;

class AuthController extends Controller
{
    private const ADMIN_ROLE = 'Administrador';
    private const AUTO_DEACTIVATE_DAYS = 30;

    // Registro de usuarios
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200|unique:users,name',
            'email' => 'required|string|email|max:200|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Notificación de bienvenida al crear el usuario (si la tabla `notifications` existe).
        try {
            $user->notify(new WelcomeNotification());
        } catch (QueryException $e) {
            // Si faltan migraciones, no romper el registro.
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // Login de usuarios
    public function login(Request $request)
    {
        error_log('LOGIN REQUEST: ' . ($request->email ?? ''));

        $request->validate([
            // En el frontend este campo es "Correo o usuario"
            'email' => 'required|string|max:200',
            'password' => 'required|string|max:200',
        ]);

        try {
            $identifier = trim((string) $request->email);
            $plainPassword = trim((string) $request->password);

            $hasDeletedAt = Schema::hasColumn('users', 'deleted_at');

            $userBaseQuery = $hasDeletedAt
                ? User::withTrashed()
                : User::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);

            // Permitir login por correo o por nombre de usuario (name).
            $user = $userBaseQuery->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                  ->orWhere('name', $identifier);
            })->first();

            error_log('LOGIN USER FOUND: ' . ($user ? 'yes' : 'no'));

            if (! $user) {
                return response()->json([
                    'message' => 'Las credenciales son incorrectas.',
                ], 401);
            }

            // Verificar contraseña - manejar tanto hasheadas como temporales (texto plano)
            $passwordValid = false;
            $isTempPassword = false;

            // 1. Primero verificar contra contraseña hasheada normal
            if (Hash::check($request->password, $user->password)) {
                $passwordValid = true;
                // Check if this is a temporary password
                $isTempPassword = $user->is_temp_password ?? false;
                error_log('PASSWORD VALID: is_temp_password=' . ($isTempPassword ? 'true' : 'false') . ' for user ' . $user->email);
            } else {
                error_log('PASSWORD INVALID for user ' . $user->email . '. Request password: ' . $request->password . ', DB hash: ' . $user->password);
            }

            if (! $passwordValid) {
                return response()->json([
                    'message' => 'Las credenciales son incorrectas.',
                ], 401);
            }

            // Si está eliminado (soft delete), NO revelar detalles; mensaje prudente.
            if ($hasDeletedAt && !empty($user->deleted_at)) {
                return response()->json([
                    'message' => 'No se puede iniciar sesión con esta cuenta. Contacta al administrador.',
                ], 403);
            }

            // Si la contraseña era temporal (texto plano), forzar cambio de contraseña
            if ($isTempPassword) {
                error_log('FORCE_PASSWORD_CHANGE: true for user ' . $user->email);
                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'message' => 'Inicio de sesión exitoso, pero debe cambiar su contraseña.',
                    'user' => $user,
                    'token' => $token,
                    'force_password_change' => true,
                ]);
            } else {
                error_log('FORCE_PASSWORD_CHANGE: false for user ' . $user->email);
            }

            // Si lleva 1 mes sin iniciar sesión, desactivar automáticamente.
            // Reglas de seguridad: nunca dejar el sistema sin al menos 1 usuario Activo, ni sin al menos 1 Administrador Activo.
            $threshold = now()->subDays(self::AUTO_DEACTIVATE_DAYS);
            $lastActivityAt = $user->last_login_at ?? $user->created_at;
            $isAdmin = trim((string) ($user->role ?? '')) === self::ADMIN_ROLE;

            if ($lastActivityAt && $lastActivityAt->lte($threshold) && trim((string) ($user->status ?? '')) === 'Activo') {
                $activeUsersQuery = $hasDeletedAt
                    ? User::query()->whereNull('deleted_at')
                    : User::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);

                $activeCount = (clone $activeUsersQuery)
                    ->where('status', 'Activo')
                    ->count();

                $activeAdminCount = (clone $activeUsersQuery)
                    ->where('status', 'Activo')
                    ->where('role', self::ADMIN_ROLE)
                    ->count();

                $canAutoDeactivate = $activeCount > 1 && (!$isAdmin || $activeAdminCount > 1);
                if ($canAutoDeactivate) {
                    $user->update(['status' => 'Inactivo']);
                    return response()->json([
                        'message' => 'Tu usuario está desactivado. Contacta al administrador.',
                    ], 403);
                }
            }

            // Verificar estado del usuario (solo Activo puede iniciar sesión)
            if (trim((string) ($user->status ?? '')) !== 'Activo') {
                return response()->json([
                    'message' => 'Tu usuario está desactivado. Contacta al administrador.',
                ], 403);
            }

            // Marcar último login
            $user->forceFill(['last_login_at' => now()])->save();

            // Auditoría: registro de sesión (incluye hora en created_at, IP y tipo de dispositivo)
            AuditLog::record(
                $request,
                'registro_sesion',
                'El usuario ha ingresado a la aplicación',
                [
                    'identifier' => $identifier,
                    'logged_in_at' => now()->toDateTimeString(),
                ],
                (int) $user->id,
                'User',
                (int) $user->id
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'user' => $user,
                'token' => $token,
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Intenta nuevamente, no hay conexión en este momento. Te agradecemos por tu paciencia.',
                'error' => 'database_connection_error'
            ], 503);
        }
    }

    // Cambio de contraseña (usuario autenticado)
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string|max:200',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.',
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->is_temp_password = false; // Mark as no longer temporary
        $user->save();

        // Notificación por cambio de contraseña (si la tabla `notifications` existe).
        try {
            $user->notify(new PasswordChangedNotification());
        } catch (QueryException $e) {
            // Si faltan migraciones, no romper el flujo.
        }

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    // Logout
    public function logout(Request $request)
{
    $user = $request->user();

    if ($user) {
        AuditLog::record(
            $request,
            'logout',
            "Cierre de sesión: {$user->name}",
            [],
            (int) $user->id,
            'User'
        );

        $user->tokens()->delete();
        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    return response()->json([
        'message' => 'No hay sesión activa',
    ], 401);
}

}
