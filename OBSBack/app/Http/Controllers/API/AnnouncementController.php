<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    // Obtener los anuncios activos y vigentes
    public function index()
    {
        try {
            $now = Carbon::now();

            $announcements = Announcement::where('active', true)
                ->where(function ($query) use ($now) {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($announcements);
        } catch (QueryException $e) {
            // Si falta la tabla `announcements` (migraciones pendientes), no romper el frontend.
            return response()->json([]);
        }
    }

    // Crear nuevo anuncio
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $announcement = Announcement::create($data);

        AuditLog::record(
            $request,
            'announcement_created',
            'Anuncio creado',
            ['announcement_id' => $announcement->id, 'title' => $announcement->title, 'active' => (bool) $announcement->active],
            (int) $announcement->id,
            'Announcement'
        );

        return response()->json($announcement, 201);
    }

    // Actualizar anuncio existente
    public function update(Request $request, Announcement $announcement)
    {
        $before = [
            'title' => $announcement->title,
            'message' => $announcement->message,
            'active' => (bool) $announcement->active,
            'starts_at' => $announcement->starts_at,
            'ends_at' => $announcement->ends_at,
        ];

        $data = $request->validate([
            'title' => 'string|max:255',
            'message' => 'string',
            'active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $announcement->update($data);

        AuditLog::record(
            $request,
            'announcement_updated',
            'Anuncio actualizado',
            [
                'announcement_id' => $announcement->id,
                'before' => $before,
                'after' => [
                    'title' => $announcement->title,
                    'message' => $announcement->message,
                    'active' => (bool) $announcement->active,
                    'starts_at' => $announcement->starts_at,
                    'ends_at' => $announcement->ends_at,
                ],
            ],
            (int) $announcement->id,
            'Announcement'
        );

        return response()->json($announcement);
    }

    // Eliminar anuncio
    public function destroy(Request $request, Announcement $announcement)
    {
        $meta = ['announcement_id' => $announcement->id, 'title' => $announcement->title, 'active' => (bool) $announcement->active];
        $announcement->delete();

        AuditLog::record(
            $request,
            'announcement_deleted',
            'Anuncio eliminado',
            $meta,
            (int) $meta['announcement_id'],
            'Announcement'
        );

        return response()->json(['message' => 'Anuncio eliminado correctamente']);
    }
}

