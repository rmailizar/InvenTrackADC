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
            <button type="button" class="btn btn-primary" onclick="openTransactionModal()">
                <i class="bi bi-plus-lg"></i> Input Transaksi
            </button>
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
                                @if(auth()->user()->isAdmin())
                                <th style="width:110px;">Aksi</th>
                                @endif
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
                                    @if(auth()->user()->isAdmin())
                                    <td>
                                        @if($tx->status === 'pending')
                                        <div class="action-buttons">
                                            <button type="button" class="btn-action edit" title="Edit" onclick="openTransactionModal({{ $tx->id }})">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form action="{{ route('transactions.destroy', $tx) }}" method="POST" id="deleteTxIdx-{{ $tx->id }}">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn-action delete" title="Hapus"
                                                    onclick="swalConfirm('Hapus Transaksi', 'Hapus transaksi pending ini?', 'warning', 'Ya, Hapus', '#deleteTxIdx-{{ $tx->id }}')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                        @else
                                        <span class="text-muted" style="font-size:11px;">—</span>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="{{ auth()->user()->isAdmin() ? 11 : 10 }}">
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

    {{-- Transaction Modal (Create/Edit) --}}
    <div class="modal fade inventrack-modal" id="transactionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="position:relative;">
                <div class="modal-loading-overlay" id="txLoading">
                    <div class="modal-spinner"></div>
                </div>
                <div class="modal-header">
                    <h5 class="modal-title" id="txModalTitle">
                        <i class="bi bi-plus-circle-fill"></i> <span>Input Transaksi</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-error-alert" id="txError">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        <span id="txErrorMsg"></span>
                    </div>
                    <form id="txForm" novalidate>
                        <input type="hidden" id="txId" value="">
                        <input type="hidden" id="txMethod" value="POST">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required id="txDate">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required id="txType">
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="masuk">📥 Barang Masuk</option>
                                    <option value="keluar">📤 Barang Keluar</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select" required id="txItemSelect">
                                <option value="">-- Pilih Barang --</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}"
                                            data-category="{{ $item->category }}"
                                            data-unit="{{ $item->unit }}"
                                            data-stock="{{ $item->current_stock }}">
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <input type="text" class="form-control" id="txItemCategory" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Satuan</label>
                                <input type="text" class="form-control" id="txItemUnit" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stok Saat Ini</label>
                                <input type="text" class="form-control" id="txItemStock" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" placeholder="Masukkan jumlah" required id="txQuantity">
                            <div id="txStockWarning" class="text-danger mt-1" style="font-size:12px;display:none;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Jumlah melebihi stok yang tersedia!
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Harga Satuan</label>
                            <input type="number" name="price" class="form-control" min="0" step="1" placeholder="Kosongkan Jika Input Barang Keluar" id="txPrice">
                        </div>

                        <div class="mb-3" id="txUserRow">
                            <label class="form-label">User</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Keterangan tambahan (opsional)" id="txDescription"></textarea>
                        </div>

                        <div class="p-3 rounded-3" id="txPendingInfo" style="background:var(--warning-bg); border: 1px solid rgba(255,159,28,0.2);">
                            <div class="d-flex align-items-center gap-2" style="font-size:13px; color: var(--warning-dark);">
                                <i class="bi bi-info-circle-fill"></i>
                                <span>Transaksi akan berstatus <strong>pending</strong> dan memerlukan approval dari Admin/Manager.</span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="txSubmitBtn" onclick="submitTransactionForm()">
                        <i class="bi bi-send-fill"></i> Kirim Transaksi
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const txModalEl = document.getElementById('transactionModal');
    const txModal = new bootstrap.Modal(txModalEl);

    // Item select → auto-fill info
    const txItemSelect = document.getElementById('txItemSelect');
    const txCategoryInput = document.getElementById('txItemCategory');
    const txUnitInput = document.getElementById('txItemUnit');
    const txStockInput = document.getElementById('txItemStock');
    const txQuantityInput = document.getElementById('txQuantity');
    const txTypeSelect = document.getElementById('txType');
    const txStockWarning = document.getElementById('txStockWarning');

    txItemSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (this.value) {
            txCategoryInput.value = selected.dataset.category || '';
            txUnitInput.value = selected.dataset.unit || '';
            txStockInput.value = selected.dataset.stock || '0';
        } else {
            txCategoryInput.value = '';
            txUnitInput.value = '';
            txStockInput.value = '';
        }
        checkTxStock();
    });

    txQuantityInput.addEventListener('input', checkTxStock);
    txTypeSelect.addEventListener('change', checkTxStock);

    function checkTxStock() {
        if (txTypeSelect.value === 'keluar' && txItemSelect.value) {
            const stock = parseInt(txStockInput.value) || 0;
            const qty = parseInt(txQuantityInput.value) || 0;
            txStockWarning.style.display = qty > stock ? 'block' : 'none';
        } else {
            txStockWarning.style.display = 'none';
        }
    }

    function openTransactionModal(id = null) {
        // Reset form
        document.getElementById('txForm').reset();
        document.getElementById('txError').style.display = 'none';
        document.querySelectorAll('#txForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        txCategoryInput.value = '';
        txUnitInput.value = '';
        txStockInput.value = '';
        txStockWarning.style.display = 'none';

        if (id) {
            // Edit mode
            document.getElementById('txModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> <span>Edit Transaksi</span>';
            document.getElementById('txId').value = id;
            document.getElementById('txMethod').value = 'PUT';
            document.getElementById('txSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update';
            document.getElementById('txPendingInfo').style.display = 'none';
            document.getElementById('txUserRow').style.display = 'none';

            const loading = document.getElementById('txLoading');
            loading.classList.add('show');
            txModal.show();

            fetch(`/transactions/${id}/edit-data`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                loading.classList.remove('show');
                document.getElementById('txDate').value = data.date;
                document.getElementById('txType').value = data.type;
                document.getElementById('txItemSelect').value = data.item_id;
                document.getElementById('txQuantity').value = data.quantity;
                document.getElementById('txPrice').value = data.price || '';
                document.getElementById('txDescription').value = data.description || '';

                // Fill item info
                txCategoryInput.value = data.item?.category || '';
                txUnitInput.value = data.item?.unit || '';
                txStockInput.value = data.item?.current_stock || '0';
            })
            .catch(() => {
                loading.classList.remove('show');
                document.getElementById('txErrorMsg').textContent = 'Gagal memuat data transaksi.';
                document.getElementById('txError').style.display = 'block';
            });
        } else {
            // Create mode
            document.getElementById('txModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> <span>Input Transaksi</span>';
            document.getElementById('txId').value = '';
            document.getElementById('txMethod').value = 'POST';
            document.getElementById('txSubmitBtn').innerHTML = '<i class="bi bi-send-fill"></i> Kirim Transaksi';
            document.getElementById('txPendingInfo').style.display = 'block';
            document.getElementById('txUserRow').style.display = 'block';
            document.getElementById('txDate').value = new Date().toISOString().split('T')[0];
            txModal.show();
        }
    }

    function submitTransactionForm() {
        const form = document.getElementById('txForm');
        const errorDiv = document.getElementById('txError');
        const errorMsg = document.getElementById('txErrorMsg');
        const loading = document.getElementById('txLoading');
        const submitBtn = document.getElementById('txSubmitBtn');
        const txId = document.getElementById('txId').value;
        const method = document.getElementById('txMethod').value;

        errorDiv.style.display = 'none';
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        loading.classList.add('show');
        submitBtn.disabled = true;

        const formData = new FormData(form);
        const url = txId ? `/transactions/${txId}` : '{{ route("transactions.store") }}';

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
                txModal.hide();
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

    // Reset on close
    txModalEl.addEventListener('hidden.bs.modal', function() {
        document.getElementById('txForm').reset();
        document.getElementById('txError').style.display = 'none';
        document.querySelectorAll('#txForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        txCategoryInput.value = '';
        txUnitInput.value = '';
        txStockInput.value = '';
        txStockWarning.style.display = 'none';
    });
</script>
@endpush