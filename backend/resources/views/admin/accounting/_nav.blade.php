<div class="mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center p-2 bg-white rounded shadow-sm border">
        <a href="{{ route('admin.accounting.index') }}" class="btn btn-sm {{ request()->routeIs('admin.accounting.index') ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="fas fa-balance-scale me-1"></i> Chart of Accounts &amp; Balances
        </a>
        <a href="{{ route('admin.accounting.journal') }}" class="btn btn-sm {{ request()->routeIs('admin.accounting.journal') ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="fas fa-book me-1"></i> Journal Entries
        </a>
        <span class="text-muted d-none d-md-inline mx-1">|</span>
        <a href="{{ route('admin.reports.gstr1') }}" class="btn btn-sm {{ request()->routeIs('admin.reports.gstr1') ? 'btn-success' : 'btn-outline-secondary' }}">
            <i class="fas fa-file-invoice me-1"></i> GSTR-1 (Outward Sales)
        </a>
        <a href="{{ route('admin.reports.gstr2') }}" class="btn btn-sm {{ request()->routeIs('admin.reports.gstr2') ? 'btn-success' : 'btn-outline-secondary' }}">
            <i class="fas fa-receipt me-1"></i> GSTR-2 (Inward ITC)
        </a>
        <a href="{{ route('admin.reports.gstr3b') }}" class="btn btn-sm {{ request()->routeIs('admin.reports.gstr3b') ? 'btn-success' : 'btn-outline-secondary' }}">
            <i class="fas fa-calculator me-1"></i> GSTR-3B (Monthly Summary)
        </a>
    </div>
</div>
