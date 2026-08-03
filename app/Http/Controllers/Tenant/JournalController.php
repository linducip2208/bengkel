<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $v = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $entry = JournalEntry::create([
            'entry_number' => 'JRN-' . date('Ym') . '-' . str_pad(JournalEntry::count() + 1, 4, '0', STR_PAD_LEFT),
            'entry_date' => $v['entry_date'],
            'description' => $v['description'],
            'created_by' => auth()->id(),
        ]);

        foreach ($v['lines'] as $line) {
            if (($line['debit'] ?? 0) > 0 || ($line['credit'] ?? 0) > 0) {
                $entry->lines()->create([
                    'chart_of_account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }
        }

        return redirect()->route('finance.journal')->with('success', 'Jurnal berhasil dicatat.');
    }
}
