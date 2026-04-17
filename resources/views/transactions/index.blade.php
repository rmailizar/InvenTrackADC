@extends('layouts.app')

@section('title', 'Transaksi')
@section('subtitle', 'Daftar transaksi barang masuk & keluar')

@section('content')
    <div class="animate-fade-in">
        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="{{ route('transactions.index') }}">
                <div class="row align-items-end g-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Cari Barang</label>
                        <input type="text" name="search" class="form-control" placeholder="Nama barang..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Jenis</label>
                        <select name="type" class="form-select">
                            <option value="">Semua</option>
                            <option value="masuk" {{ request('type') == 'masuk' ? 'selected' : '' }}>Masuk</option>
                            <option value="keluar" {{ request('type') == 'keluar' ? 'selected' : '' }}>Keluar</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i>
                                Cari</button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary flex-fill"><i
                                    class="bi bi-x-lg"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Action buttons -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted" style="font-size:13px;">
                Total: {{ $transactions->total() }} transaksi
            </div>
            <a href="{{ route('transactions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Input
                Transaksi</a>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table" id="transactions-table">
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
                                <th>Status</th>
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
                                            <i class="bi bi-arrow-{{ $tx->type === 'masuk' ? 'down' : 'up' }}-circle-fill"
                                                style="font-size:10px;"></i>
                                            {{ strtoupper($tx->type) }}
                                        </span>
                                    </td>
                                    <td class="fw-700">{{ number_format($tx->quantity) }}</td>
                                    <td>{{ $tx->price }}</td>
                                    <td>{{ $tx->item->unit ?? '-' }}</td>
                                    <td>{{ $tx->user->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge-status badge-{{ $tx->status }}">{{ ucfirst($tx->status) }}</span>
                                        @if($tx->status !== 'pending' && $tx->approver)
                                            <div style="font-size:10px; color:var(--text-muted); margin-top:2px;">
                                                oleh {{ $tx->approver->name }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="9">
                                        <i class="bi bi-inbox"
                                            style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                        Belum ada data transaksi
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