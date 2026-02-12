<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    private static function detectDeviceType(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);
        if ($ua === '') return 'Desconocido';

        // Bots / tools
        if (str_contains($ua, 'bot') || str_contains($ua, 'spider') || str_contains($ua, 'crawler')) {
            return 'Bot';
        }

        // Tablets (algunos iPad traen "Mobile" también, pero suele contener "ipad")
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'Tablet';
        }

        // Móviles
        if (
            str_contains($ua, 'mobi') ||
            str_contains($ua, 'android') ||
            str_contains($ua, 'iphone') ||
            str_contains($ua, 'ipod')
        ) {
            return 'Móvil';
        }

        // Desktop
        if (str_contains($ua, 'windows') || str_contains($ua, 'macintosh') || str_contains($ua, 'linux')) {
            return 'Escritorio';
        }

        return 'Desconocido';
    }

    public static function record(
        Request $request,
        string $action,
        ?string $description = null,
        array $meta = [],
        ?int $subjectId = null,
        ?string $subjectType = null,
        ?int $actorUserId = null
    ): void
    {
        try {
            $actorId = $actorUserId ?? optional($request->user())->id;
            $uaFull = (string) $request->userAgent();
            $deviceType = static::detectDeviceType($uaFull);

            if (!array_key_exists('device_type', $meta)) {
                $meta['device_type'] = $deviceType;
            }
            if (!array_key_exists('user_agent_full', $meta) && $uaFull !== '') {
                // Guardar el UA completo en meta por si excede 255 chars.
                $meta['user_agent_full'] = $uaFull;
            }

            static::create([
                'actor_user_id' => $actorId,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => substr($uaFull, 0, 255),
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            // No romper flujos críticos por fallas de auditoría.
        }
    }
}

