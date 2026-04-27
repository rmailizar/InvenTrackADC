@extends('layouts.app')

@section('title', 'Laporan')
@section('subtitle', 'Laporan transaksi inventory')

@section('content')
    <div class="animate-fade-in">
        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="{{ route('reports.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-select">
                            <option value="">Semua</option>
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
                            <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>In</option>
                            <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Out</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
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
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-search"></i> Filter
                            </button>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="d-grid">
                            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Reset
                            </a>
                        </div>
                    </div>

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
                            <div class="stats-label">Total Masuk</div>
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
                            <div class="stats-label">Total Keluar</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stats-card primary">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon"><i class="bi bi-calculator-fill"></i></div>
                        <div>
                            <div class="stats-value" style="font-size:22px;">{{ number_format($totalMasuk - $totalKeluar) }}
                            </div>
                            <div class="stats-label">Selisih</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('reports.export', request()->all()) }}" class="text-decoration-none">
                    <div class="stats-card warning" style="cursor:pointer;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stats-icon">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                            </div>
                            <div>
                                <div class="stats-value" style="font-size:22px; color:var(--warning-dark);">
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
                <span><i class="bi bi-table me-2"></i>Data Transaksi (Approved)</span>
                <span class="text-muted" style="font-size:12px;">{{ $transactions->total() }} data</span>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table">
                        <thead>
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
                        </thead>
                        <tbody>
                            @forelse($transactions as $index => $tx)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $index }}</td>
                                    <td>{{ $tx->date->format('d/m/Y') }}</td>
                                    <td class="fw-600">{{ $tx->item->name ?? '-' }}</td>
                                    <td>{{ $tx->item->category ?? '-' }}</td>
                                    <td>
                                        <span class="badge-status badge-{{ $tx->type }}">
                                            {{ strtoupper($tx->type) }}
                                        </span>
                                    </td>
                                    <td class="fw-700">{{ number_format($tx->quantity) }}</td>
                                    <td>{{ $tx->price ?? '-' }}</td>
                                    <td>{{ $tx->item->unit ?? '-' }}</td>
                                    <td>{{ $tx->user->name ?? '-' }}</td>
                                    <td style="max-width:200px; font-size:12px; color:var(--text-secondary);">
                                        {{ \Illuminate\Support\Str::limit($tx->description, 50) }}
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="9">
                                        <i class="bi bi-inbox"
                                            style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                        Tidak ada data untuk filter ini
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($transactions->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $transactions->links('pagination.custom') }}
            </div>
        @endif
    </div>
@endsection