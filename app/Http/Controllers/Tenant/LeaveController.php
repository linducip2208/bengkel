<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $leaves = Leave::with(['user', 'approver'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->latest()->paginate(20)->withQueryString();

        $users = User::orderBy('name')->get();
        $pendingCount = Leave::where('status', 'pending')->count();

        return view('hrm.leaves.index', compact('leaves', 'users', 'pendingCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:cuti,sakit,izin',
            'reason' => 'nullable|string|max:500',
        ]);

        Leave::create($validated + ['status' => 'pending']);

        return back()->with('success', 'Pengajuan cuti berhasil dibuat.');
    }

    public function approve(Leave $leave): RedirectResponse
    {
        $leave->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', 'Cuti disetujui.');
    }

    public function reject(Request $request, Leave $leave): RedirectResponse
    {
        $validated = $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $leave->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'rejection_reason' => $validated['rejection_reason']]);

        return back()->with('success', 'Cuti ditolak.');
    }

    public function destroy(Leave $leave): RedirectResponse
    {
        $leave->delete();

        return back()->with('success', 'Cuti dihapus.');
    }
}
