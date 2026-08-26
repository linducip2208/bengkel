<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailLog::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('to', 'like', "%{$request->search}%")
                    ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }
        $logs = $query->latest()->paginate(30)->withQueryString();

        return view('email-logs.index', compact('logs'));
    }

    public function show(EmailLog $emailLog)
    {
        return view('email-logs.show', ['log' => $emailLog]);
    }

    public function destroy(EmailLog $emailLog)
    {
        $emailLog->delete();

        return redirect()->route('email-logs.index')->with('success', 'Log dihapus.');
    }
}
