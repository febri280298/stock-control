<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    // GET /activity-logs (role admin dicek di middleware route)
    public function index(Request $request)
    {
        $query = ActivityLog::orderBy('created_at', 'desc');

        if ($request->action) {
            $query->where('action', $request->action);
        }
        if ($request->subject_type) {
            $query->where('subject_type', $request->subject_type);
        }

        return response()->json($query->paginate(50));
    }

    // GET /activity-logs/export -> data lengkap (ga dibatesin per halaman), buat di-export ke Excel
    public function export(Request $request)
    {
        $query = ActivityLog::orderBy('created_at', 'desc');

        if ($request->action) {
            $query->where('action', $request->action);
        }
        if ($request->subject_type) {
            $query->where('subject_type', $request->subject_type);
        }

        return response()->json($query->get());
    }
}
