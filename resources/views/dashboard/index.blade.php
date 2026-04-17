@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan data inventory Anda')

@section('content')
<div class="animate-fade-in">
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stats-card primary">
                <div class="stats-icon">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <div class="stats-value">{{ number_format($totalItems) }}</div>
                <div class="stats-label">Total Barang</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stats-card success">
                <div class="stats-icon">
                    <i class="bi bi-arrow-down-circle-fill"></i>
                </div>
                <div class="stats-value">{{ number_format($masukBulanIni) }}</div>
                <div class="stats-label">Barang Masuk (Bulan Ini)</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stats-card warning">
                <div class="stats-icon">
                    <i class="bi bi-arrow-up-circle-fill"></i>
                </div>
                <div class="stats-value">{{ number_format($keluarBulanIni) }}</div>
                <div class="stats-label">Barang Keluar (Bulan Ini)</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stats-card danger">
                <div class="stats-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stats-value">{{ number_format($pendingCount) }}</div>
                <div class="stats-label">Menunggu Approval</div>
            </div>
        </div>
    </div>

    {{-- ADMIN: Pending Transactions Approval (Per Hari) --}}
    @if(auth()->user()->isAdmin() && $pendingByDate->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <span><i class="bi bi-check2-square text-warning-custom me-2"></i>Transaksi Menunggu Approval</span>
            <span class="badge bg-warning text-dark">{{ $pendingCount }} pending</span>
        </div>
        <div class="card-body p-0">
            @foreach($pendingByDate as $date => $transactions)
            <div class="approval-date-group">
                <div class="d-flex align-items-center justify-content-between px-3 py-3" style="background:var(--body-bg); border-bottom:1px solid var(--border-color);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3 text-primary-custom"></i>
                        <span class="fw-700" style="font-size:14px;">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
                        <span class="badge bg-secondary" style="font-size:10px;">{{ $transactions->count() }} transaksi</span>
                    </div>
                    <div class="d-flex gap-2">
                        <form action="{{ route('dashboard.approveByDate') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve semua transaksi tanggal ini?')">
                                <i class="bi bi-check-all"></i> Approve Semua
                            </button>
                        </form>
                        <form action="{{ route('dashboard.rejectByDate') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject semua transaksi tanggal ini?')">
                                <i class="bi bi-x-lg"></i> Reject Semua
                            </button>
                        </form>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Satuan</th>
                                <th>Harga Satuan</th>
                                <th>User</th>
                                <th>Keterangan</th>
                                <th class="text-end" style="white-space:nowrap;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $tx)
                            <tr>
                                <td class="fw-600">{{ $tx->item->name ?? '-' }}</td>
                                <td>{{ $tx->item->category ?? '-' }}</td>
                                <td>
                                    <span class="badge-status badge-{{ $tx->type }}">
                                        <i class="bi bi-arrow-{{ $tx->type === 'masuk' ? 'down' : 'up' }}-circle-fill" style="font-size:10px;"></i>
                                        {{ strtoupper($tx->type) }}
                                    </span>
                                </td>
                                <td class="fw-700">{{ number_format($tx->quantity) }}</td>
                                <td>{{ $tx->item->unit ?? '-' }}</td>
                                <td>{{ $tx->price ?? '-' }}</td>
                                <td>{{ $tx->user->name ?? '-' }}</td>
                                <td style="font-size:12px; color:var(--text-secondary);">{{ Str::limit($tx->description, 40) }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                                        <a href="{{ route('transactions.edit', $tx) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit transaksi">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('transactions.destroy', $tx) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus transaksi pending ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus transaksi">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-bar-chart-fill text-primary-custom me-2"></i>Barang Masuk vs Keluar (12 Bulan)</span>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height:320px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-pie-chart-fill text-primary-custom me-2"></i>Stok per Kategori</span>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height:320px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="row g-3">
        <!-- Low Stock Alert -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-exclamation-triangle-fill text-warning-custom me-2"></i>Stok Menipis</span>
                    @if($lowStockItems->count() > 0)
                        <span class="badge bg-danger">{{ $lowStockItems->count() }}</span>
                    @endif
                </div>
                <div class="card-body" style="max-height:350px; overflow-y:auto;">
                    @forelse($lowStockItems as $item)
                        <div class="low-stock-item">
                            <div class="item-info">
                                <h6>{{ $item->name }}</h6>
                                <span>{{ $item->category }} · Min: {{ $item->min_stock }} {{ $item->unit }}</span>
                            </div>
                            <div class="stock-value">{{ $item->current_stock }} {{ $item->unit }}</div>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:30px 10px;">
                            <i class="bi bi-check-circle" style="font-size:40px; color:var(--success);"></i>
                            <h6 class="mt-2" style="font-size:13px;">Semua stok aman</h6>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Top Items Keluar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-fire text-danger-custom me-2"></i>Barang Paling Sering Keluar</span>
                </div>
                <div class="card-body">
                    @forelse($topKeluar as $index => $tx)
                        <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fw-700" style="width:24px;height:24px;border-radius:50%;background:var(--primary-bg);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:11px;">{{ $index+1 }}</span>
                                <div>
                                    <div class="fw-600" style="font-size:13px;">{{ $tx->item->name ?? '-' }}</div>
                                    <div style="font-size:11px;color:var(--text-secondary);">{{ $tx->item->category ?? '' }}</div>
                                </div>
                            </div>
                            <span class="fw-700 text-danger-custom">{{ number_format($tx->total) }}</span>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:30px 10px;">
                            <i class="bi bi-inbox" style="font-size:40px;"></i>
                            <h6 class="mt-2" style="font-size:13px;">Belum ada data</h6>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Transactions + Sync Button -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-clock-fill text-primary-custom me-2"></i>Transaksi Terbaru</span>
                    @if(auth()->user()->isAdmin())
                    <form action="{{ route('sync.sheets') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Sync semua data ke Google Sheets?')" title="Sync ke Google Sheets">
                            <i class="bi bi-cloud-arrow-up-fill"></i> Sync
                        </button>
                    </form>
                    @endif
                </div>
                <div class="card-body" style="max-height:350px; overflow-y:auto;">
                    @forelse($recentTransactions as $tx)
                        <div class="d-flex align-items-start gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;{{ $tx->type === 'masuk' ? 'background:var(--success-bg);color:var(--success);' : 'background:var(--danger-bg);color:var(--danger);' }}">
                                <i class="bi bi-arrow-{{ $tx->type === 'masuk' ? 'down' : 'up' }}-circle-fill"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-600" style="font-size:13px;">{{ $tx->item->name ?? '-' }}</div>
                                <div style="font-size:11px;color:var(--text-secondary);">
                                    {{ $tx->quantity }} {{ $tx->item->unit ?? '' }} · {{ $tx->user->name ?? '' }}
                                </div>
                            </div>
                            <span class="badge-status badge-{{ $tx->status }}" style="font-size:10px;">{{ ucfirst($tx->status) }}</span>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:30px 10px;">
                            <i class="bi bi-inbox" style="font-size:40px;"></i>
                            <h6 class="mt-2" style="font-size:13px;">Belum ada transaksi</h6>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Detect current theme
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';
    const tickColor = isDark ? '#94a3b8' : '#6c757d';
    const legendColor = isDark ? '#e2e8f0' : '#1a1a2e';

    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_column($monthlyData, 'label')) !!},
            datasets: [
                {
                    label: 'Barang Masuk',
                    data: {!! json_encode(array_column($monthlyData, 'masuk')) !!},
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Barang Keluar',
                    data: {!! json_encode(array_column($monthlyData, 'keluar')) !!},
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 20,
                        color: legendColor,
                        font: { family: 'Inter', size: 12, weight: 500 }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: tickColor, font: { family: 'Inter', size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: tickColor, font: { family: 'Inter', size: 11 } }
                }
            }
        }
    });

    // Category Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    const catColors = ['#10b981', '#06b6d4', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1'];
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_column($categoryData, 'category')) !!},
            datasets: [{
                data: {!! json_encode(array_column($categoryData, 'stock')) !!},
                backgroundColor: catColors.slice(0, {{ count($categoryData) }}),
                borderWidth: 0,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        color: legendColor,
                        font: { family: 'Inter', size: 11, weight: 500 }
                    }
                }
            }
        }
    });
</script>
@endpush
