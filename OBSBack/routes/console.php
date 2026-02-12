<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Incidence;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ReportExpiryNotification;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-desactivar usuarios si pasan 30 días sin iniciar sesión.
// Nota: requiere que el Scheduler esté corriendo (cron -> `php artisan schedule:run`).
Schedule::call(function () {
    $threshold = now()->subDays(30);

    $candidates = User::query()
        ->whereNull('deleted_at')
        ->where('status', 'Activo')
        ->whereRaw('COALESCE(last_login_at, created_at) <= ?', [$threshold])
        ->orderByRaw('COALESCE(last_login_at, created_at) asc')
        ->get();

    $activeCount = User::query()
        ->whereNull('deleted_at')
        ->where('status', 'Activo')
        ->count();

    $activeAdminCount = User::query()
        ->whereNull('deleted_at')
        ->where('status', 'Activo')
        ->where('role', 'Administrador')
        ->count();

    foreach ($candidates as $user) {
        // No dejar el sistema sin al menos 1 usuario Activo
        if ($activeCount <= 1) break;

        $isAdmin = trim((string) ($user->role ?? '')) === 'Administrador';
        if ($isAdmin && $activeAdminCount <= 1) {
            // No desactivar el último admin activo
            continue;
        }

        $user->update(['status' => 'Inactivo']);
        $activeCount--;
        if ($isAdmin) $activeAdminCount--;
    }
})->dailyAt('01:00')->name('users:auto-deactivate');

// Notificar vencimiento de reportes (Incidencias) por fecha de vencimiento.
// - El mismo día: "se va a vencer"
// - El día siguiente: "se venció"
// Reglas de destinatarios:
// - Siempre al creador (reported_by_user_id) si existe
// - Si es General: también a todos los usuarios con rol tipo Sistema/Super usuario
Schedule::call(function () {
    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();

    $privRoleNames = Role::query()
        ->whereIn('user_type', ['Sistema', 'Super usuario'])
        ->pluck('name')
        ->map(fn ($x) => (string) $x)
        ->all();

    $privUsers = User::query()
        ->whereNull('deleted_at')
        ->where('status', 'Activo')
        ->whereIn('role', $privRoleNames)
        ->get(['id', 'name', 'email', 'role']);

    $notify = function (Incidence $inc, string $kind, string $title, string $body) use ($privUsers) {
        $targets = [];

        // Creador
        if ($inc->reported_by_user_id) {
            $u = User::query()
                ->where('id', $inc->reported_by_user_id)
                ->whereNull('deleted_at')
                ->first();
            if ($u) $targets[$u->id] = $u;
        }

        // General: admins/super usuarios
        if (trim((string) $inc->type) === 'General') {
            foreach ($privUsers as $u) {
                $targets[$u->id] = $u;
            }
        }

        if (!count($targets)) return;

        foreach ($targets as $u) {
            $u->notify(new ReportExpiryNotification(
                $title,
                $body,
                $kind,
                [
                    'incidence_id' => $inc->id,
                    'type' => $inc->type,
                    'title' => $inc->title,
                    'due_date' => $inc->due_date,
                ]
            ));
        }
    };

    // Vence hoy
    $dueToday = Incidence::query()
        ->whereNotNull('due_date')
        ->whereDate('due_date', $today)
        ->whereNull('due_today_notified_at')
        ->get();

    foreach ($dueToday as $inc) {
        $notify(
            $inc,
            'report_due_today',
            'Reporte por vencer',
            "El reporte '{$inc->title}' vence hoy ({$today})."
        );
        $inc->forceFill(['due_today_notified_at' => now()])->save();
    }

    // Venció ayer (avisar al día siguiente)
    $overdue = Incidence::query()
        ->whereNotNull('due_date')
        ->whereDate('due_date', $yesterday)
        ->whereNull('overdue_notified_at')
        ->get();

    foreach ($overdue as $inc) {
        $notify(
            $inc,
            'report_overdue',
            'Reporte vencido',
            "El reporte '{$inc->title}' venció el {$yesterday}."
        );
        $inc->forceFill(['overdue_notified_at' => now()])->save();
    }
})->dailyAt('07:00')->name('reports:expiry-notifications');
