@extends('layouts.app')

@section('title', 'Production Dashboard')

@section('content')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Top Header & Previous Master Metric Cards -->
<div class="content-header" style="margin-bottom: 1.25rem;">
    <div class="content-title">
        <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary);">Production Dashboard</h1>
        <p style="font-size: 0.9rem; color: var(--text-secondary);">Overview and management of production master registers</p>
    </div>
</div>

<div class="metrics-grid">
    <a href="{{ route('masters.party.index') }}" class="metric-card active-card">
        <div class="metric-card-content">
            <span class="metric-card-title">Parties</span>
            <span class="metric-card-value">{{ $partiesCount }}</span>
        </div>
        <div class="metric-card-icon bg-icon-blue">👥</div>
    </a>

    <a href="{{ route('masters.supplier.index') }}" class="metric-card">
        <div class="metric-card-content">
            <span class="metric-card-title">Suppliers</span>
            <span class="metric-card-value">{{ $suppliersCount }}</span>
        </div>
        <div class="metric-card-icon bg-icon-orange">🚚</div>
    </a>

    <a href="{{ route('masters.rollsize.index') }}" class="metric-card">
        <div class="metric-card-content">
            <span class="metric-card-title">Roll Sizes</span>
            <span class="metric-card-value">{{ $rollSizesCount }}</span>
        </div>
        <div class="metric-card-icon bg-icon-cyan">📏</div>
    </a>

    <a href="{{ route('masters.loomnumber.index') }}" class="metric-card">
        <div class="metric-card-content">
            <span class="metric-card-title">Looms</span>
            <span class="metric-card-value">{{ $loomsCount }}</span>
        </div>
        <div class="metric-card-icon bg-icon-green">⚙️</div>
    </a>

    <a href="{{ route('masters.fabriccolor.index') }}" class="metric-card">
        <div class="metric-card-content">
            <span class="metric-card-title">Colors</span>
            <span class="metric-card-value">{{ $colorsCount }}</span>
        </div>
        <div class="metric-card-icon bg-icon-dark">🎨</div>
    </a>
</div>

