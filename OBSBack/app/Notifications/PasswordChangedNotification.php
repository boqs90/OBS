<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $name = $notifiable->name ?? 'usuario';

        return [
            'title' => 'Contraseña actualizada',
            'body' => "Hola {$name}, tu contraseña fue actualizada correctamente. Si no fuiste tú, contacta a un administrador de inmediato.",
            'kind' => 'password_changed',
        ];
    }
}

