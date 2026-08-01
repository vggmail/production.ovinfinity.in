@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>Dashboard</h1>
        <p>Overview of master records in Production system</p>
    </div>
</div>

<!-- Stats Grid -->
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius);
        padding: 2rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        text-decoration: none;
        color: inherit;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent-primary);
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.15);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(to bottom, var(--accent-primary), var(--accent-secondary));
    }
    
    .stat-icon {
        font-size: 2.2rem;
        opacity: 0.85;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
    }
    
    .stat-label {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--text-primary);
    }
    
    .stat-desc {
        color: var(--text-secondary);
        font-size: 0.85rem;
    }
</style>

<div class="stats-grid">
    <a href="{{ route('masters.party.index') }}" class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-label">Party Master</div>
        <div class="stat-value">{{ $partiesCount }}</div>
        <div class="stat-desc">Manage clients and parties</div>
    </a>

    <a href="{{ route('masters.supplier.index') }}" class="stat-card">
        <div class="stat-icon">🚚</div>
        <div class="stat-label">Supplier Master</div>
        <div class="stat-value">{{ $suppliersCount }}</div>
        <div class="stat-desc">Manage materials suppliers</div>
    </a>

    <a href="{{ route('masters.rollsize.index') }}" class="stat-card">
        <div class="stat-icon">📏</div>
        <div class="stat-label">Roll Size Master</div>
        <div class="stat-value">{{ $rollSizesCount }}</div>
        <div class="stat-desc">Manage fabric roll dimensions</div>
    </a>

    <a href="{{ route('masters.loomnumber.index') }}" class="stat-card">
        <div class="stat-icon">⚙️</div>
        <div class="stat-label">Loom Number Master</div>
        <div class="stat-value">{{ $loomsCount }}</div>
        <div class="stat-desc">Manage loom configurations</div>
    </a>

    <a href="{{ route('masters.fabriccolor.index') }}" class="stat-card">
        <div class="stat-icon">🎨</div>
        <div class="stat-label">Fabric Color Master</div>
        <div class="stat-value">{{ $colorsCount }}</div>
        <div class="stat-desc">Manage color palettes</div>
    </a>
</div>
@endsection
