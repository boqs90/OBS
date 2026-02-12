<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private function userType(?User $user): string
    {
        if (!$user) return 'Usuario normal';
        $roleName = trim((string) ($user->role ?? ''));
        if ($roleName === '') return 'Usuario normal';

        $type = Role::query()->where('name', $roleName)->value('user_type');
        $type = trim((string) $type);
        return $type !== '' ? $type : 'Usuario normal';
    }

    private function canViewAll(?User $user): bool
    {
        $type = $this->userType($user);
        return in_array($type, ['Sistema', 'Super usuario'], true);
    }

    public function index(Request $request): JsonResponse
    {
        $me = $request->user();
        $viewAll = $this->canViewAll($me);

        $perPage = (int) ($request->query('per_page', 25));
        if ($perPage < 1) $perPage = 25;
        if ($perPage > 200) $perPage = 200;

        $q = trim((string) $request->query('q', ''));
        $action = trim((string) $request->query('action', ''));
        $userIdFilter = $request->query('user_id');

        $query = AuditLog::query()
            ->with(['actorUser:id,name,email,role,status'])
            ->orderByDesc('created_at');

        if (!$viewAll) {
            $query->where('actor_user_id', optional($me)->id);
        } else {
            if ($userIdFilter !== null && $userIdFilter !== '') {
                $query->where('actor_user_id', (int) $userIdFilter);
            }
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('action', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%")
                    ->orWhere('subject_type', 'like', "%{$q}%")
                    ->orWhereRaw('CAST(subject_id AS CHAR) like ?', ["%{$q}%"])
                    ->orWhereHas('actorUser', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('role', 'like', "%{$q}%");
                    });
            });
        }

        $result = $query->paginate($perPage);

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
                'can_view_all' => $viewAll,
                'user_type' => $this->userType($me),
            ],
        ]);
    }
}

