@extends('layouts.app')

@section('title', 'Input Transaksi')
@section('subtitle', 'Tambahkan transaksi barang masuk atau keluar')

@section('content')
<div class="animate-fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-plus-circle-fill text-primary-custom me-2"></i>Form Input Transaksi</span>
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('transactions.store') }}">
                        @csrf

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required id="transaction-type">
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="masuk" {{ old('type') == 'masuk' ? 'selected' : '' }}>📥 Barang Masuk</option>
                                    <option value="keluar" {{ old('type') == 'keluar' ? 'selected' : '' }}>📤 Barang Keluar</option>
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
                                            {{ old('item_id') == $item->id ? 'selected' : '' }}>
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
                            <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" min="1" placeholder="Masukkan jumlah" required id="quantity-input">
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="stock-warning" class="text-danger mt-1" style="font-size:12px;display:none;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Jumlah melebihi stok yang tersedia!
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Harga Satuan</label>
                            <input type="number" name="price" class="form-control" min="0" step="1" placeholder="Kosongkan Jika Input Barang Keluar">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->name }}">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Keterangan</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Keterangan tambahan (opsional)">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="p-3 rounded-3 mb-4" style="background:var(--warning-bg); border: 1px solid rgba(255,159,28,0.2);">
                            <div class="d-flex align-items-center gap-2" style="font-size:13px; color: var(--warning-dark);">
                                <i class="bi bi-info-circle-fill"></i>
                                <span>Transaksi akan berstatus <strong>pending</strong> dan memerlukan approval dari Admin/Manager.</span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill"></i> Kirim Transaksi</button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Batal</a>
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

    // Trigger on page load if old values exist
    if (itemSelect.value) {
        itemSelect.dispatchEvent(new Event('change'));
    }
</script>
@endpush
