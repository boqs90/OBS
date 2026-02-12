<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AdminEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $body,
        protected string $kind = 'admin_event',
        protected array $meta = [],
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => Str::limit($this->title, 200, '…'),
            'body' => Str::limit($this->body, 200, '…'),
            'kind' => $this->kind,
            'meta' => $this->meta,
        ];
    }
}

