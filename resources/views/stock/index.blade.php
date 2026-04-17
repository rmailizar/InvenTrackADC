@extends('layouts.app')

@section('title', 'Rekap Stok')
@section('subtitle', 'Ringkasan stok barang saat ini')

@section('content')
<div class="animate-fade-in">
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="{{ route('stock.index') }}">
            <div class="row align-items-end g-3">
                <div class="col-md-3">
                    <label class="form-label">Cari Barang</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama atau kategori..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('stock.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-clipboard-data-fill text-primary-custom me-2"></i>Rekap Stok Barang</span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="stock-table">
                    <thead>
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
                    </thead>
                    <tbody>
                        @php $no = isset($paginated) && $paginated && method_exists($items, 'firstItem') ? $items->firstItem() : 1; @endphp
                        @forelse($items as $index => $item)
                        @php
                            $totalMasuk = $item->transactions()->masuk()->approved()->sum('quantity');
                            $totalKeluar = $item->transactions()->keluar()->approved()->sum('quantity');
                            $currentStock = $totalMasuk - $totalKeluar;
                        @endphp
                        <tr>
                            <td>{{ $no + $index }}</td>
                            <td class="fw-600">{{ $item->name }}</td>
                            <td>{{ $item->category }}</td>
                            <td>{{ $item->unit }}</td>
                            <td class="text-center fw-600 text-success-custom">{{ number_format($totalMasuk) }}</td>
                            <td class="text-center fw-600 text-danger-custom">{{ number_format($totalKeluar) }}</td>
                            <td class="text-center fw-700" style="font-size:15px; {{ $currentStock <= $item->min_stock ? 'color:var(--danger);' : 'color:var(--success);' }}">
                                {{ number_format($currentStock) }}
                            </td>
                            <td class="text-center">{{ $item->min_stock }}</td>
                            <td>
                                @if($currentStock <= $item->min_stock)
                                    <span class="badge-low-stock"><i class="bi bi-exclamation-triangle-fill"></i> Rendah</span>
                                @else
                                    <span class="badge-stock-ok"><i class="bi bi-check-circle-fill"></i> Aman</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr class="no-data-row">
                            <td colspan="9">
                                <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                Tidak ada data stok
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(isset($paginated) && $paginated && method_exists($items, 'hasPages') && $items->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $items->links('pagination.custom') }}
    </div>
    @endif
</div>
@endsection
