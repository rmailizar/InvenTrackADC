@extends('layouts.app')

@section('title', 'Laporan')
@section('subtitle', 'Laporan transaksi dan rekap stok inventory')

@section('content')
    @php
        $isStock = $activeTable === 'stock';
        $isTeknik = auth()->user()->bidang === 'teknik';
        $transactionTabParams = request()->only(['date_from', 'date_to', 'category', 'type', 'year', 'price_filter', 'sort']);
        $stockTabParams = request()->only(['search', 'category', 'stock_status']);
        $exportParams = $isStock
            ? request()->only(['search', 'category', 'stock_status'])
            : request()->except('page');
        $exportParams['table'] = $activeTable;
    @endphp

    <div class="animate-fade-in">
        <div class="report-tabs mb-3">
            <a href="{{ route('reports.index', array_merge($transactionTabParams, ['table' => 'transactions'])) }}"
                class="report-tab {{ !$isStock ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i>
                Data Transaksi (Approved)
            </a>
            <a href="{{ route('reports.index', array_merge($stockTabParams, ['table' => 'stock'])) }}"
                class="report-tab {{ $isStock ? 'active' : '' }}">
                <i class="bi bi-clipboard-data-fill"></i>
                Rekap Stok
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar report-filter-panel">
            <form method="GET" action="{{ route('reports.index') }}">
                <input type="hidden" name="table" value="{{ $activeTable }}">

                <div class="row g-3 align-items-end">
                    @if($isStock)
                        <div class="col-md-3">
                            <label class="form-label">Cari Barang</label>
                            <input type="text" name="search" class="form-control" placeholder="Nama atau kategori..."
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ $isTeknik ? 'Komponen' : 'Kategori' }}</label>
                            <select name="category" class="form-select">
                                <option value="">Semua {{ $isTeknik ? 'Komponen' : 'Kategori' }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status Stok</label>
                            <select name="stock_status" class="form-select">
                                <option value="">Semua</option>
                                <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Stok Rendah</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="bi bi-search"></i> Filter</button>
                            <a href="{{ route('reports.index', ['table' => 'stock']) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Reset
                            </a>
                        </div>
                    @else
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">{{ $isTeknik ? 'Komponen' : 'Kategori' }}</label>
                            <select name="category" class="form-select">
                                <option value="">Semua {{ $isTeknik ? 'Komponen' : 'Kategori' }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Jenis</label>
                            <select name="type" class="form-select">
                                <option value="">Semua</option>
                                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>{{ $isTeknik ? 'Goods Receipt (IN)' : 'In' }}</option>
                                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>{{ $isTeknik ? 'Goods Issue (OUT)' : 'Out' }}</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Tahun</label>
                            <select name="year" class="form-select">
                                <option value="">Semua</option>
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Harga</label>
                            <select name="price_filter" class="form-select">
                                <option value="">Semua</option>
                                <option value="tertinggi" {{ request('price_filter') == 'tertinggi' ? 'selected' : '' }}>
                                    Tertinggi
                                </option>
                                <option value="terendah" {{ request('price_filter') == 'terendah' ? 'selected' : '' }}>
                                    Terendah
                                </option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Urutkan</label>
                            <select name="sort" class="form-select">
                                <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>
                                    &#8595; Terbaru
                                </option>
                                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>
                                    &#8593; Terlama
                                </option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <div class="d-grid">
                                <a href="{{ route('reports.index', ['table' => 'transactions']) }}"
                                    class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i> Reset
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Summary Cards + Export Button -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="stats-card success">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
                        <div>
                            <div class="stats-value" style="font-size:22px;">{{ number_format($totalMasuk) }}</div>
                            <div class="stats-label">{{ $isTeknik ? 'Total Goods Receipt' : 'Total Masuk' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stats-card danger">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
                        <div>
                            <div class="stats-value" style="font-size:22px;">{{ number_format($totalKeluar) }}</div>
                            <div class="stats-label">{{ $isTeknik ? 'Total Goods Issue' : 'Total Keluar' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stats-card warning">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon"><i class="bi bi-calculator-fill"></i></div>
                        <div>
                            <div class="stats-value" style="font-size:22px;">{{ number_format($totalAkhir) }}</div>
                            <div class="stats-label">{{ $isStock ? 'Stok Akhir' : 'Selisih' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('reports.export', $exportParams) }}" class="text-decoration-none">
                    <div class="stats-card primary" style="cursor:pointer;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stats-icon">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                            </div>
                            <div>
                                <div class="stats-value" style="font-size:22px; color:var(--primary);">
                                    Export
                                </div>
                                <div class="stats-label">Download Excel</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header">
                <span>
                    <i class="bi {{ $isStock ? 'bi-clipboard-data-fill' : 'bi-table' }} me-2"></i>
                    {{ $isStock ? 'Rekap Stok' : 'Data Transaksi (Approved)' }}
                </span>
                <span class="text-muted" style="font-size:12px;">
                    {{ $isStock ? $stockItems->total() : $transactions->total() }} data
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    @if($isStock)
                        <table class="table">
                            <thead>
                                @if($isTeknik)
                                    <tr>
                                        <th style="width:50px;">No</th>
                                        <th>No Normalisasi</th>
                                        <th>Nama Barang</th>
                                        <th>Komponen</th>
                                        <th>Ship Unloader</th>
                                        <th>Lokasi</th>
                                        <th class="text-center">Volume</th>
                                        <th>Satuan</th>
                                        <th class="text-center">Total Goods Receipt</th>
                                        <th class="text-center">Total Goods Issue</th>
                                        <th class="text-center">Stok Saat Ini</th>
                                        <th class="text-center">Min Stok</th>
                                        <th>Status</th>
                                    </tr>
                                @else
                                    <tr>
                                        <th style="width:50px;">No</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Satuan</th>
                                        <th class="text-center">Total Masuk</th>
                                        <th class="text-center">Total Keluar</th>
                                        <th class="text-center">Stok Saat Ini</th>
                                        <th class="text-center">Min Stok</th>
                                        <th>Status</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody>
                                @forelse($stockItems as $index => $row)
                                    <tr>
                                        <td>{{ $stockItems->firstItem() + $index }}</td>
                                        @if($isTeknik)
                                            <td class="fw-600">{{ $row->item->no_normalisasi ?? '-' }}</td>
                                            <td class="fw-600">{{ $row->item->name }}</td>
                                            <td>{{ $row->item->category }}</td>
                                            <td>{{ $row->item->stock_ship_unloader_label }}</td>
                                            <td>{{ $row->item->lokasi ?? '-' }}</td>
                                            <td class="text-center fw-700">{{ number_format($row->stok_akhir) }}</td>
                                            <td>{{ $row->item->unit }}</td>
                                        @else
                                            <td class="fw-600">{{ $row->item->name }}</td>
                                            <td>{{ $row->item->category }}</td>
                                            <td>{{ $row->item->unit }}</td>
                                        @endif
                                        <td class="text-center fw-600 text-success-custom">{{ number_format($row->masuk) }}</td>
                                        <td class="text-center fw-600 text-danger-custom">{{ number_format($row->keluar) }}</td>
                                        <td class="text-center fw-700"
                                            style="{{ $row->stok_akhir <= $row->item->min_stock ? 'color:var(--danger);' : 'color:var(--success);' }}">
                                            {{ number_format($row->stok_akhir) }}
                                        </td>
                                        <td class="text-center">{{ number_format($row->item->min_stock) }}</td>
                                        <td>
                                            @if($row->stok_akhir <= 0)
                                                <span class="badge-status badge-rejected"><i class="bi bi-x-circle-fill"></i>
                                                    Out of Stock</span>
                                            @elseif($row->stok_akhir < $row->item->min_stock)
                                                <span class="badge-status badge-pending">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> Request Order
                                                </span>
                                            @else
                                                <span class="badge-status badge-approved"><i class="bi bi-check-circle-fill"></i> Ready</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="no-data-row">
                                        <td colspan="{{ $isTeknik ? 13 : 9 }}">
                                            <i class="bi bi-inbox"
                                                style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                            Tidak ada data stok untuk filter ini
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <table class="table">
                            <thead>
                                @if($isTeknik)
                                    <tr>
                                        <th style="width:50px;">No</th>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>No Normalisasi</th>
                                        <th>Nama Barang</th>
                                        <th>Komponen</th>
                                        <th>Ship Unloader</th>
                                        <th>Lokasi</th>
                                        <th class="text-center">Volume</th>
                                        <th class="text-end">Harga Satuan</th>
                                        <th>Satuan</th>
                                        <th>User</th>
                                        <th>Status</th>
                                    </tr>
                                @else
                                    <tr>
                                        <th style="width:50px;">No</th>
                                        <th>Tanggal</th>
                                        <th>Barang</th>
                                        <th>Kategori</th>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Satuan</th>
                                        <th>User</th>
                                        <th>Keterangan</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody>
                                @forelse($transactions as $index => $tx)
                                    <tr>
                                        <td>{{ $transactions->firstItem() + $index }}</td>
                                        <td>{{ $tx->date->format('d/m/Y') }}</td>
                                        @if($isTeknik)
                                            <td>
                                                <span class="badge-status badge-{{ $tx->type }}">
                                                    <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"
                                                        style="font-size:10px;"></i>
                                                    {{ $tx->type_label }}
                                                </span>
                                            </td>
                                            <td class="fw-600">{{ $tx->no_normalisasi ?? $tx->item->no_normalisasi ?? '-' }}</td>
                                            <td class="fw-600">{{ $tx->item->name ?? '-' }}</td>
                                            <td>{{ $tx->item->category ?? '-' }}</td>
                                            <td>{{ $tx->ship_unloader_label }}</td>
                                            <td>{{ $tx->lokasi ?? $tx->item->lokasi ?? '-' }}</td>
                                            <td class="text-center fw-700">{{ number_format($tx->quantity) }}</td>
                                            <td class="text-end">{{ $tx->price === null ? '-' : 'Rp ' . number_format($tx->price, 0, ',', '.') }}</td>
                                            <td>{{ $tx->item->unit ?? '-' }}</td>
                                            <td>{{ $tx->user->name ?? '-' }}</td>
                                            <td><span class="badge-status badge-approved">Approved</span></td>
                                        @else
                                            <td class="fw-600">{{ $tx->item->name ?? '-' }}</td>
                                            <td>{{ $tx->item->category ?? '-' }}</td>
                                            <td>
                                                <span class="badge-status badge-{{ $tx->type }}">
                                                    <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"
                                                        style="font-size:10px;"></i>
                                                    {{ $tx->type_label }}
                                                </span>
                                            </td>
                                            <td class="fw-700">{{ number_format($tx->quantity) }}</td>
                                            <td>{{ $tx->price ?? '-' }}</td>
                                            <td>{{ $tx->item->unit ?? '-' }}</td>
                                            <td>{{ $tx->user->name ?? '-' }}</td>
                                            <td style="max-width:200px; font-size:12px; color:var(--text-secondary);">
                                                {{ \Illuminate\Support\Str::limit($tx->description, 50) }}
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr class="no-data-row">
                                        <td colspan="{{ $isTeknik ? 13 : 10 }}">
                                            <i class="bi bi-inbox"
                                                style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                            Tidak ada data untuk filter ini
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        @if($isStock && $stockItems->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $stockItems->links('pagination.custom') }}
            </div>
        @elseif(!$isStock && $transactions->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $transactions->links('pagination.custom') }}
            </div>
        @endif
    </div>
@endsection
