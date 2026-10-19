@extends('admin.layouts.app')

@section('title', 'Real-Time Double-Entry Accounting')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Accounting & General Ledger</h1>
            <p class="text-muted small mb-0">Automated double-entry bookkeeping synchronized with Purchases, Sales & Returns.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.accounting.journal') }}" class="btn btn-outline-primary">
                <i class="fas fa-book me-1"></i> View Journal Entries
            </a>
            <a href="{{ route('admin.reports.gstr3b') }}" class="btn btn-success">
                <i class="fas fa-file-invoice-dollar me-1"></i> GSTR-3B Tax Return
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary h-100 p-3 bg-white">
                <div class="text-uppercase text-primary fw-bold small">Total Assets</div>
                <div class="h4 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($totalAssets, 2) }}</div>
                <div class="text-muted small mt-1">Cash, Bank, Inventory, Input GST</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-danger h-100 p-3 bg-white">
                <div class="text-uppercase text-danger fw-bold small">Current Liabilities</div>
                <div class="h4 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($totalLiabilities, 2) }}</div>
                <div class="text-muted small mt-1">Accounts Payable, Output GST</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-success h-100 p-3 bg-white">
                <div class="text-uppercase text-success fw-bold small">Total Sales Revenue</div>
                <div class="h4 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($totalRevenue, 2) }}</div>
                <div class="text-muted small mt-1">Product Sales & Shipping</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-warning h-100 p-3 bg-white">
                <div class="text-uppercase text-warning fw-bold small">COGS & Operating Expenses</div>
                <div class="h4 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($totalExpense, 2) }}</div>
                <div class="text-muted small mt-1">Cost of Goods Sold & Handling</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Chart of Accounts -->
        <div class="col-lg-7">
            <div class="card shadow">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list-ol me-2"></i> Indian Standard Chart of Accounts (COA)</h6>
                    <span class="badge bg-primary">Auto-Synchronized</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Account Name</th>
                                    <th>Classification</th>
                                    <th class="text-end">Current Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expenses'] as $type => $label)
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="4" class="py-2 text-uppercase text-dark">{{ $label }}</td>
                                    </tr>
                                    @foreach($accountsByType[$type] as $acc)
                                        @php $bal = $acc->currentBalance(); @endphp
                                        <tr>
                                            <td class="font-monospace fw-bold">{{ $acc->code }}</td>
                                            <td>{{ $acc->name }}</td>
                                            <td><span class="badge bg-light text-dark border">{{ ucfirst($acc->type) }}</span></td>
                                            <td class="text-end fw-bold {{ $bal < 0 ? 'text-danger' : 'text-dark' }}">
                                                ₹{{ number_format(abs($bal), 2) }}
                                                <small class="text-muted">{{ in_array($acc->type, ['asset', 'expense']) ? ($bal >= 0 ? 'Dr' : 'Cr') : ($bal >= 0 ? 'Cr' : 'Dr') }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Journal Entries -->
        <div class="col-lg-5">
            <div class="card shadow">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-2"></i> Recent Journal Entries</h6>
                    <a href="{{ route('admin.accounting.journal') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentEntries as $entry)
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="font-monospace text-primary">{{ $entry->entry_number }}</strong>
                                    <small class="text-muted">{{ $entry->entry_date->format('d M Y') }}</small>
                                </div>
                                <div class="small text-dark mb-2">{{ $entry->narration }}</div>
                                <div class="bg-light p-2 rounded small">
                                    @foreach($entry->items as $item)
                                        <div class="d-flex justify-content-between text-muted">
                                            <span>{{ $item->account->code ?? '' }} - {{ $item->account->name ?? '' }}</span>
                                            <span class="font-monospace">
                                                @if($item->debit > 0)
                                                    <span class="text-success">Dr ₹{{ number_format($item->debit, 2) }}</span>
                                                @else
                                                    <span class="text-danger">Cr ₹{{ number_format($item->credit, 2) }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="text-end mt-2">
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Balanced: ₹{{ number_format($entry->totalDebit(), 2) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-balance-scale fa-2x mb-2 text-secondary"></i>
                                <p class="mb-0">No journal entries recorded yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
