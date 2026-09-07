<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * Catat 1 baris histori aktivitas.
     *
     * @param Request     $request     request yang lagi jalan (buat ambil user login)
     * @param string      $action      'create' | 'update' | 'delete' | 'approve' | 'import' | dst
     * @param string      $subjectType nama entity, misal 'Part', 'Po'
     * @param int|null    $subjectId   id dari entity terkait
     * @param string      $description kalimat manusiawi, misal "Update price part 71876-0K400"
     * @param array       $meta        data tambahan opsional (before/after value dll)
     */
    public static function log(Request $request, string $action, string $subjectType, ?int $subjectId, string $description, array $meta = []): void
    {
        ActivityLog::create([
            'user_id'      => $request->user()->id ?? null,
            'user_name'    => $request->user()->name ?? 'System',
            'role'         => $request->user()->role ?? null,
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'description'  => $description,
            'meta'         => $meta ?: null,
        ]);
    }
}
