@extends('layouts.app')

@section('title', 'Master Barang')
@section('subtitle', 'Kelola daftar barang inventory')

@section('content')
    <div class="animate-fade-in">
        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="{{ route('items.index') }}">
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label">Cari Barang</label>
                        <input type="text" name="search" class="form-control" placeholder="Nama atau kategori..."
                            value="{{ request('search') }}">
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
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i>
                            Reset</a>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="d-grid gap-2">
                            <a href="{{ route('items.create') }}" class="btn btn-primary w-100"><i
                                    class="bi bi-plus-lg"></i> Tambah</a>
                            <a href="{{ route('items.lookups.index') }}" class="btn btn-outline-primary w-100"><i
                                    class="bi bi-sliders2"></i> Kelola</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table" id="items-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Satuan</th>
                                <th>Min Stok</th>
                                <th>Stok Saat Ini</th>
                                <th>Status</th>
                                <th style="width:100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $item)
                                <tr>
                                    <td>{{ $items->firstItem() + $index }}</td>
                                    <td class="fw-600">{{ $item->name }}</td>
                                    <td>{{ $item->category }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td>{{ $item->min_stock }}</td>
                                    <td class="fw-700
                                                            @if($item->current_stock == 0)
                                                                text-danger-custom
                                                            @elseif($item->current_stock < $item->min_stock)
                                                                text-warning
                                                            @else
                                                                text-success-custom
                                                            @endif
                                                        ">
                                        {{ $item->current_stock }}
                                    </td>

                                    <td>
                                        @if($item->current_stock == 0)
                                            <span class="badge-stock-ok text-danger-custom">
                                                <i class="bi bi-x-circle-fill"></i> Habis
                                            </span>

                                        @elseif($item->current_stock < $item->min_stock)
                                            <span class="badge-low-stock text-warning">
                                                <i class="bi bi-exclamation-triangle-fill"></i> Request Order
                                            </span>

                                        @else
                                            <span class="badge-stock-ok">
                                                <i class="bi bi-check-circle-fill"></i> Aman
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="{{ route('items.edit', $item) }}" class="btn-action edit" title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <form action="{{ route('items.destroy', $item) }}" method="POST"
                                                id="deleteItem-{{ $item->id }}">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn-action delete" title="Hapus"
                                                    onclick="swalConfirm('Hapus Barang', 'Yakin hapus barang ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteItem-{{ $item->id }}')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="8">
                                        <i class="bi bi-inbox"
                                            style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                        Belum ada data barang
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($items->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $items->links('pagination.custom') }}
            </div>
        @endif
    </div>
@endsection