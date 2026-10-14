<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index()
    {
        $accounts = ChartOfAccount::with(['items'])->orderBy('code')->get();

        $accountsByType = [
            'asset' => $accounts->where('type', 'asset'),
            'liability' => $accounts->where('type', 'liability'),
            'equity' => $accounts->where('type', 'equity'),
            'revenue' => $accounts->where('type', 'revenue'),
            'expense' => $accounts->where('type', 'expense'),
        ];

        $recentEntries = JournalEntry::with(['items.account', 'creator'])
            ->latest()
            ->take(10)
            ->get();

        $totalAssets = $accountsByType['asset']->sum(fn ($a) => $a->currentBalance());
        $totalLiabilities = $accountsByType['liability']->sum(fn ($a) => $a->currentBalance());
        $totalEquity = $accountsByType['equity']->sum(fn ($a) => $a->currentBalance());
        $totalRevenue = $accountsByType['revenue']->sum(fn ($a) => $a->currentBalance());
        $totalExpense = $accountsByType['expense']->sum(fn ($a) => $a->currentBalance());

        return view('admin.accounting.index', compact(
            'accountsByType',
            'recentEntries',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'totalRevenue',
            'totalExpense'
        ));
    }

    public function journal(Request $request)
    {
        $query = JournalEntry::with(['items.account', 'creator'])->latest('entry_date')->latest('id');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                  ->orWhere('narration', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date_to);
        }

        $entries = $query->paginate(20)->withQueryString();

        return view('admin.accounting.journal', compact('entries'));
    }
}