<!-- ======================================================== -->
<!-- REPORTS SECTION (Col-8 Graph + Col-4 Side Widget Layout) -->
<!-- ======================================================== -->
<div class="dash-reports-container">

    <!-- REPORT 1: MONTHLY PRODUCTION GRAPHS -->
    <div class="dash-section">
        <div class="section-title-bar">
            <div class="section-title-left">
                <div>
                    <h2 class="section-heading">1. Monthly Production Graphs</h2>
                    <span class="section-subtext">Actual Meters woven & Net Weight output across FY {{ $availableFYs[$selectedFY] ?? $selectedFY }}</span>
                </div>
            </div>

            <!-- Compact Inline Filters -->
            <form method="GET" action="{{ route('dashboard') }}" class="compact-inline-filter">
                <select name="fy" onchange="this.form.submit()" title="Financial Year" class="compact-select">
                    @foreach($availableFYs as $year => $label)
                        <option value="{{ $year }}" {{ $selectedFY == $year ? 'selected' : '' }}>FY {{ $label }}</option>
                    @endforeach
                </select>

                <select name="inward" onchange="this.form.submit()" title="Inward Source" class="compact-select">
                    <option value="all" {{ $inward == 'all' ? 'selected' : '' }}>All Sources</option>
                    <option value="prod" {{ $inward == 'prod' ? 'selected' : '' }}>Production</option>
                    <option value="purchase_lam" {{ $inward == 'purchase_lam' ? 'selected' : '' }}>Purchase Lam</option>
                    <option value="purchase" {{ $inward == 'purchase' ? 'selected' : '' }}>Purchase Non-Lam</option>
                </select>

                <a href="{{ route('reports.monthly_production.index', ['inward' => $inward]) }}" class="btn-section-link">
                    Open Table ↗
                </a>
            </form>
        </div>

        <!-- Col-8 + Col-4 Row -->
        <div class="dash-two-col-grid">
            <!-- LEFT (col-sm-8): Dual-Axis Combo Chart -->
            <div class="chart-container-card">
                <div class="chart-card-header">
                    <div class="chart-legend-custom">
                        <span class="legend-badge meter-badge">
                            <span class="legend-circle teal"></span> Actual Meters (Bars)
                        </span>
                        <span class="legend-badge weight-badge">
                            <span class="legend-circle amber"></span> Net Weight kg (Line)
                        </span>
                    </div>
                    <div class="chart-tag">FY {{ $availableFYs[$selectedFY] ?? $selectedFY }} Performance</div>
                </div>
                <div class="chart-canvas-wrapper">
                    <canvas id="monthlyProductionChart"></canvas>
                </div>
            </div>

            <!-- RIGHT (col-sm-4): Side KPI Stats Stack -->
            <div class="side-kpi-stack">
                <div class="kpi-card-compact">
                    <div class="kpi-icon-box amber">⚖️</div>
                    <div class="kpi-content">
                        <span class="kpi-label">Total Net Weight</span>
                        <div class="kpi-number-wrap">
                            <span class="kpi-value">{{ number_format($totalNetWeight, 1) }}</span>
                            <span class="kpi-unit">kg</span>
                        </div>
                        <div class="kpi-trend {{ $growthDirection === 'up' ? 'positive' : ($growthDirection === 'down' ? 'negative' : 'neutral') }}">
                            @if($growthDirection === 'up')
                                <span>↑</span> +{{ abs($growthRate) }}% vs prev month
                            @elseif($growthDirection === 'down')
                                <span>↓</span> -{{ abs($growthRate) }}% vs prev month
                            @else
                                <span>•</span> Steady production
                            @endif
                        </div>
                    </div>
                </div>

                <div class="kpi-card-compact">
                    <div class="kpi-icon-box teal">📏</div>
                    <div class="kpi-content">
                        <span class="kpi-label">Total Actual Meters</span>
                        <div class="kpi-number-wrap">
                            <span class="kpi-value">{{ number_format($totalActualMeters, 0) }}</span>
                            <span class="kpi-unit">m</span>
                        </div>
                        <div class="kpi-trend positive">
                            <span>📊</span> Full Financial Year
                        </div>
                    </div>
                </div>

                <div class="kpi-card-compact">
                    <div class="kpi-icon-box emerald">📈</div>
                    <div class="kpi-content">
                        <span class="kpi-label">Monthly Growth Rate</span>
                        <div class="kpi-number-wrap">
                            <span class="kpi-value">{{ $growthRate > 0 ? '+' : '' }}{{ $growthRate }}%</span>
                        </div>
                        <div class="kpi-trend {{ $growthRate >= 0 ? 'positive' : 'negative' }}">
                            <span>{{ $growthRate >= 0 ? '🟢' : '🔴' }}</span> Recent trend momentum
                        </div>
                    </div>
                </div>

                <div class="kpi-card-compact">
                    <div class="kpi-icon-box ocean">⚙️</div>
                    <div class="kpi-content">
                        <span class="kpi-label">Avg Output / Active Loom</span>
                        <div class="kpi-number-wrap">
                            <span class="kpi-value">{{ number_format($avgOutputPerLoom, 1) }}</span>
                            <span class="kpi-unit">m/day</span>
                        </div>
                        <div class="kpi-trend positive">
                            <span>🧵</span> {{ $loomsCount }} Looms registered
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- REPORT 2: MONTHLY ROLLS TRANSFER & DISPATCH GRAPHS -->
    <div class="dash-section" style="margin-top: 1.5rem;">
        <div class="section-title-bar">
            <div class="section-title-left">
                <div>
                    <h2 class="section-heading">2. Monthly Rolls Transfer & Dispatch Graphs</h2>
                    <span class="section-subtext">Dispatches vs Internal Warehouse Godown Transfers</span>
                </div>
            </div>
            <div class="section-actions">
                <a href="{{ route('reports.monthly_dispatch_transfer.index', ['inward' => $inward]) }}" class="btn-section-link">
                    Open Table ↗
                </a>
            </div>
        </div>

        <!-- Col-8 + Col-4 Row -->
        <div class="dash-two-col-grid">
            <!-- LEFT (col-sm-8): Stacked Bar Chart with Quick Footer -->
            <div class="chart-container-card">
                <div class="chart-card-header">
                    <div class="chart-legend-custom">
                        <span class="legend-badge dispatch-badge">
                            <span class="legend-circle ocean"></span> Dispatches kg
                        </span>
                        <span class="legend-badge transfer-badge">
                            <span class="legend-circle teal"></span> Transfers kg
                        </span>
                    </div>
                    <div class="chart-tag">Monthly Movement (kg)</div>
                </div>
                <div class="chart-canvas-wrapper">
                    <canvas id="monthlyTransferChart"></canvas>
                </div>

                <!-- Bottom Quick Stats Pills inside chart card -->
                <div class="chart-footer-stats">
                    <div class="footer-stat-pill pill-teal">
                        <span class="footer-stat-icon">📦</span>
                        <span class="footer-stat-text">Produced: <strong>{{ number_format($totalRollsInward) }}</strong> rolls</span>
                    </div>
                    <div class="footer-stat-pill pill-ocean">
                        <span class="footer-stat-icon">🚚</span>
                        <span class="footer-stat-text">Dispatched: <strong>{{ number_format($totalDispatchedRolls) }}</strong> rolls ({{ number_format($totalDispatchNw, 1) }} kg)</span>
                    </div>
                    <div class="footer-stat-pill pill-mint">
                        <span class="footer-stat-icon">🔄</span>
                        <span class="footer-stat-text">Transferred: <strong>{{ number_format($totalTransferredRolls) }}</strong> rolls ({{ number_format($totalTransferNw, 1) }} kg)</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT (col-sm-4): Donut Ratio + Pending Store Card -->
            <div class="side-widget-panel">
                <div class="donut-container-card">
                    <div class="donut-card-header">
                        <h3 class="donut-title">Dispatch / Transfer Ratio</h3>
                        <span class="donut-subtitle">Net Weight distribution</span>
                    </div>
                    <div class="donut-canvas-wrapper">
                        <canvas id="transferRatioDonut"></canvas>
                        <div class="donut-center-metric">
                            <span class="donut-center-val">{{ $dispatchPercent }}%</span>
                            <span class="donut-center-lbl">Dispatch</span>
                        </div>
                    </div>
                    <div class="donut-legend-footer">
                        <div class="donut-legend-item">
                            <span class="donut-dot ocean"></span>
                            <div>
                                <div class="donut-name">Direct Dispatches</div>
                                <div class="donut-weight">{{ number_format($totalDispatchNw, 1) }} kg ({{ $dispatchPercent }}%)</div>
                            </div>
                        </div>
                        <div class="donut-legend-item">
                            <span class="donut-dot teal"></span>
                            <div>
                                <div class="donut-name">Internal Transfers</div>
                                <div class="donut-weight">{{ number_format($totalTransferNw, 1) }} kg ({{ $transferPercent }}%)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Warehouse Inventory Card -->
                <div class="pending-stock-card">
                    <div class="pending-stock-header">
                        <span class="pending-stock-icon">⏳</span>
                        <div>
                            <div class="pending-stock-title">Pending In Store</div>
                            <div class="pending-stock-subtitle">Ready for Dispatch / Transfer</div>
                        </div>
                    </div>
                    <div class="pending-stock-value-wrap">
                        <span class="pending-stock-val">{{ number_format($pendingDispatchRolls) }}</span>
                        <span class="pending-stock-unit">Rolls Available</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 3: STORES MODULE ROADMAP (Hidden)
    <div class="dash-section" style="margin-top: 1.5rem;">
        <div class="section-title-bar">
            <div class="section-title-left">
                <div class="section-indicator-dot amber"></div>
                <div>
                    <h2 class="section-heading">Store Inventory Highlights</h2>
                    <span class="section-subtext">Low stock items, spares catalog, store valuation, and pending PIs</span>
                </div>
            </div>
        </div>

        <div class="store-highlights-grid">
            <a href="{{ route('reports.current_stock.index') }}" class="store-tile">
                <div class="store-tile-icon warning">⚠️</div>
                <div class="store-tile-info">
                    <span class="store-tile-num">Low Stock</span>
                    <span class="store-tile-lbl">ITEM Below: Minimum Qty</span>
                </div>
                <span class="store-tile-arrow">→</span>
            </a>

            <a href="{{ route('store.itemmaster.index') }}" class="store-tile">
                <div class="store-tile-icon blue">📦</div>
                <div class="store-tile-info">
                    <span class="store-tile-num">Spares Catalog</span>
                    <span class="store-tile-lbl">Total Spares ITEM</span>
                </div>
                <span class="store-tile-arrow">→</span>
            </a>

            <a href="{{ route('reports.current_stock.index') }}" class="store-tile">
                <div class="store-tile-icon emerald">₹</div>
                <div class="store-tile-info">
                    <span class="store-tile-num">Inventory Value</span>
                    <span class="store-tile-lbl">Current Store Value in Rs.</span>
                </div>
                <span class="store-tile-arrow">→</span>
            </a>

            <a href="{{ route('reports.monthly_material_consumption.index') }}" class="store-tile">
                <div class="store-tile-icon purple">🧪</div>
                <div class="store-tile-info">
                    <span class="store-tile-num">Department Wise</span>
                    <span class="store-tile-lbl">Monthly Consumption Value</span>
                </div>
                <span class="store-tile-arrow">→</span>
            </a>

            <a href="{{ route('store.pi.index') }}" class="store-tile">
                <div class="store-tile-icon red">⏳</div>
                <div class="store-tile-info">
                    <span class="store-tile-num">Overdue PIs</span>
                    <span class="store-tile-lbl">PI Pending > 1 Month</span>
                </div>
                <span class="store-tile-arrow">→</span>
            </a>
        </div>
    </div>
    --}}
