@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="content-header" style="margin-bottom: 1.5rem;">
    <div class="content-title">
        <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary);">Production Dashboard</h1>
        <p style="font-size: 0.9rem; color: var(--text-secondary);">Overview and management of production master registers</p>
    </div>
</div>

<!-- Metrics Cards Section (Matching Mockup) -->
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
@endsection
