<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Screen;
use App\Models\User;
use App\Models\UserScreenOverride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScreenController extends Controller
{
    // Roles con acceso total (ignoran role_screen; siempre obtienen todas las pantallas)
    private const FULL_ACCESS_ROLES = ['Super usuario', 'Sistema'];

    // Roles autorizados a ver/editar overrides por usuario
    private const MANAGE_OVERRIDES_ROLES = ['Super usuario', 'Sistema', 'Administrador'];

    private function isFullAccessRoleName(string $roleName): bool
    {
        $roleName = trim($roleName);
        foreach (self::FULL_ACCESS_ROLES as $r) {
            if (strcasecmp($roleName, $r) === 0) return true;
        }
        return false;
    }

    private function canManageOverridesRoleName(string $roleName): bool
    {
        $roleName = trim($roleName);
        foreach (self::MANAGE_OVERRIDES_ROLES as $r) {
            if (strcasecmp($roleName, $r) === 0) return true;
        }
        return false;
    }

    public function index(): JsonResponse
    {
        $screens = Screen::orderBy('group')->orderBy('sort_order')->orderBy('label')->get();
        return response()->json($screens);
    }

    // Devuelve las pantallas asignadas a un rol (por key)
    public function roleScreens(Role $role): JsonResponse
    {
        // Devolver también permisos por acción (pivot)
        $rows = $role->screens()
            ->get(['screens.key', 'role_screen.can_create', 'role_screen.can_edit', 'role_screen.can_delete']);

        $screenPermissions = [];
        $keys = [];
        foreach ($rows as $s) {
            $k = (string) $s->key;
            $keys[] = $k;
            $screenPermissions[$k] = [
                'can_create' => (bool) $s->pivot->can_create,
                'can_edit' => (bool) $s->pivot->can_edit,
                'can_delete' => (bool) $s->pivot->can_delete,
            ];
        }

        return response()->json([
            'role_id' => $role->id,
            'role' => $role->name,
            // compat: lista simple
            'screen_keys' => $keys,
            // nuevo: map con permisos
            'screen_permissions' => $screenPermissions,
        ]);
    }

    // Guarda pantallas asignadas a un rol (por key)
    public function updateRoleScreens(Request $request, Role $role): JsonResponse
    {
        $beforeKeys = $role->screens()->pluck('screens.key')->map(fn ($k) => (string) $k)->all();

        $roleType = trim((string) ($role->user_type ?? ''));
        if (strcasecmp($role->name, 'Super usuario') === 0 || strcasecmp($roleType, 'Super usuario') === 0) {
            return response()->json(['message' => 'El rol Super usuario siempre tiene todas las pantallas y no se le pueden quitar permisos.'], 403);
        }

        // Nuevo formato: screen_permissions (con acciones)
        // Compatibilidad: si viene screen_keys (formato viejo), se guardan con todo en true.
        $validated = $request->validate([
            'screen_keys' => ['array'],
            'screen_keys.*' => ['string', 'max:200', Rule::exists('screens', 'key')],

            'screen_permissions' => ['array'],
            'screen_permissions.*.key' => ['required_with:screen_permissions', 'string', 'max:200', Rule::exists('screens', 'key')],
            'screen_permissions.*.can_create' => ['boolean'],
            'screen_permissions.*.can_edit' => ['boolean'],
            'screen_permissions.*.can_delete' => ['boolean'],
        ]);

        $syncData = [];

        if (!empty($validated['screen_permissions'])) {
            $items = $validated['screen_permissions'];
            $keys = array_values(array_unique(array_map(fn ($x) => (string) ($x['key'] ?? ''), $items)));
            $keys = array_values(array_filter($keys, fn ($k) => $k !== ''));
            $screens = Screen::whereIn('key', $keys)->get(['id', 'key'])->keyBy('key');

            foreach ($items as $it) {
                $k = (string) ($it['key'] ?? '');
                if ($k === '' || !isset($screens[$k])) continue;

                $canCreate = array_key_exists('can_create', $it) ? (bool) $it['can_create'] : true;
                $canEdit = array_key_exists('can_edit', $it) ? (bool) $it['can_edit'] : true;
                $canDelete = array_key_exists('can_delete', $it) ? (bool) $it['can_delete'] : true;

                // Si todas las acciones están en false, interpretarlo como "sin acceso".
                // En ese caso simplemente no se asigna esta pantalla al rol (equivale a quitarla).
                if (!$canCreate && !$canEdit && !$canDelete) {
                    continue;
                }

                $syncData[$screens[$k]->id] = [
                    'can_create' => $canCreate,
                    'can_edit' => $canEdit,
                    'can_delete' => $canDelete,
                ];
            }
        } else {
            $keys = $validated['screen_keys'] ?? [];
            $screenIds = Screen::whereIn('key', $keys)->pluck('id');
            foreach ($screenIds as $id) {
                $syncData[(int) $id] = ['can_create' => true, 'can_edit' => true, 'can_delete' => true];
            }
        }

        $role->screens()->sync($syncData);

        $afterKeys = $role->screens()->pluck('screens.key')->map(fn ($k) => (string) $k)->all();
        AuditLog::record(
            $request,
            'role_screens_updated',
            "Permisos (pantallas) actualizados para rol: {$role->name}",
            [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'before_screen_keys' => $beforeKeys,
                'after_screen_keys' => $afterKeys,
            ],
            (int) $role->id,
            'Role'
        );

        return response()->json([
            'message' => 'Pantallas actualizadas.',
            'role_id' => $role->id,
            'screen_keys' => $role->screens()->pluck('screens.key'),
        ]);
    }

    // Pantallas permitidas del usuario autenticado (por key)
    public function myScreens(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleName = $user ? (string) ($user->role ?? '') : '';
        $roleName = trim($roleName);

        if ($roleName === '') {
            return response()->json(['screen_keys' => []]);
        }

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return response()->json(['screen_keys' => []]);
        }

        // Base: permisos por rol (o todo si es privilegiado)
        $baseKeys = [];
        if ($this->isFullAccessRoleName((string) $role->name)) {
            $baseKeys = Screen::pluck('key')->map(fn ($k) => (string) $k)->all();
        } else {
            $baseKeys = $role->screens()->pluck('screens.key')->map(fn ($k) => (string) $k)->all();
        }

        // Overrides por usuario (deny tiene prioridad)
        $allow = [];
        $deny = [];
        if ($user) {
            $overrides = UserScreenOverride::where('user_id', $user->id)->get(['screen_key', 'allowed']);
            foreach ($overrides as $o) {
                $k = (string) $o->screen_key;
                if ((bool) $o->allowed) $allow[] = $k;
                else $deny[] = $k;
            }
        }

        $allowed = array_values(array_unique(array_merge($baseKeys, $allow)));
        if (count($deny)) {
            $denySet = array_flip(array_map('strval', $deny));
            $allowed = array_values(array_filter($allowed, fn ($k) => !isset($denySet[(string) $k])));
        }

        return response()->json(['screen_keys' => $allowed]);
    }

    // Overrides por usuario: ver estado (solo para Administrador/Sistema)
    public function userScreens(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $actorRole = $actor ? (string) ($actor->role ?? '') : '';
        if (!$this->canManageOverridesRoleName($actorRole)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $roleName = trim((string) ($user->role ?? ''));
        $role = $roleName !== '' ? Role::where('name', $roleName)->first() : null;

        $baseKeys = [];
        if ($role && $this->isFullAccessRoleName((string) $role->name)) {
            $baseKeys = Screen::pluck('key')->map(fn ($k) => (string) $k)->all();
        } elseif ($role) {
            $baseKeys = $role->screens()->pluck('screens.key')->map(fn ($k) => (string) $k)->all();
        }

        $overrides = UserScreenOverride::where('user_id', $user->id)->get(['screen_key', 'allowed']);
        $allow = [];
        $deny = [];
        foreach ($overrides as $o) {
            $k = (string) $o->screen_key;
            if ((bool) $o->allowed) $allow[] = $k;
            else $deny[] = $k;
        }

        $effective = array_values(array_unique(array_merge($baseKeys, $allow)));
        if (count($deny)) {
            $denySet = array_flip(array_map('strval', $deny));
            $effective = array_values(array_filter($effective, fn ($k) => !isset($denySet[(string) $k])));
        }

        return response()->json([
            'user_id' => $user->id,
            'role' => $roleName,
            'role_screen_keys' => $baseKeys,
            'override_allow_keys' => array_values(array_unique($allow)),
            'override_deny_keys' => array_values(array_unique($deny)),
            'effective_screen_keys' => $effective,
        ]);
    }

    // Overrides por usuario: guardar (replace) (solo para Administrador/Sistema)
    public function updateUserScreens(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $actorRole = $actor ? (string) ($actor->role ?? '') : '';
        if (!$this->canManageOverridesRoleName($actorRole)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $before = UserScreenOverride::where('user_id', $user->id)->get(['screen_key', 'allowed']);
        $beforeAllow = [];
        $beforeDeny = [];
        foreach ($before as $o) {
            $k = (string) $o->screen_key;
            if ((bool) $o->allowed) $beforeAllow[] = $k;
            else $beforeDeny[] = $k;
        }

        $validated = $request->validate([
            'allow_keys' => ['array'],
            'allow_keys.*' => ['string', 'max:200', Rule::exists('screens', 'key')],
            'deny_keys' => ['array'],
            'deny_keys.*' => ['string', 'max:200', Rule::exists('screens', 'key')],
        ]);

        $allow = array_values(array_unique(array_map('strval', $validated['allow_keys'] ?? [])));
        $deny = array_values(array_unique(array_map('strval', $validated['deny_keys'] ?? [])));

        // Prioridad deny: si un key aparece en ambos, se queda en deny
        if (count($allow) && count($deny)) {
            $denySet = array_flip($deny);
            $allow = array_values(array_filter($allow, fn ($k) => !isset($denySet[(string) $k])));
        }

        DB::transaction(function () use ($user, $allow, $deny) {
            UserScreenOverride::where('user_id', $user->id)->delete();

            $now = now();
            $rows = [];
            foreach ($allow as $k) {
                $rows[] = ['user_id' => $user->id, 'screen_key' => $k, 'allowed' => true, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach ($deny as $k) {
                $rows[] = ['user_id' => $user->id, 'screen_key' => $k, 'allowed' => false, 'created_at' => $now, 'updated_at' => $now];
            }
            if (count($rows)) {
                UserScreenOverride::insert($rows);
            }
        });

        AuditLog::record(
            $request,
            'user_screens_updated',
            "Permisos (pantallas) actualizados para usuario: {$user->name}",
            [
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
                'before_allow_keys' => array_values(array_unique($beforeAllow)),
                'before_deny_keys' => array_values(array_unique($beforeDeny)),
                'after_allow_keys' => $allow,
                'after_deny_keys' => $deny,
            ],
            (int) $user->id,
            'User'
        );

        return $this->userScreens($request, $user);
    }
}

