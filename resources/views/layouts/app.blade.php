<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - InvenTrack</title>
    <meta name="description" content="InvenTrack - Sistem Manajemen Inventory Modern">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <!-- Logo  -->
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    <!-- Prevent flash: apply theme before render -->
    <script>
        (function () {
            const theme = localStorage.getItem('inventrack-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>

<body>
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-box-seam-fill"></i>
            </div>
            <div>
                <div class="brand-text">InvenTrack</div>
                <div class="brand-sub">Inventory System</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-label">Menu Utama</div>

            {{-- Dashboard: Admin & Manager only --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                <a href="{{ route('dashboard') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                    @if(auth()->user()->isAdmin())
                        @php $pendingTxCount = \App\Models\Transaction::pending()->count(); @endphp
                        @if($pendingTxCount > 0)
                            <span class="badge bg-warning text-dark">{{ $pendingTxCount }}</span>
                        @endif
                    @endif
                </a>
            @endif

            {{-- Master Barang: Admin only --}}
            @if(auth()->user()->isAdmin())
                <a href="{{ route('items.index') }}"
                    class="sidebar-link {{ request()->routeIs('items.*') ? 'active' : '' }}">
                    <i class="bi bi-box-fill"></i>
                    <span>Master Barang</span>
                </a>
            @endif

            {{-- Transaksi: Admin & Staff only --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
                <a href="{{ route('transactions.index') }}"
                    class="sidebar-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transaksi</span>
                </a>
            @endif

            {{-- Rekap Stok: Admin & Manager only --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                <a href="{{ route('stock.index') }}"
                    class="sidebar-link {{ request()->routeIs('stock.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data-fill"></i>
                    <span>Rekap Stok</span>
                </a>
            @endif

            {{-- Laporan: Admin & Manager --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                <div class="sidebar-label" style="margin-top: 8px;">Laporan</div>

                <a href="{{ route('reports.index') }}"
                    class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Laporan</span>
                </a>
            @endif

            {{-- Pengaturan: Admin only --}}
            @if(auth()->user()->isAdmin())
                <div class="sidebar-label" style="margin-top: 8px;">Pengaturan</div>

                <a href="{{ route('users.index') }}"
                    class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Manajemen User</span>
                </a>

                <a href="{{ route('import.index') }}"
                    class="sidebar-link {{ request()->routeIs('import.*') ? 'active' : '' }}">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                    <span>Import Data</span>
                </a>
            @endif

            {{-- Approval User: Manager only --}}
            @if(auth()->user()->isManager())
                <div class="sidebar-label" style="margin-top: 8px;">Pengaturan</div>

                <a href="{{ route('pendingUsers.index', ['account_status' => 'pending']) }}"
                    class="sidebar-link {{ request()->routeIs('pendingUsers.*') ? 'active' : '' }}">
                    <i class="bi bi-person-check-fill"></i>
                    <span>Approval User</span>
                    @php
                        $pendingUsersCount = 0;
                        try {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'account_status')) {
                                $pendingUsersCount = \App\Models\User::where('account_status', 'pending')->count();
                            }
                        } catch (\Throwable $e) {
                            $pendingUsersCount = 0;
                        }
                    @endphp
                    @if($pendingUsersCount > 0)
                        <span class="badge bg-warning text-dark">{{ $pendingUsersCount }}</span>
                    @endif
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">{{ ucfirst(auth()->user()->role) }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-icon"
                        style="background:rgba(255,255,255,0.08);border:none;color:rgba(255,255,255,0.5);width:34px;height:34px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                        title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="btn-sidebar-toggle" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="page-title">@yield('title', 'Dashboard')</div>
                    <div class="page-subtitle">@yield('subtitle', '')</div>
                </div>
            </div>
            <div class="topbar-right">
                @if(!auth()->user()->isStaff())
                    @php
                        $lowStockCount = \App\Models\Item::all()->filter(fn($i) => $i->is_low_stock)->count();
                    @endphp
                    @if($lowStockCount > 0)
                        <a href="{{ route('stock.index', ['stock_status' => 'low']) }}" class="btn-icon"
                            title="{{ $lowStockCount }} barang stok rendah">
                            <i class="bi bi-bell-fill"></i>
                            <span class="notification-dot"></span>
                        </a>
                    @endif
                @endif

                <!-- Dark/Light Mode Toggle -->
                <button class="btn-theme-toggle" onclick="toggleTheme()" title="Ganti tema" id="themeToggle">
                    <i class="bi bi-sun-fill icon-sun"></i>
                    <i class="bi bi-moon-fill icon-moon"></i>
                </button>

                <div class="d-none d-md-flex align-items-center gap-2 ms-2">
                    <span class="text-muted" style="font-size:12px;">{{ now()->translatedFormat('l, d M Y') }}</span>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">
            @yield('content')
        </div>
    </main>

    <!-- Toast Notifications -->
    @if(session('success'))
        <div class="alert-float alert-success" id="alertToast">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert-float alert-danger" id="alertToast">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <script>
        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('inventrack-theme', newTheme);
        }

        // Sidebar toggle (desktop: collapse, mobile: slide)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const overlay = document.getElementById('sidebarOverlay');
            const isMobile = window.innerWidth < 992;

            if (isMobile) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
                localStorage.setItem('inventrack-sidebar', sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
            }
        }

        // Restore sidebar state on load (desktop only)
        (function () {
            if (window.innerWidth >= 992) {
                const sidebarState = localStorage.getItem('inventrack-sidebar');
                if (sidebarState === 'collapsed') {
                    document.getElementById('sidebar').classList.add('collapsed');
                    document.querySelector('.main-content').classList.add('sidebar-collapsed');
                }
            }
        })();

        // Auto-dismiss toast
        const alertToast = document.getElementById('alertToast');
        if (alertToast) {
            setTimeout(() => {
                alertToast.style.animation = 'slideInRight 0.4s reverse forwards';
                setTimeout(() => alertToast.remove(), 400);
            }, 4000);
        }

        // Close sidebar on link click (mobile)
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    document.getElementById('sidebar').classList.remove('show');
                    document.getElementById('sidebarOverlay').classList.remove('show');
                }
            });
        });

        // Handle window resize: reset mobile state
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) {
                document.getElementById('sidebar').classList.remove('show');
                document.getElementById('sidebarOverlay').classList.remove('show');
            }
        });
    </script>

    @stack('scripts')
</body>

</html>