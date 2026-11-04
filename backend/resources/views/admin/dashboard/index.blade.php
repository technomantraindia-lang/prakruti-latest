@extends('admin.layouts.app')
@section('title', 'Dashboard')

@push('styles')
<style>
    .dashboard-hero { background: linear-gradient(135deg, #173d20 0%, #2d6a3d 62%, #82b64a 100%); color: #fff; border-radius: 18px; padding: 24px; box-shadow: 0 12px 28px rgba(23,61,32,.18); }
    .dashboard-hero .muted { color: rgba(255,255,255,.72); }
    .range-pills .btn { border-radius: 999px; font-size: .78rem; padding: .45rem .8rem; }
    .range-pills .btn.active { background: #fff; color: #1d512b; border-color: #fff; font-weight: 700; }
    .metric-card { border: 0; overflow: hidden; height: 100%; }
    .metric-card .metric-icon { width: 42px; height: 42px; border-radius: 13px; display: grid; place-items: center; font-size: 1.05rem; }
    .metric-card .value { font-size: 1.55rem; font-weight: 800; letter-spacing: -.03em; }
    .metric-card .label { color: #64748b; font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .chart-card { border: 0; height: 100%; }
    .chart-wrap { position: relative; min-height: 270px; }
    .chart-wrap.tall { min-height: 310px; }
    .trend-table td, .trend-table th { padding: .65rem .75rem; }
    .product-rank { border-bottom: 1px solid #eef2ee; padding: 12px 0; }
    .product-rank:last-child { border-bottom: 0; }
    .product-rank .rank { width: 28px; height: 28px; border-radius: 9px; display: grid; place-items: center; background: #eff8ef; color: #2d6a3d; font-size: .75rem; font-weight: 800; }
    .product-rank .name { font-weight: 700; font-size: .86rem; }
    .product-rank .meta { color: #94a3b8; font-size: .72rem; }
    .dashboard-section-title { font-size: 1rem; font-weight: 800; color: #193a15; }
    .table thead th { white-space: nowrap; font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .custom-dates { display: none; }
    .custom-dates.show { display: flex; }
    .dashboard-alert { color: inherit; transition: transform .15s ease, box-shadow .15s ease; }
    .dashboard-alert:hover { color: inherit; transform: translateY(-1px); box-shadow: 0 .25rem .75rem rgba(0,0,0,.08); }
    .dashboard-page { width: 100%; max-width: 1920px; margin: 0 auto; }
    .dashboard-page .card-body { padding: clamp(1rem, 1.35vw, 1.5rem); }
    .dashboard-page .table-responsive { overflow-x: auto; }
    .dashboard-page .inventory-table { min-width: 760px; }
    .dashboard-page .orders-table { min-width: 590px; }
    @media (min-width: 1200px) and (max-width: 1440px) {
        .dashboard-page .dashboard-hero { padding: 20px; border-radius: 15px; }
        .dashboard-page .metric-card .card-body { padding: 1rem .9rem; gap: .7rem !important; }
        .dashboard-page .metric-card .metric-icon { width: 38px; height: 38px; font-size: .95rem; }
        .dashboard-page .metric-card .value { font-size: 1.35rem; }
        .dashboard-page .metric-card .label { font-size: .7rem; }
        .dashboard-page .product-rank { padding: 10px 0; }
        .dashboard-page .product-rank .name { font-size: .8rem; }
    }
    @media (min-width: 1800px) {
        .dashboard-page .dashboard-hero { padding: 28px 32px; }
        .dashboard-page .chart-wrap { min-height: 300px; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 dashboard-page">
    <div class="dashboard-hero mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="small text-uppercase fw-bold opacity-75 mb-2">Store performance</div>
                <h1 class="h3 fw-bold mb-1">Good {{ now()->format('A') === 'AM' ? 'morning' : 'evening' }}, {{ auth()->user()?->name ?? 'Admin' }}</h1>
                <div class="muted small">{{ $rangeLabel }} · revenue, orders, product demand and margin</div>
            </div>
            <a href="{{ route('admin.inward.create') }}" class="btn btn-light text-success fw-bold"><i class="fas fa-plus me-1"></i> Record stock</a>
        </div>
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-4">
            <div class="range-pills d-flex flex-wrap gap-2">
                @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $key => $label)
                    <a class="btn btn-sm btn-outline-light {{ $rangeKey === $key ? 'active' : '' }}" href="{{ route('admin.dashboard', ['range' => $key]) }}">{{ $label }}</a>
                @endforeach
                <button class="btn btn-sm btn-outline-light {{ $rangeKey === 'custom' ? 'active' : '' }}" type="button" id="customRangeToggle"><i class="far fa-calendar me-1"></i> Custom</button>
            </div>
            <span class="small muted"><i class="fas fa-circle me-1" style="font-size:.45rem"></i> Live from orders and inventory</span>
        </div>
        <form method="GET" class="custom-dates align-items-end gap-2 mt-3 {{ $rangeKey === 'custom' ? 'show' : '' }}" id="customRangeForm">
            <input type="hidden" name="range" value="custom">
            <div><label class="small muted d-block mb-1">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}"></div>
            <div><label class="small muted d-block mb-1">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}"></div>
            <button class="btn btn-sm btn-light text-success fw-bold">Apply dates</button>
        </form>
    </div>

    <div class="row g-3 mb-4">
        @php($cards = [
            ['label' => 'Sales in period', 'value' => '₹'.number_format($stats['total_sales'], 0), 'icon' => 'fa-indian-rupee-sign', 'tone' => 'success', 'sub' => $stats['period_orders'].' orders'],
            ['label' => 'Units sold', 'value' => number_format($stats['period_units']), 'icon' => 'fa-basket-shopping', 'tone' => 'primary', 'sub' => 'selected period'],
            ['label' => 'Gross margin', 'value' => '₹'.number_format($stats['period_margin'], 0), 'icon' => 'fa-chart-line', 'tone' => 'warning', 'sub' => 'sales less product cost'],
            ['label' => 'Inventory value', 'value' => '₹'.number_format($stats['inventory_value'], 0), 'icon' => 'fa-boxes-stacked', 'tone' => 'info', 'sub' => 'at purchase cost'],
        ])
        @foreach($cards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="metric-icon bg-{{ $card['tone'] }} bg-opacity-10 text-{{ $card['tone'] }}"><i class="fas {{ $card['icon'] }}"></i></div>
                        <div><div class="label">{{ $card['label'] }}</div><div class="value">@if(in_array($card['label'], ['Sales in period', 'Gross margin', 'Inventory value'], true))&#8377;{{ preg_replace('/[^0-9,.\-]/', '', $card['value']) }}@else{{ $card['value'] }}@endif</div><div class="small text-muted">{{ $card['sub'] }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card chart-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2"><div><div class="dashboard-section-title">Sales trend</div><div class="small text-muted">Revenue and order volume over {{ strtolower($rangeKey) }} view</div></div><span class="badge bg-success bg-opacity-10 text-success">{{ number_format($stats['period_orders']) }} orders</span></div>
                    <div class="chart-wrap"><canvas id="salesTrendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card chart-card shadow-sm">
                <div class="card-body">
                    <div class="dashboard-section-title">Sales by category</div><div class="small text-muted mb-2">Share of selected-period revenue</div>
                    <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-4"><div class="card chart-card shadow-sm"><div class="card-body"><div class="dashboard-section-title">Highest selling products</div><div class="small text-muted mb-2">Ranked by units sold</div><div id="highestSellingList"></div></div></div></div>
        <div class="col-12 col-xl-4"><div class="card chart-card shadow-sm"><div class="card-body"><div class="dashboard-section-title">High margin products</div><div class="small text-muted mb-2">Ranked by gross contribution</div><div id="highMarginList"></div></div></div></div>
        <div class="col-12 col-xl-4"><div class="card chart-card shadow-sm"><div class="card-body"><div class="dashboard-section-title">Low selling products</div><div class="small text-muted mb-2">Products needing attention in this period</div><div id="lowSellingList"></div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-2"><div><div class="dashboard-section-title">Recent orders</div><div class="small text-muted">Latest customer activity</div></div><a href="{{ route('admin.orders.index') }}" class="small text-success fw-bold text-decoration-none">View all</a></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0 orders-table"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>
                @forelse($recentOrders as $order)<tr><td class="fw-bold font-monospace">{{ $order->order_num }}</td><td>{{ $order->user?->name ?? 'Guest' }}</td><td>₹{{ number_format($order->total, 0) }}</td><td><span class="badge bg-{{ $order->statusBadgeClass() }}">{{ ucfirst($order->status) }}</span></td><td class="text-muted">{{ $order->created_at->format('d M, H:i') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No orders yet</td></tr>@endforelse
                </tbody></table></div>
            </div></div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card shadow-sm h-100"><div class="card-body"><div class="dashboard-section-title mb-3">Inventory alerts</div>
                <a href="{{ route('admin.products.index', ['low_stock' => 1]) }}" class="dashboard-alert d-flex justify-content-between align-items-center p-3 rounded-3 bg-danger bg-opacity-10 mb-2 text-decoration-none" title="Open low stock products"><span><i class="fas fa-arrow-trend-down text-danger me-2"></i>Low stock products</span><strong class="text-danger">{{ $stats['low_stock_products'] }}</strong></a>
                <a href="{{ route('admin.inventory.batches', ['near_expiry_only' => 1]) }}" class="dashboard-alert d-flex justify-content-between align-items-center p-3 rounded-3 bg-warning bg-opacity-10 mb-2 text-decoration-none" title="Open batches expiring within 60 days"><span><i class="fas fa-hourglass-half text-warning me-2"></i>Expiry alerts (60 days)</span><strong class="text-warning">{{ $stats['expiry_alerts'] }}</strong></a>
                <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="dashboard-alert d-flex justify-content-between align-items-center p-3 rounded-3 bg-info bg-opacity-10 text-decoration-none" title="Open pending orders"><span><i class="fas fa-clock text-info me-2"></i>Pending orders</span><strong class="text-info">{{ $stats['pending_orders'] }}</strong></a>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="dashboard-section-title">Inventory &amp; expiry date</div>
                    <div class="small text-muted">Current product stock, package, MRP, batch and nearest expiry from inventory.</div>
                </div>
                <a href="{{ route('admin.inventory.batches') }}" class="small text-success fw-bold text-decoration-none">Open inventory</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 inventory-table">
                    <thead><tr><th>Product</th><th>Package</th><th>Stock</th><th>MRP</th><th>Batch</th><th>Expiry date</th></tr></thead>
                    <tbody>
                    @forelse($inventoryRows as $row)
                        <tr>
                            <td><strong>{{ $row['product_name'] }}</strong><br><small class="text-muted">{{ $row['sku'] }}</small></td>
                            <td>{{ $row['package'] }}</td>
                            <td>{{ number_format($row['stock']) }}</td>
                            <td>&#8377;{{ number_format($row['mrp'], 2) }}</td>
                            <td>{{ $row['batch_number'] ?: 'Not recorded' }}</td>
                            <td class="{{ $row['expiry_date'] ? 'fw-bold' : 'text-muted' }}">{{ $row['expiry_date'] ?: 'Not recorded' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No inventory records yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($trend);
    const categories = @json($categoryChart);
    const highestSelling = @json($highestSelling);
    const highMargin = @json($highMargin);
    const lowSelling = @json($lowSelling);
    const money = value => '₹' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });

    new Chart(document.getElementById('salesTrendChart'), { type: 'line', data: { labels: trend.map(item => item.label), datasets: [
        { label: 'Sales', data: trend.map(item => item.sales), borderColor: '#2d6a3d', backgroundColor: 'rgba(45,106,61,.12)', fill: true, tension: .35, yAxisID: 'y' },
        { label: 'Orders', data: trend.map(item => item.orders), borderColor: '#e3a93b', backgroundColor: 'transparent', tension: .35, yAxisID: 'y1' }
    ] }, options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'top', align: 'end' }, tooltip: { callbacks: { label: ctx => ctx.dataset.label === 'Sales' ? ' Sales: ' + money(ctx.raw) : ' Orders: ' + ctx.raw } } }, scales: { y: { beginAtZero: true, ticks: { callback: value => money(value) }, grid: { color: '#eef2ee' } }, y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } }, x: { grid: { display: false } } } } });

    const categoryLabels = Object.keys(categories);
    new Chart(document.getElementById('categoryChart'), { type: 'doughnut', data: { labels: categoryLabels.length ? categoryLabels : ['No sales in selected period'], datasets: [{ data: categoryLabels.length ? Object.values(categories) : [1], backgroundColor: ['#2d6a3d','#7cae4c','#e3a93b','#3b82a8','#b8792b','#94a3b8'], borderWidth: 3, borderColor: '#fff' }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '66%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } }, tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + money(ctx.raw) } } } } });

    function renderRanks(id, items, mode) {
        const el = document.getElementById(id);
        if (!items.length) { el.innerHTML = '<div class="text-muted small py-4 text-center">No product sales in this period.</div>'; return; }
        el.innerHTML = items.map((item, index) => {
            const value = mode === 'units' ? `${Number(item.units).toLocaleString('en-IN')} units` : mode === 'margin' ? `${money(item.margin)} · ${item.margin_pct}%` : `${Number(item.units).toLocaleString('en-IN')} units`;
            const sub = mode === 'margin' ? `Revenue ${money(item.revenue)}` : `Revenue ${money(item.revenue)}`;
            return `<div class="product-rank d-flex align-items-center gap-2"><div class="rank">${index + 1}</div><div class="flex-grow-1 min-w-0"><div class="name text-truncate">${item.name}</div><div class="meta">${item.sku} · ${sub}</div></div><div class="text-end"><div class="fw-bold small">${value}</div>${mode === 'low' ? '<div class="text-danger small">Review</div>' : ''}</div></div>`;
        }).join('');
    }
    renderRanks('highestSellingList', highestSelling, 'units');
    renderRanks('highMarginList', highMargin, 'margin');
    renderRanks('lowSellingList', lowSelling, 'low');

    document.getElementById('customRangeToggle')?.addEventListener('click', () => document.getElementById('customRangeForm').classList.toggle('show'));
});
</script>
@endpush
