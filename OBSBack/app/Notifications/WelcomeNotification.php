<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
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
            'title' => '¡Bienvenido!',
            'body' => "Hola {$name}, bienvenido a Olanchito Bilingual School.",
            'kind' => 'welcome',
        ];
    }
}

