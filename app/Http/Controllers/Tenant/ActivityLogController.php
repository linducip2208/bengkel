<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('event')) $query->where('event', 'like', "%{$request->event}%");
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        $logs = $query->latest()->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get();
        return view('activity-logs.index', compact('logs', 'users'));
    }
}
