<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Incidence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Incidence::query()->with(['teacher:id,fullName,email', 'reportedBy:id,name,email']);

        // filtros opcionales
        if ($request->filled('type') && $request->input('type') !== 'all') {
            $q->where('type', $request->input('type'));
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('severity') && $request->input('severity') !== 'all') {
            $q->where('severity', $request->input('severity'));
        }
        if ($request->filled('teacher_id')) {
            $q->where('teacher_id', $request->integer('teacher_id'));
        }
        if ($request->filled('from')) {
            $q->whereDate('occurred_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('occurred_at', '<=', $request->input('to'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('occurred_at')->orderByDesc('id')->get();
        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['General', 'Maestro'])],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'severity' => ['required', Rule::in(['Baja', 'Media', 'Alta'])],
            'status' => ['required', Rule::in(['Abierta', 'En Proceso', 'Resuelta'])],
            'occurred_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        if (($validated['type'] ?? null) === 'Maestro' && empty($validated['teacher_id'])) {
            return response()->json(['message' => 'Selecciona un maestro para una incidencia de tipo "Maestro".'], 422);
        }
        if (($validated['type'] ?? null) === 'General') {
            $validated['teacher_id'] = null;
        }

        $user = $request->user();
        if ($user) $validated['reported_by_user_id'] = $user->id;

        $created = Incidence::create($validated);

        AuditLog::record(
            $request,
            'incidence_created',
            'Incidencia registrada',
            [
                'incidence_id' => $created->id,
                'type' => $created->type,
                'status' => $created->status,
                'severity' => $created->severity,
                'teacher_id' => $created->teacher_id,
            ],
            (int) $created->id,
            'Incidence'
        );
        return response()->json($created->load(['teacher:id,fullName,email', 'reportedBy:id,name,email']), 201);
    }

    public function update(Request $request, Incidence $incidence): JsonResponse
    {
        $before = [
            'type' => $incidence->type,
            'teacher_id' => $incidence->teacher_id,
            'title' => $incidence->title,
            'severity' => $incidence->severity,
            'status' => $incidence->status,
            'occurred_at' => $incidence->occurred_at,
            'due_date' => $incidence->due_date,
        ];

        $validated = $request->validate([
            'type' => ['required', Rule::in(['General', 'Maestro'])],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'severity' => ['required', Rule::in(['Baja', 'Media', 'Alta'])],
            'status' => ['required', Rule::in(['Abierta', 'En Proceso', 'Resuelta'])],
            'occurred_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        if (($validated['type'] ?? null) === 'Maestro' && empty($validated['teacher_id'])) {
            return response()->json(['message' => 'Selecciona un maestro para una incidencia de tipo "Maestro".'], 422);
        }
        if (($validated['type'] ?? null) === 'General') {
            $validated['teacher_id'] = null;
        }

        $incidence->update($validated);

        AuditLog::record(
            $request,
            'incidence_updated',
            'Incidencia actualizada',
            [
                'incidence_id' => $incidence->id,
                'before' => $before,
                'after' => [
                    'type' => $incidence->type,
                    'teacher_id' => $incidence->teacher_id,
                    'title' => $incidence->title,
                    'severity' => $incidence->severity,
                    'status' => $incidence->status,
                    'occurred_at' => $incidence->occurred_at,
                    'due_date' => $incidence->due_date,
                ],
            ],
            (int) $incidence->id,
            'Incidence'
        );
        return response()->json($incidence->fresh()->load(['teacher:id,fullName,email', 'reportedBy:id,name,email']));
    }

    public function destroy(Request $request, Incidence $incidence): JsonResponse
    {
        $meta = [
            'incidence_id' => $incidence->id,
            'type' => $incidence->type,
            'status' => $incidence->status,
            'severity' => $incidence->severity,
            'teacher_id' => $incidence->teacher_id,
        ];
        $incidence->delete();

        AuditLog::record(
            $request,
            'incidence_deleted',
            'Incidencia eliminada',
            $meta,
            (int) $meta['incidence_id'],
            'Incidence'
        );
        return response()->json(['message' => 'Incidencia eliminada.']);
    }
}
