<!-- Sidebar -->
<aside class="sidebar">
    <!-- Brand Wrapper matching mockups -->
    <div style="padding: 0.5rem 0.5rem 1.5rem 0.5rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid var(--card-border); margin-bottom: 1rem;">
        <div style="width: 38px; height: 38px; background: var(--accent-primary); color: #fff; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 700; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); flex-shrink: 0;">PRD</div>
        <div class="brand-details">
            <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary); line-height: 1.2;">Production</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; letter-spacing: 1px; text-transform: uppercase;">Admin Panel</div>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" title="Dashboard">
                <span>📊</span> <span class="menu-text">Dashboard</span>
            </a>
        </li>

        <li class="sidebar-item has-submenu {{ request()->routeIs('masters.*') ? 'open' : '' }}">
            <a href="#" onclick="toggleSubmenu(event)" class="submenu-toggle" title="Masters">
                <span style="display: flex; align-items: center; gap: 0.75rem;">
                    <span>🗂️</span> <span class="menu-text">Masters</span>
                </span>
                <span class="submenu-arrow">{{ request()->routeIs('masters.*') ? '▼' : '▶' }}</span>
            </a>
            <ul class="sidebar-submenu" style="display: {{ request()->routeIs('masters.*') ? 'flex' : 'none' }};">
                <li class="sidebar-subitem {{ request()->routeIs('masters.party.*') ? 'active' : '' }}">
                    <a href="{{ route('masters.party.index') }}" title="Party Master">
                        <span>👥</span> <span class="menu-text">Party Master</span>
                    </a>
                </li>
                <li class="sidebar-subitem {{ request()->routeIs('masters.supplier.*') ? 'active' : '' }}">
                    <a href="{{ route('masters.supplier.index') }}" title="Supplier Master">
                        <span>🚚</span> <span class="menu-text">Supplier Master</span>
                    </a>
                </li>
                <li class="sidebar-subitem {{ request()->routeIs('masters.rollsize.*') ? 'active' : '' }}">
                    <a href="{{ route('masters.rollsize.index') }}" title="Roll Size Master">
                        <span>📏</span> <span class="menu-text">Roll Size Master</span>
                    </a>
                </li>
                <li class="sidebar-subitem {{ request()->routeIs('masters.loomnumber.*') ? 'active' : '' }}">
                    <a href="{{ route('masters.loomnumber.index') }}" title="Loom Number Master">
                        <span>⚙️</span> <span class="menu-text">Loom Number Master</span>
                    </a>
                </li>
                <li class="sidebar-subitem {{ request()->routeIs('masters.fabriccolor.*') ? 'active' : '' }}">
                    <a href="{{ route('masters.fabriccolor.index') }}" title="Fabric Color Master">
                        <span>🎨</span> <span class="menu-text">Fabric Color Master</span>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="sidebar-item has-submenu {{ request()->routeIs('inventories.*') ? 'open' : '' }}">
            <a href="#" onclick="toggleSubmenu(event)" class="submenu-toggle" title="Inventories">
                <span style="display: flex; align-items: center; gap: 0.75rem;">
                    <span>📦</span> <span class="menu-text">Inventories</span>
                </span>
                <span class="submenu-arrow">{{ request()->routeIs('inventories.*') ? '▼' : '▶' }}</span>
            </a>
            <ul class="sidebar-submenu" style="display: {{ request()->routeIs('inventories.*') ? 'flex' : 'none' }};">
                <li class="sidebar-subitem {{ request()->routeIs('inventories.production.*') ? 'active' : '' }}">
                    <a href="{{ route('inventories.production.index') }}" title="Production">
                        <span>⚙️</span> <span class="menu-text">Production</span>
                    </a>
                </li>
                <li class="sidebar-subitem {{ request()->routeIs('inventories.purchase.*') ? 'active' : '' }}">
                    <a href="{{ route('inventories.purchase.index') }}" title="Purchase">
                        <span>🛍️</span> <span class="menu-text">Purchase</span>
                    </a>
                </li>
                <li class="sidebar-subitem {{ request()->routeIs('inventories.dispatch.*') ? 'active' : '' }}">
                    <a href="{{ route('inventories.dispatch.index') }}" title="Dispatch">
                        <span>🚚</span> <span class="menu-text">Dispatch</span>
                    </a>
                </li>
                <!-- 
                <li class="sidebar-subitem {{ request()->routeIs('inventories.transfer.*') ? 'active' : '' }}">
                    <a href="{{ route('inventories.transfer.index') }}" title="Transfer">
                        <span>🔄</span> <span class="menu-text">Transfer</span>
                    </a>
                </li>
                -->
            </ul>
        </li>

        <li class="sidebar-item has-submenu {{ request()->routeIs('reports.*') ? 'open' : '' }}">
            <a href="#" onclick="toggleSubmenu(event)" class="submenu-toggle" title="Reports">
                <span style="display: flex; align-items: center; gap: 0.75rem;">
                    <span>📈</span> <span class="menu-text">Reports</span>
                </span>
                <span class="submenu-arrow">{{ request()->routeIs('reports.*') ? '▼' : '▶' }}</span>
            </a>
            <ul class="sidebar-submenu" style="display: {{ request()->routeIs('reports.*') ? 'flex' : 'none' }};">
                <li class="sidebar-subitem {{ request()->routeIs('reports.summary.*') ? 'active' : '' }}">
                    <a href="{{ route('reports.summary.index') }}" title="Summary Report">
                        <span>📊</span> <span class="menu-text">Summary Report</span>
                    </a>
                </li>
            </ul>
        </li>

        <li class="sidebar-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
            <a href="{{ route('profile.edit') }}" title="Profile Settings">
                <span>👤</span> <span class="menu-text">Profile Settings</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" title="Sign Out">
                <span>🚪</span> <span class="menu-text">Sign Out</span>
            </a>
        </li>
    </ul>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</aside>
