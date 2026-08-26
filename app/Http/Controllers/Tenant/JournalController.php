<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    // Chart of Accounts
    public function coaIndex()
    {
        $accounts = ChartOfAccount::orderBy('code')->get()->groupBy('type');

        return view('finance.coa', compact('accounts'));
    }

    public function coaCreate()
    {
        $parents = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        return view('finance.coa-create', compact('parents'));
    }

    public function coaStore(Request $request)
    {
        $v = $request->validate([
            'code' => 'required|unique:chart_of_accounts',
            'name' => 'required',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
        ]);
        ChartOfAccount::create($v);

        return redirect()->route('finance.coa')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function coaDestroy(ChartOfAccount $account)
    {
        $account->delete();

        return back()->with('success', 'Akun dihapus.');
    }

    // Journal Entries
    public function journalIndex()
    {
        $entries = JournalEntry::with('lines.account')->latest()->paginate(20);

        return view('finance.journal', compact('entries'));
    }

    public function journalCreate()
    {
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        return view('finance.journal-create', compact('accounts'));
    }

    public function journalStore(Request $request)
    {
        $this->authorize('journals.manage');

        $v = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        // A manual journal must balance exactly — refuse otherwise.
        $totalDebit = round(collect($v['lines'])->sum(fn ($l) => (float) ($l['debit'] ?? 0)), 2);
        $totalCredit = round(collect($v['lines'])->sum(fn ($l) => (float) ($l['credit'] ?? 0)), 2);

        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) >= 0.01) {
            return back()->withInput()->with('error', 'Jurnal tidak seimbang: total debit Rp '.number_format($totalDebit, 0, ',', '.').' vs total kredit Rp '.number_format($totalCredit, 0, ',', '.').'.');
        }

        $entry = JournalEntry::create([
            'entry_number' => DocumentNumberService::generate(DocumentNumberService::JOURNALS, 'JRN', 'Ym', 4),
            'entry_type' => 'manual',
            'entry_date' => $v['entry_date'],
            'description' => $v['description'],
            'created_by' => auth()->id(),
        ]);

        foreach ($v['lines'] as $line) {
            if (($line['debit'] ?? 0) > 0 || ($line['credit'] ?? 0) > 0) {
                $entry->lines()->create([
                    'chart_of_account_id' => $line['account_id'],
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                ]);
            }
        }

        ActivityLog::record('journal.create', $entry, "Jurnal manual {$entry->entry_number}: D {$totalDebit} / K {$totalCredit}");

        return redirect()->route('finance.journal')->with('success', 'Jurnal berhasil dicatat.');
    }
}
