<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    /**
     * Cara pakai di routes/api.php:
     *   ->middleware('role:admin,marketing')
     *   ->middleware('role:admin,pcd')
     * Role di dalam kurung itu daftar role yang DIIZINKAN, boleh lebih dari satu.
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $userRole = $request->user()->role ?? 'user';

        if (!in_array($userRole, $roles)) {
            return response()->json(['message' => 'Tidak diizinkan untuk aksi ini.'], 403);
        }

        return $next($request);
    }
}
