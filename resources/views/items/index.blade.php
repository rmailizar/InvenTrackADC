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
                            <button type="button" class="btn btn-primary w-100" onclick="openItemModal()"><i
                                    class="bi bi-plus-lg"></i> Tambah</button>
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
                                            <button type="button" class="btn-action edit" title="Edit" onclick="openItemModal({{ $item->id }})">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
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

    {{-- Item Modal (Create/Edit) --}}
    <div class="modal fade inventrack-modal" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="position:relative;">
                <div class="modal-loading-overlay" id="itemLoading">
                    <div class="modal-spinner"></div>
                </div>
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalTitle">
                        <i class="bi bi-plus-circle-fill"></i> <span>Tambah Barang</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-error-alert" id="itemError">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        <span id="itemErrorMsg"></span>
                    </div>
                    <form id="itemForm" novalidate>
                        <input type="hidden" id="itemId" value="">
                        <input type="hidden" id="itemMethod" value="POST">

                        <div class="mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Masukkan nama barang" required id="itemName">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <input type="text" name="category" list="category-list" class="form-control" placeholder="Pilih atau ketik kategori" required id="itemCategory">
                                <datalist id="category-list">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                <input type="text" name="unit" list="unit-list" class="form-control" placeholder="Pilih atau ketik satuan" required id="itemUnit">
                                <datalist id="unit-list">
                                    @foreach($units as $u)
                                        <option value="{{ $u }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Minimum Stok <span class="text-danger">*</span></label>
                            <input type="number" name="min_stock" class="form-control" min="0" required id="itemMinStock" value="0">
                            <small class="text-muted">Sistem akan memberikan peringatan jika stok di bawah angka ini</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="itemSubmitBtn" onclick="submitItemForm()">
                        <i class="bi bi-check-lg"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const itemModalEl = document.getElementById('itemModal');
    const itemModal = new bootstrap.Modal(itemModalEl);

    function openItemModal(id = null) {
        // Reset form
        document.getElementById('itemForm').reset();
        document.getElementById('itemError').style.display = 'none';
        document.querySelectorAll('#itemForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.getElementById('itemMinStock').value = '0';

        if (id) {
            // Edit mode — load data
            document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> <span>Edit Barang</span>';
            document.getElementById('itemId').value = id;
            document.getElementById('itemMethod').value = 'PUT';
            document.getElementById('itemSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update';

            const loading = document.getElementById('itemLoading');
            loading.classList.add('show');
            itemModal.show();

            fetch(`/items/${id}/edit-data`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                loading.classList.remove('show');
                document.getElementById('itemName').value = data.name;
                document.getElementById('itemCategory').value = data.category;
                document.getElementById('itemUnit').value = data.unit;
                document.getElementById('itemMinStock').value = data.min_stock;
            })
            .catch(() => {
                loading.classList.remove('show');
                document.getElementById('itemErrorMsg').textContent = 'Gagal memuat data barang.';
                document.getElementById('itemError').style.display = 'block';
            });
        } else {
            // Create mode
            document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> <span>Tambah Barang</span>';
            document.getElementById('itemId').value = '';
            document.getElementById('itemMethod').value = 'POST';
            document.getElementById('itemSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
            itemModal.show();
        }
    }

    function submitItemForm() {
        const form = document.getElementById('itemForm');
        const errorDiv = document.getElementById('itemError');
        const errorMsg = document.getElementById('itemErrorMsg');
        const loading = document.getElementById('itemLoading');
        const submitBtn = document.getElementById('itemSubmitBtn');
        const itemId = document.getElementById('itemId').value;
        const method = document.getElementById('itemMethod').value;

        // Clear errors
        errorDiv.style.display = 'none';
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        loading.classList.add('show');
        submitBtn.disabled = true;

        const formData = new FormData(form);
        const url = itemId ? `/items/${itemId}` : '{{ route("items.store") }}';

        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            loading.classList.remove('show');
            submitBtn.disabled = false;

            if (data.success) {
                itemModal.hide();
                Toast.fire({ icon: 'success', title: data.message });
                setTimeout(() => location.reload(), 1000);
            } else {
                let messages = [];
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        messages.push(data.errors[key][0]);
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) input.classList.add('is-invalid');
                    });
                }
                if (data.message && !data.errors) messages.push(data.message);
                errorMsg.innerHTML = messages.join('<br>');
                errorDiv.style.display = 'block';
            }
        })
        .catch(() => {
            loading.classList.remove('show');
            submitBtn.disabled = false;
            errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            errorDiv.style.display = 'block';
        });
    }

    // Reset modal on close
    itemModalEl.addEventListener('hidden.bs.modal', function() {
        document.getElementById('itemForm').reset();
        document.getElementById('itemError').style.display = 'none';
        document.querySelectorAll('#itemForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    });
</script>
@endpush