@extends('layouts.app')

@section('title', 'Tambah Barang')
@section('subtitle', 'Tambahkan barang baru ke inventory')

@section('content')
<div class="animate-fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-plus-circle-fill text-primary-custom me-2"></i>Form Tambah Barang</span>
                    <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('items.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Masukkan nama barang" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <input type="text" name="category" list="category-list" class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}" placeholder="Pilih atau ketik kategori" required>
                                <datalist id="category-list">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                <input type="text" name="unit" list="unit-list" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit') }}" placeholder="Pilih atau ketik satuan" required>
                                <datalist id="unit-list">
                                    @foreach($units as $unit)
                                        <option value="{{ $unit }}">
                                    @endforeach
                                </datalist>
                                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('items.lookups.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-sliders2"></i> Kelola kategori & satuan
                            </a>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Minimum Stok <span class="text-danger">*</span></label>
                            <input type="number" name="min_stock" class="form-control @error('min_stock') is-invalid @enderror" value="{{ old('min_stock', 0) }}" min="0" required>
                            <small class="text-muted">Sistem akan memberikan peringatan jika stok di bawah angka ini</small>
                            @error('min_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
