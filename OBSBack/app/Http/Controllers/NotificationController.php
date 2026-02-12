<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Notifications\AdminEventNotification;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    private function userType(?object $user): string
    {
        if (!$user || !isset($user->role)) return 'Usuario normal';
        $roleName = trim((string) $user->role);
        if ($roleName === '') return 'Usuario normal';
        $type = Role::query()->where('name', $roleName)->value('user_type');
        $type = trim((string) $type);
        return $type !== '' ? $type : 'Usuario normal';
    }

    private function canViewAdminFeed(?object $user): bool
    {
        $type = $this->userType($user);
        return in_array($type, ['Sistema', 'Super usuario'], true);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        try {
            $limit = (int) $request->query('limit', 50);
            if ($limit <= 0) $limit = 50;
            if ($limit > 200) $limit = 200;

            $q = $user->notifications()->orderByDesc('created_at')->limit($limit);

            // Regla:
            // - Usuarios normales: no ver notificaciones generales del sistema (AdminEventNotification).
            // - Sistema/Super usuario: ver TODO (incluye AdminEventNotification + otras).
            if (!$this->canViewAdminFeed($user)) {
                $q->where('type', '!=', AdminEventNotification::class);
            }

            $notifications = $q
                ->get()
                ->map(function (DatabaseNotification $n) {
                    return [
                        'id' => $n->id,
                        'title' => data_get($n->data, 'title'),
                        'body' => data_get($n->data, 'body'),
                        'kind' => data_get($n->data, 'kind'),
                        'read_at' => $n->read_at,
                        'created_at' => $n->created_at,
                    ];
                })
                ->values();

            $unreadQ = $user->unreadNotifications();
            if (!$this->canViewAdminFeed($user)) {
                $unreadQ->where('type', '!=', AdminEventNotification::class);
            }

            return response()->json([
                'data' => $notifications,
                'unread_count' => $unreadQ->count(),
            ]);
        } catch (QueryException $e) {
            // Si falta la tabla `notifications` (migraciones pendientes), no romper el header.
            return response()->json([
                'data' => [],
                'unread_count' => 0,
            ]);
        }

    }

    public function readAll(Request $request)
    {
        $user = $request->user();

        try {
            $unreadQ = $user->unreadNotifications();
            if ($this->isAdmin($user)) {
                $unreadQ->where('type', AdminEventNotification::class);
            } else {
                $unreadQ->where('type', '!=', AdminEventNotification::class);
            }
            $unreadQ->get()->markAsRead();
        } catch (QueryException $e) {
            // Si falta la tabla, no romper.
        }

        return response()->json([
            'message' => 'Notificaciones marcadas como leídas.',
        ]);
    }

    public function readOne(Request $request, string $id)
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $id)->first();
        if (!$notification) {
            return response()->json(['message' => 'Notificación no encontrada.'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notificación marcada como leída.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $id)->first();
        if (!$notification) {
            return response()->json(['message' => 'Notificación no encontrada.'], 404);
        }

        AuditLog::record(
            $request,
            'notification_deleted',
            'Notificación eliminada',
            ['notification_id' => $notification->id, 'type' => $notification->type],
            null,
            null
        );

        $notification->delete();

        return response()->json([
            'message' => 'Notificación eliminada.',
        ]);
    }
}

