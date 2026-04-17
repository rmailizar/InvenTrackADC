@extends('layouts.app')

@section('title', 'Edit Transaksi')
@section('subtitle', 'Ubah transaksi pending')

@section('content')
<div class="animate-fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-pencil-fill text-primary-custom me-2"></i>Edit Transaksi Pending</span>
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('transactions.update', $transaction) }}">
                        @csrf @method('PUT')

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $transaction->date->format('Y-m-d')) }}" required>
                                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required id="transaction-type">
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="masuk" {{ old('type', $transaction->type) == 'masuk' ? 'selected' : '' }}>📥 Barang Masuk</option>
                                    <option value="keluar" {{ old('type', $transaction->type) == 'keluar' ? 'selected' : '' }}>📤 Barang Keluar</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select @error('item_id') is-invalid @enderror" required id="item-select">
                                <option value="">-- Pilih Barang --</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}"
                                            data-category="{{ $item->category }}"
                                            data-unit="{{ $item->unit }}"
                                            data-stock="{{ $item->current_stock }}"
                                            {{ old('item_id', $transaction->item_id) == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <input type="text" class="form-control" id="item-category" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Satuan</label>
                                <input type="text" class="form-control" id="item-unit" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stok Saat Ini</label>
                                <input type="text" class="form-control" id="item-stock" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $transaction->quantity) }}" min="1" required id="quantity-input">
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="stock-warning" class="text-danger mt-1" style="font-size:12px;display:none;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Jumlah melebihi stok yang tersedia!
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Harga Satuan</label>
                            <input type="number" name="price" class="form-control" min="0" step="1" placeholder="Kosongkan Jika Input Barang Keluar" value="{{ old('price', $transaction->price) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <input type="text" class="form-control" value="{{ $transaction->user->name ?? '-' }}" readonly>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Keterangan</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Keterangan tambahan (opsional)">{{ old('description', $transaction->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const itemSelect = document.getElementById('item-select');
    const categoryInput = document.getElementById('item-category');
    const unitInput = document.getElementById('item-unit');
    const stockInput = document.getElementById('item-stock');
    const quantityInput = document.getElementById('quantity-input');
    const typeSelect = document.getElementById('transaction-type');
    const stockWarning = document.getElementById('stock-warning');

    itemSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (this.value) {
            categoryInput.value = selected.dataset.category || '';
            unitInput.value = selected.dataset.unit || '';
            stockInput.value = selected.dataset.stock || '0';
        } else {
            categoryInput.value = '';
            unitInput.value = '';
            stockInput.value = '';
        }
        checkStock();
    });

    quantityInput.addEventListener('input', checkStock);
    typeSelect.addEventListener('change', checkStock);

    function checkStock() {
        if (typeSelect.value === 'keluar' && itemSelect.value) {
            const stock = parseInt(stockInput.value) || 0;
            const qty = parseInt(quantityInput.value) || 0;
            if (qty > stock) {
                stockWarning.style.display = 'block';
            } else {
                stockWarning.style.display = 'none';
            }
        } else {
            stockWarning.style.display = 'none';
        }
    }

    if (itemSelect.value) {
        itemSelect.dispatchEvent(new Event('change'));
    }
</script>
@endpush
