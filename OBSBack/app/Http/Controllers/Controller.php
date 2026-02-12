<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AdminEventNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Notification;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function notifyAdmins(?Request $request, string $title, string $body, string $kind, array $meta = []): void
    {
        $admins = User::query()
            ->where('role', 'Administrador')
            ->where('status', 'Activo')
            ->get();

        if ($admins->isEmpty()) return;

        $actor = $request?->user();
        if ($actor && !empty($actor->name)) {
            $meta = array_merge([
                'actor_id' => $actor->id ?? null,
                'actor_name' => $actor->name,
            ], $meta);
        }

        Notification::send($admins, new AdminEventNotification($title, $body, $kind, $meta));
    }
}
