@extends('admin.layouts.app')

@section('title', 'General Ledger Journal Entries')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">General Ledger Journal Entries</h1>
            <p class="text-muted small mb-0">Complete audit trail of all financial debits and credits triggered by Purchases, Sales, and Returns.</p>
        </div>
        <a href="{{ route('admin.accounting.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Accounting Overview
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <form method="GET" action="{{ route('admin.accounting.journal') }}" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search Entry # or Narration..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 14%;">Entry Details</th>
                            <th style="width: 30%;">Narration</th>
                            <th style="width: 28%;">Account Breakdown</th>
                            <th style="width: 14%;" class="text-end">Debit (₹)</th>
                            <th style="width: 14%;" class="text-end">Credit (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            <tr class="table-light border-top border-2 border-primary">
                                <td rowspan="{{ max(1, $entry->items->count() + 1) }}" class="align-top bg-white">
                                    <strong class="font-monospace text-primary">{{ $entry->entry_number }}</strong><br>
                                    <small class="text-muted"><i class="far fa-calendar me-1"></i>{{ $entry->entry_date->format('d M Y') }}</small><br>
                                    <span class="badge bg-secondary small mt-1">{{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}</span>
                                </td>
                                <td rowspan="{{ max(1, $entry->items->count() + 1) }}" class="align-top bg-white">
                                    <p class="mb-1 text-dark">{{ $entry->narration }}</p>
                                    <small class="text-muted">Recorded: {{ $entry->created_at->format('d M Y, h:i A') }}</small>
                                </td>
                            </tr>
                            @foreach($entry->items as $item)
                                <tr>
                                    <td>
                                        <span class="font-monospace text-muted small">[{{ $item->account->code }}]</span>
                                        <strong>{{ $item->account->name }}</strong>
                                    </td>
                                    <td class="text-end font-monospace {{ $item->debit > 0 ? 'fw-bold text-success' : 'text-muted' }}">
                                        {{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}
                                    </td>
                                    <td class="text-end font-monospace {{ $item->credit > 0 ? 'fw-bold text-danger' : 'text-muted' }}">
                                        {{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
                                    <p class="mb-0">No journal entries found matching criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($entries->hasPages())
            <div class="card-footer bg-white">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
