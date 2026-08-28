<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class CustomerPortalController extends Controller
{
    public function loginForm()
    {
        if (Session::has('customer_id')) {
            return redirect()->route('customer.dashboard');
        }

        return view('public.customer-login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        $phoneFormats = [$phone, '0'.ltrim($phone, '0'), '62'.ltrim($phone, '0')];

        $customer = Customer::withoutGlobalScopes()
            ->where(function ($q) use ($phoneFormats) {
                foreach ($phoneFormats as $p) {
                    $q->orWhere('phone', $p);
                }
            })
            ->first();

        if (! $customer) {
            return back()->withInput()->with('error', 'Nomor HP tidak terdaftar.');
        }

        // Jika belum set portal_password, izinkan first-time setup pakai password 6 digit terakhir HP
        if (! $customer->portal_password) {
            $defaultPass = substr($phone, -6);
            if ($validated['password'] !== $defaultPass) {
                return back()->withInput()->with('error', 'Password belum di-set. Password default = 6 digit terakhir HP.');
            }
        } elseif (! Hash::check($validated['password'], $customer->portal_password)) {
            return back()->withInput()->with('error', 'Password salah.');
        }

        Session::put('customer_id', $customer->id);
        $customer->update(['portal_last_login' => now()]);

        return redirect()->route('customer.dashboard');
    }

    public function dashboard()
    {
        $customer = $this->currentCustomer();
        $invoices = Invoice::withoutGlobalScopes()->where('customer_id', $customer->id)->latest()->limit(10)->get();
        $services = Service::withoutGlobalScopes()->where('customer_id', $customer->id)->with('vehicle', 'repairCategory')->latest('service_date')->limit(10)->get();

        return view('public.customer-dashboard', compact('customer', 'invoices', 'services'));
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate(['new_password' => 'required|min:6']);
        $customer = $this->currentCustomer();
        $customer->update(['portal_password' => Hash::make($validated['new_password'])]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function invoiceDetail($id)
    {
        $customer = $this->currentCustomer();
        $invoice = Invoice::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->with(['items.product', 'paymentRecords.paymentMethod', 'voucherUsages.voucher'])
            ->findOrFail($id);

        $totalPaid = (float) $invoice->paid_amount;
        $remaining = max($invoice->grand_total - $totalPaid, 0);

        return view('public.customer-invoice', compact('customer', 'invoice', 'totalPaid', 'remaining'));
    }

    public function serviceDetail($id)
    {
        $customer = $this->currentCustomer();
        $service = Service::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->with(['vehicle', 'repairCategory', 'technicians', 'jobcardDetail', 'invoice.items'])
            ->findOrFail($id);

        return view('public.customer-service', compact('customer', 'service'));
    }

    public function uploadPayment(Request $request, $id)
    {
        $customer = $this->currentCustomer();
        $invoice = Invoice::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

        $request->validate([
            'payment_proof' => 'required|image|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $path = $request->file('payment_proof')->store('payment-proofs/'.$invoice->id, 'local');

        // Uploading a proof is only an evidence submission. It must NOT mutate
        // payment_status / paid_amount — those are owned by PaymentService and
        // only change after admin verifies and records the actual payment.
        $invoice->update([
            'payment_proof' => $path,
        ]);

        ActivityLog::record('payment.proof_upload', $invoice, "Customer upload bukti bayar invoice {$invoice->invoice_number}");

        return back()->with('success', 'Bukti pembayaran berhasil diupload. Admin akan memverifikasi.');
    }

    public function logout()
    {
        Session::forget('customer_id');

        return redirect()->route('customer.login')->with('success', 'Logout berhasil.');
    }

    private function currentCustomer(): Customer
    {
        $id = Session::get('customer_id');
        abort_unless($id, 403);

        return Customer::withoutGlobalScopes()->findOrFail($id);
    }
}
