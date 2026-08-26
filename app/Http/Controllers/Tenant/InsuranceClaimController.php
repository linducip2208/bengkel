<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\InsuranceClaim;
use App\Models\Service;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;

class InsuranceClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = InsuranceClaim::with(['customer', 'vehicle', 'service']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $claims = $query->latest()->paginate(20)->withQueryString();

        return view('insurance-claims.index', compact('claims'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $services = Service::with('customer')->latest('service_date')->limit(200)->get();

        return view('insurance-claims.create', compact('customers', 'services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'insurance_company' => 'nullable|string|max:255',
            'policy_number' => 'nullable|string|max:255',
            'claim_date' => 'required|date',
            'estimated_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['status'] = 'pending';
        $validated['claim_number'] = $this->generateClaimNumber();

        $claim = InsuranceClaim::create($validated);

        ActivityLog::record('insurance-claim.create', $claim, "Klaim asuransi {$claim->claim_number} dibuat");

        return redirect()->route('insurance-claims.show', $claim)->with('success', 'Klaim asuransi berhasil dibuat.');
    }

    public function show(InsuranceClaim $insuranceClaim)
    {
        $insuranceClaim->load(['customer', 'vehicle', 'service']);

        return view('insurance-claims.show', compact('insuranceClaim'));
    }

    public function update(Request $request, InsuranceClaim $insuranceClaim)
    {
        $validated = $request->validate([
            'insurance_company' => 'nullable|string|max:255',
            'policy_number' => 'nullable|string|max:255',
            'claim_date' => 'nullable|date',
            'estimated_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $insuranceClaim->update($validated);
        ActivityLog::record('insurance-claim.update', $insuranceClaim, "Klaim {$insuranceClaim->claim_number} diperbarui");

        return back()->with('success', 'Klaim asuransi diperbarui.');
    }

    public function approve(Request $request, InsuranceClaim $insuranceClaim)
    {
        $validated = $request->validate([
            'approved_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $insuranceClaim->update([
            'status' => 'approved',
            'approved_amount' => $validated['approved_amount'] ?? null,
            'notes' => $validated['notes'] ?? $insuranceClaim->notes,
        ]);

        ActivityLog::record('insurance-claim.approve', $insuranceClaim, "Klaim {$insuranceClaim->claim_number} disetujui");

        return back()->with('success', 'Klaim disetujui.');
    }

    public function reject(Request $request, InsuranceClaim $insuranceClaim)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:5000',
        ]);

        $insuranceClaim->update([
            'status' => 'rejected',
            'notes' => $validated['notes'] ?? $insuranceClaim->notes,
        ]);

        ActivityLog::record('insurance-claim.reject', $insuranceClaim, "Klaim {$insuranceClaim->claim_number} ditolak");

        return back()->with('success', 'Klaim ditolak.');
    }

    public function markPaid(InsuranceClaim $insuranceClaim)
    {
        $insuranceClaim->update(['status' => 'paid']);
        ActivityLog::record('insurance-claim.paid', $insuranceClaim, "Klaim {$insuranceClaim->claim_number} dibayar");

        return back()->with('success', 'Klaim ditandai dibayar.');
    }

    public function destroy(InsuranceClaim $insuranceClaim)
    {
        ActivityLog::record('insurance-claim.delete', $insuranceClaim, "Hapus klaim {$insuranceClaim->claim_number}");
        $insuranceClaim->delete();

        return redirect()->route('insurance-claims.index')->with('success', 'Klaim dihapus.');
    }

    private function generateClaimNumber(): string
    {
        return DocumentNumberService::generate(DocumentNumberService::INSURANCE_CLAIMS, 'ASR', 'Ymd', 3);
    }
}