</div>

<!-- STYLES -->
<style>
    .dash-reports-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        padding-bottom: 3rem;
    }

    .dash-section {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .section-title-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .section-title-left {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .section-indicator-dot {
        width: 11px;
        height: 11px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .section-indicator-dot.indigo { background: #4f46e5; box-shadow: 0 0 8px rgba(79, 70, 229, 0.4); }
    .section-indicator-dot.blue { background: #2563eb; box-shadow: 0 0 8px rgba(37, 99, 235, 0.4); }
    .section-indicator-dot.amber { background: #f59e0b; box-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }

    .section-heading {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
    }

    .section-subtext {
        font-size: 0.8rem;
        color: #64748b;
    }

    /* 2-Column Grid: Col-8 (68%) + Col-4 (32%) */
    .dash-two-col-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.25rem;
        align-items: stretch;
    }

    @media (max-width: 992px) {
        .dash-two-col-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Compact Inline Filter */
    .compact-inline-filter {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .compact-select {
        padding: 0.4rem 0.65rem;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 0.82rem;
        font-weight: 600;
        background: #ffffff;
        color: #1e293b;
        outline: none;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .compact-select:hover,
    .compact-select:focus {
        border-color: #0d9488;
    }

    .btn-section-link {
        font-size: 0.8rem;
        font-weight: 600;
        color: #0d9488;
        background: rgba(13, 148, 136, 0.08);
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .btn-section-link:hover {
        background: #0d9488;
        color: #ffffff;
    }

    /* Chart Container Card (Col-8) */
    .chart-container-card {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .chart-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.85rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .chart-legend-custom {
        display: flex;
        gap: 0.85rem;
        flex-wrap: wrap;
    }

    .legend-badge {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.78rem;
        font-weight: 600;
        color: #334155;
    }

    .legend-circle {
        width: 9px;
        height: 9px;
        border-radius: 50%;
    }

    .legend-circle.teal { background: #0d9488; }
    .legend-circle.amber { background: #f59e0b; }
    .legend-circle.ocean { background: #0284c7; }
    .legend-circle.emerald { background: #10b981; }

    .chart-tag {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        background: #f0fdfa;
        color: #0f766e;
        border: 1px solid #ccfbf1;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
    }

    .chart-canvas-wrapper {
        position: relative;
        width: 100%;
        height: 310px;
    }

    /* Footer Stats inside chart card */
    .chart-footer-stats {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid #f1f5f9;
    }

    .footer-stat-pill {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 0.3rem 0.65rem;
        border-radius: 8px;
        font-size: 0.78rem;
        color: #334155;
    }

    .footer-stat-pill.pill-teal {
        background: #f0fdfa;
        border-color: #ccfbf1;
        color: #0f766e;
    }

    .footer-stat-pill.pill-ocean {
        background: #f0f9ff;
        border-color: #e0f2fe;
        color: #0369a1;
    }

    .footer-stat-pill.pill-mint {
        background: #ecfdf5;
        border-color: #d1fae5;
        color: #047857;
    }

    .footer-stat-icon {
        font-size: 0.85rem;
    }

    /* Side KPI Stack (Col-4 for Section 1) */
    .side-kpi-stack {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        justify-content: space-between;
    }

    .kpi-card-compact {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 12px;
        padding: 0.9rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        flex: 1;
    }

    .kpi-card-compact:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px -2px rgba(15, 23, 42, 0.08);
    }

    .kpi-icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .kpi-icon-box.amber { background: #fef3c7; color: #b45309; }
    .kpi-icon-box.teal { background: #ccfbf1; color: #0f766e; }
    .kpi-icon-box.emerald { background: #d1fae5; color: #047857; }
    .kpi-icon-box.ocean { background: #e0f2fe; color: #0369a1; }

    .kpi-content {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .kpi-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .kpi-number-wrap {
        display: flex;
        align-items: baseline;
        gap: 0.25rem;
        margin: 0.1rem 0;
    }

    .kpi-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }

    .kpi-unit {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
    }

    .kpi-trend {
        font-size: 0.7rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.2rem;
    }

    .kpi-trend.positive { color: #059669; }
    .kpi-trend.negative { color: #dc2626; }
    .kpi-trend.neutral { color: #64748b; }

    /* Side Widget Panel (Col-4 for Section 2) */
    .side-widget-panel {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        justify-content: space-between;
    }

    /* Donut Ratio Card */
    .donut-container-card {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .donut-card-header {
        margin-bottom: 0.5rem;
    }

    .donut-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }

    .donut-subtitle {
        font-size: 0.72rem;
        color: #64748b;
    }

    .donut-canvas-wrapper {
        position: relative;
        width: 100%;
        height: 155px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .donut-center-metric {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        pointer-events: none;
    }

    .donut-center-val {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }

    .donut-center-lbl {
        font-size: 0.65rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        margin-top: 1px;
    }

    .donut-legend-footer {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-top: 0.65rem;
        padding-top: 0.65rem;
        border-top: 1px solid #f1f5f9;
    }

    .donut-legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .donut-dot {
        width: 9px;
        height: 9px;
        border-radius: 3px;
        flex-shrink: 0;
    }

    .donut-dot.ocean { background: #0284c7; }
    .donut-dot.teal { background: #0d9488; }

    .donut-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: #334155;
    }

    .donut-weight {
        font-size: 0.7rem;
        color: #64748b;
    }

    /* Pending Stock Card */
    .pending-stock-card {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .pending-stock-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .pending-stock-icon {
        font-size: 1.3rem;
    }

    .pending-stock-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #92400e;
    }

    .pending-stock-subtitle {
        font-size: 0.68rem;
        color: #b45309;
    }

    .pending-stock-value-wrap {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .pending-stock-val {
        font-size: 1.25rem;
        font-weight: 800;
        color: #b45309;
        line-height: 1;
    }

    .pending-stock-unit {
        font-size: 0.65rem;
        font-weight: 600;
        color: #92400e;
        margin-top: 2px;
    }

    /* Store Highlights Grid */
    .store-highlights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.85rem;
    }

    .store-tile {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 12px;
        padding: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
    }

    .store-tile:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 14px -2px rgba(15, 23, 42, 0.08);
    }

    .store-tile-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .store-tile-icon.warning { background: #fef3c7; color: #b45309; }
    .store-tile-icon.blue { background: #dbeafe; color: #1d4ed8; }
    .store-tile-icon.emerald { background: #d1fae5; color: #047857; }
    .store-tile-icon.purple { background: #ede9fe; color: #6d28d9; }
    .store-tile-icon.red { background: #fee2e2; color: #b91c1c; }

    .store-tile-info {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        overflow: hidden;
    }

    .store-tile-num {
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
    }

    .store-tile-lbl {
        font-size: 0.68rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .store-tile-arrow {
        color: #cbd5e1;
        font-size: 0.9rem;
        transition: transform 0.2s;
    }

    .store-tile:hover .store-tile-arrow {
        color: #4f46e5;
        transform: translateX(3px);
    }
</style>

<!-- CHART.JS INITIALIZATION SCRIPTS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------------
    // CHART 1: MONTHLY PRODUCTION (DUAL-AXIS COMBO CHART)
    // -------------------------------------------------------------
    const prodCtx = document.getElementById('monthlyProductionChart').getContext('2d');

    const tealGradient = prodCtx.createLinearGradient(0, 0, 0, 300);
    tealGradient.addColorStop(0, 'rgba(13, 148, 136, 0.95)');
    tealGradient.addColorStop(1, 'rgba(45, 212, 191, 0.45)');

    const monthLabels = {!! json_encode($monthLabels) !!};
    const prodMetersData = {!! json_encode($prodMetersData) !!};
    const prodWeightData = {!! json_encode($prodWeightData) !!};

    new Chart(prodCtx, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    type: 'line',
                    label: 'Net Weight (kg)',
                    data: prodWeightData,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.12)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#f59e0b',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'yWeight',
                    order: 1
                },
                {
                    type: 'bar',
                    label: 'Actual Meters (m)',
                    data: prodMetersData,
                    backgroundColor: tealGradient,
                    hoverBackgroundColor: '#0f766e',
                    borderRadius: 5,
                    borderSkipped: false,
                    barPercentage: 0.55,
                    yAxisID: 'yMeters',
                    order: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleFont: { family: 'Outfit', size: 12, weight: '700' },
                    bodyFont: { family: 'Outfit', size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let val = context.raw || 0;
                            return ' ' + label + ': ' + Number(val).toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Outfit', size: 11, weight: '600' },
                        color: '#64748b'
                    }
                },
                yMeters: {
                    type: 'linear',
                    position: 'left',
                    grid: {
                        color: 'rgba(226, 232, 240, 0.6)',
                        drawBorder: false
                    },
                    ticks: {
                        font: { family: 'Outfit', size: 10.5 },
                        color: '#0f766e',
                        callback: function(value) {
                            return value >= 1000 ? (value / 1000).toLocaleString() + 'k m' : value + ' m';
                        }
                    }
                },
                yWeight: {
                    type: 'linear',
                    position: 'right',
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Outfit', size: 10.5 },
                        color: '#d97706',
                        callback: function(value) {
                            return value >= 1000 ? (value / 1000).toLocaleString() + 'k kg' : value + ' kg';
                        }
                    }
                }
            }
        }
    });

    // -------------------------------------------------------------
    // CHART 2: MONTHLY TRANSFER & DISPATCH (STACKED BAR)
    // -------------------------------------------------------------
    const transferCtx = document.getElementById('monthlyTransferChart').getContext('2d');
    const dispatchNwData = {!! json_encode($dispatchNwData) !!};
    const transferNwData = {!! json_encode($transferNwData) !!};

    new Chart(transferCtx, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    label: 'Dispatches kg',
                    data: dispatchNwData,
                    backgroundColor: '#0284c7',
                    hoverBackgroundColor: '#0369a1',
                    borderRadius: 4,
                    barPercentage: 0.6
                },
                {
                    label: 'Internal Transfers kg',
                    data: transferNwData,
                    backgroundColor: '#0d9488',
                    hoverBackgroundColor: '#0f766e',
                    borderRadius: 4,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleFont: { family: 'Outfit', size: 12, weight: '700' },
                    bodyFont: { family: 'Outfit', size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.dataset.label + ': ' + Number(context.raw).toLocaleString() + ' kg';
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Outfit', size: 11, weight: '600' },
                        color: '#64748b'
                    }
                },
                y: {
                    stacked: true,
                    grid: {
                        color: 'rgba(226, 232, 240, 0.6)',
                        drawBorder: false
                    },
                    ticks: {
                        font: { family: 'Outfit', size: 10.5 },
                        color: '#64748b',
                        callback: function(value) {
                            return value >= 1000 ? (value / 1000).toLocaleString() + 'k kg' : value + ' kg';
                        }
                    }
                }
            }
        }
    });

    // -------------------------------------------------------------
    // CHART 3: DISPATCH VS TRANSFER RATIO (DONUT)
    // -------------------------------------------------------------
    const donutCtx = document.getElementById('transferRatioDonut').getContext('2d');
    const totalDispatchNw = {{ (float)$totalDispatchNw }};
    const totalTransferNw = {{ (float)$totalTransferNw }};

    new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Dispatches', 'Internal Transfers'],
            datasets: [{
                data: [
                    totalDispatchNw > 0 || totalTransferNw > 0 ? totalDispatchNw : 1,
                    totalDispatchNw > 0 || totalTransferNw > 0 ? totalTransferNw : 1
                ],
                backgroundColor: ['#0284c7', '#0d9488'],
                hoverBackgroundColor: ['#0369a1', '#0f766e'],
                borderWidth: 2.5,
                borderColor: '#ffffff',
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleFont: { family: 'Outfit', size: 11 },
                    bodyFont: { family: 'Outfit', size: 11 },
                    padding: 8,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.label + ': ' + Number(context.raw).toLocaleString() + ' kg';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
