<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Production</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('styles')
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span>Production</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <span>📊</span> Dashboard
                    </a>
                </li>
                
                <li class="sidebar-item has-submenu {{ request()->routeIs('masters.*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu(event)" class="submenu-toggle">
                        <span>🗂️</span> Masters <span class="submenu-arrow">{{ request()->routeIs('masters.*') ? '▼' : '▶' }}</span>
                    </a>
                    <ul class="sidebar-submenu" style="display: {{ request()->routeIs('masters.*') ? 'flex' : 'none' }};">
                        <li class="sidebar-subitem {{ request()->routeIs('masters.party.*') ? 'active' : '' }}">
                            <a href="{{ route('masters.party.index') }}">
                                <span>👥</span> Party Master
                            </a>
                        </li>
                        <li class="sidebar-subitem {{ request()->routeIs('masters.supplier.*') ? 'active' : '' }}">
                            <a href="{{ route('masters.supplier.index') }}">
                                <span>🚚</span> Supplier Master
                            </a>
                        </li>
                        <li class="sidebar-subitem {{ request()->routeIs('masters.rollsize.*') ? 'active' : '' }}">
                            <a href="{{ route('masters.rollsize.index') }}">
                                <span>📏</span> Roll Size Master
                            </a>
                        </li>
                        <li class="sidebar-subitem {{ request()->routeIs('masters.loomnumber.*') ? 'active' : '' }}">
                            <a href="{{ route('masters.loomnumber.index') }}">
                                <span>⚙️</span> Loom Number Master
                            </a>
                        </li>
                        <li class="sidebar-subitem {{ request()->routeIs('masters.fabriccolor.*') ? 'active' : '' }}">
                            <a href="{{ route('masters.fabriccolor.index') }}">
                                <span>🎨</span> Fabric Color Master
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <div class="sidebar-user">
                @auth
                    <div class="user-info">
                        <span class="user-name">{{ auth()->user()->FullName }}</span>
                        <span class="user-role">Code: {{ auth()->user()->UserCode }}</span>
                    </div>
                @endauth
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <span>🚪</span> Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Alert Notifications -->
            @if (session('success'))
                <div class="alert alert-success">
                    <span>✅</span> {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">
                    <span>❌</span> {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        function toggleSubmenu(event) {
            event.preventDefault();
            const item = event.currentTarget.closest('.has-submenu');
            item.classList.toggle('open');
            const submenu = item.querySelector('.sidebar-submenu');
            const arrow = item.querySelector('.submenu-arrow');
            
            if (item.classList.contains('open')) {
                submenu.style.display = 'flex';
                arrow.textContent = '▼';
            } else {
                submenu.style.display = 'none';
                arrow.textContent = '▶';
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
