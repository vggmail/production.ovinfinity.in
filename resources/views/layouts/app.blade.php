<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Production</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @yield('styles')
    <script>
        // Apply collapsed state before render to prevent layout jump
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed-init');
        }
    </script>
    <style>
        html.sidebar-collapsed-init body {
            /* Handled dynamically by JS */
        }
    </style>
</head>
<body>
    <div class="app-container">
        @include('layouts.sidebar')

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button type="button" id="sidebar-toggle-btn" class="sidebar-toggle-btn" title="Toggle Sidebar">
                        ☰
                    </button>
                    <div class="top-bar-date">
                        <span>📅</span> {{ now()->format('l, d F Y') }}
                    </div>
                </div>
                @auth
                <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">
                    Welcome, <a href="{{ route('profile.edit') }}" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">{{ auth()->user()->FullName }}</a>
                </div>
                @endauth
            </div>

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
        document.addEventListener('DOMContentLoaded', () => {
            // Restore collapsed state
            if (localStorage.getItem('sidebar_collapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
            }

            const toggleBtn = document.getElementById('sidebar-toggle-btn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    document.body.classList.toggle('sidebar-collapsed');
                    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
                });
            }

            // Auto-hide success alerts after 5 seconds
            const successAlerts = document.querySelectorAll('.alert-success');
            successAlerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease, margin-bottom 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });

        function toggleSubmenu(event) {
            event.preventDefault();
            
            // If sidebar is collapsed, auto-expand on submenu click
            if (document.body.classList.contains('sidebar-collapsed')) {
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebar_collapsed', 'false');
            }

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
    <!-- jQuery & Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @yield('scripts')
</body>
</html>
