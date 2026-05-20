@extends('layouts.app')

@section('title', 'Transaksi')
@section('subtitle', 'Daftar transaksi barang masuk & keluar')

@section('content')
    @php
        $isTeknik = auth()->user()->bidang === 'teknik';
    @endphp
    <div class="animate-fade-in">
        <div class="filter-bar">
            <form method="GET" action="{{ route('transactions.index') }}">
                <div class="row align-items-end g-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Cari Barang</label>
                        <input type="text" name="search" class="form-control" placeholder="Nama barang..." value="{{ request('search') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Jenis</label>
                        <select name="type" class="form-select">
                            <option value="">Semua</option>
                            <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>{{ $isTeknik ? 'Goods Receipt' : 'In' }}</option>
                            <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>{{ $isTeknik ? 'Goods Issue' : 'Out' }}</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu Approval</option>
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
                            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Cari</button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary flex-fill"><i class="bi bi-x-lg"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @include('transactions.partials.table', ['transactions' => $transactions])
    </div>

    <div class="modal fade inventrack-modal" id="transactionDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-eye-fill me-2"></i>Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3" id="transactionDetailGrid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade inventrack-modal" id="transactionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="position:relative;">
                <div class="modal-loading-overlay" id="txLoading"><div class="modal-spinner"></div></div>
                <div class="modal-header">
                    <h5 class="modal-title" id="txModalTitle"><i class="bi bi-plus-circle-fill"></i> <span>Input Transaksi</span></h5>
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
                                    <option value="in">{{ $isTeknik ? 'Goods Receipt' : 'Barang Masuk' }}</option>
                                    <option value="out">{{ $isTeknik ? 'Goods Issue' : 'Barang Keluar' }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Barang <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select" required id="txItemSelect">
                                <option value="">-- Pilih Barang --</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}"
                                        data-category="{{ $item->category }}"
                                        data-unit="{{ $item->unit }}"
                                        data-stock="{{ $item->current_stock }}"
                                        data-no-normalisasi="{{ $item->no_normalisasi }}"
                                        data-lokasi="{{ $item->lokasi }}"
                                        data-volume="{{ $item->current_stock }}"
                                        data-ship-unloader="{{ $item->ship_unloader }}">
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ $isTeknik ? 'Komponen' : 'Kategori' }}</label>
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

                        @if($isTeknik)
                            <div class="row g-3 mb-3" id="txTechnicalInfo">
                                <div class="col-md-4">
                                    <label class="form-label">No Normalisasi</label>
                                    <input type="text" class="form-control" id="txNoNormalisasi" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" id="txLokasi" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Volume</label>
                                    <input type="text" class="form-control" id="txVolume" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Ship Unloader <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach([1, 2, 3, 4] as $ship)
                                            <label class="form-check form-check-inline mb-0">
                                                <input class="form-check-input tx-ship-checkbox" type="checkbox" name="ship_unloader[]" value="{{ $ship }}">
                                                <span class="form-check-label">Ship {{ $ship }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" required id="txQuantity">
                                <div id="txStockWarning" class="text-danger mt-1" style="font-size:12px;display:none;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Jumlah melebihi stok yang tersedia!
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Satuan</label>
                                <input type="number" name="price" class="form-control" min="0" step="1" placeholder="0" id="txPrice">
                            </div>
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
                                <span>
                                    @if($isTeknik)
                                        Transaksi Teknik akan berstatus <strong>Auto Approve</strong> dan langsung masuk riwayat approved.
                                    @else
                                        Transaksi akan berstatus <strong>pending</strong> dan memerlukan approval dari Admin/Manager.
                                    @endif
                                </span>
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
        window.__transactionDetailData = @json($transactionDetailData);
        const txModalEl = document.getElementById('transactionModal');
        const txModal = new bootstrap.Modal(txModalEl);
        const txDetailModalEl = document.getElementById('transactionDetailModal');
        const txDetailModal = new bootstrap.Modal(txDetailModalEl);
        const txTypeSelect = document.getElementById('txType');
        const txItemSelect = document.getElementById('txItemSelect');
        const txCategoryInput = document.getElementById('txItemCategory');
        const txUnitInput = document.getElementById('txItemUnit');
        const txStockInput = document.getElementById('txItemStock');
        const txQuantityInput = document.getElementById('txQuantity');
        const txPriceInput = document.getElementById('txPrice');
        const txStockWarning = document.getElementById('txStockWarning');
        const txNoNormalisasi = document.getElementById('txNoNormalisasi');
        const txLokasi = document.getElementById('txLokasi');
        const txVolume = document.getElementById('txVolume');
        const isTeknikTransaction = @json($isTeknik);

        function appendTransactionDetail(container, label, value) {
            const wrap = document.createElement('div');
            wrap.className = 'col-sm-6';
            const muted = document.createElement('div');
            muted.className = 'text-muted';
            muted.style.fontSize = '12px';
            muted.textContent = label;
            const strong = document.createElement('div');
            strong.className = 'fw-700';
            strong.textContent = value || '-';
            wrap.appendChild(muted);
            wrap.appendChild(strong);
            container.appendChild(wrap);
        }

        function bindTransactionDetailButtons() {
            document.querySelectorAll('.btn-transaction-detail-open').forEach(function(button) {
                button.addEventListener('click', function() {
                const data = window.__transactionDetailData[this.dataset.transactionId];
                if (!data) return;
                const grid = document.getElementById('transactionDetailGrid');
                grid.replaceChildren();
                appendTransactionDetail(grid, 'Tanggal', data.date);
                appendTransactionDetail(grid, 'Jenis', data.type);
                if (isTeknikTransaction) {
                    appendTransactionDetail(grid, 'No Normalisasi', data.no_normalisasi);
                    appendTransactionDetail(grid, 'Nama Barang', data.name);
                    appendTransactionDetail(grid, 'Komponen', data.category);
                    appendTransactionDetail(grid, 'Ship Unloader', data.ship_unloader);
                    appendTransactionDetail(grid, 'Lokasi', data.lokasi);
                    appendTransactionDetail(grid, 'Volume', data.volume);
                    appendTransactionDetail(grid, 'Harga Satuan', data.price);
                } else {
                    appendTransactionDetail(grid, 'Barang', data.name);
                    appendTransactionDetail(grid, 'Kategori', data.category);
                    appendTransactionDetail(grid, 'Jumlah', data.quantity);
                    appendTransactionDetail(grid, 'Keterangan', data.description);
                }
                appendTransactionDetail(grid, 'Satuan', data.unit);
                appendTransactionDetail(grid, 'User', data.user);
                appendTransactionDetail(grid, 'Status', data.status);
                txDetailModal.show();
            });
            });
        }

        function bindTransactionDateSort() {
            document.querySelectorAll('.js-date-sort').forEach(function(button) {
                button.addEventListener('click', function() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('sort', this.dataset.sort || 'latest');
                    url.searchParams.delete('page');
                    loadTransactionsTable(url);
                });
            });
        }

        function loadTransactionsTable(url) {
            const region = document.getElementById('transactionsTableRegion');
            if (!region) return;

            region.classList.add('table-ajax-loading');

            fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    region.outerHTML = data.html;
                    window.__transactionDetailData = data.detailData || {};
                    window.history.pushState({}, '', url.toString());
                    bindTransactionDetailButtons();
                    bindTransactionDateSort();
                })
                .catch(() => {
                    Toast.fire({ icon: 'error', title: 'Gagal mengurutkan data transaksi.' });
                    region.classList.remove('table-ajax-loading');
                });
        }

        bindTransactionDetailButtons();
        bindTransactionDateSort();

        function refreshItemInfo() {
            const selected = txItemSelect.options[txItemSelect.selectedIndex];
            if (txItemSelect.value) {
                txCategoryInput.value = selected.dataset.category || '';
                txUnitInput.value = selected.dataset.unit || '';
                txStockInput.value = selected.dataset.stock || '0';
                if (isTeknikTransaction) {
                    txNoNormalisasi.value = selected.dataset.noNormalisasi || '';
                    txLokasi.value = selected.dataset.lokasi || '';
                    txVolume.value = txQuantityInput.value || '0';
                    const ships = (selected.dataset.shipUnloader || '').split(',').filter(Boolean);
                    document.querySelectorAll('.tx-ship-checkbox').forEach(el => el.checked = ships.includes(el.value));
                }
            } else {
                txCategoryInput.value = '';
                txUnitInput.value = '';
                txStockInput.value = '';
                if (isTeknikTransaction) {
                    txNoNormalisasi.value = '';
                    txLokasi.value = '';
                    txVolume.value = '';
                    document.querySelectorAll('.tx-ship-checkbox').forEach(el => el.checked = false);
                }
            }
            checkTxStock();
        }

        function togglePriceInput() {
            const disabled = txTypeSelect.value === 'out';
            if (disabled) txPriceInput.value = '';
            txPriceInput.disabled = disabled;
            checkTxStock();
        }

        function checkTxStock() {
            const stock = parseInt(txStockInput.value || '0') || 0;
            const qty = parseInt(txQuantityInput.value || '0') || 0;
            if (isTeknikTransaction) {
                txVolume.value = qty ? qty : '0';
            }
            txStockWarning.style.display = txTypeSelect.value === 'out' && txItemSelect.value && qty > stock ? 'block' : 'none';
        }

        txItemSelect.addEventListener('change', refreshItemInfo);
        txQuantityInput.addEventListener('input', checkTxStock);
        txTypeSelect.addEventListener('change', togglePriceInput);

        window.openTransactionModal = function (id = null) {
            document.getElementById('txForm').reset();
            document.getElementById('txError').style.display = 'none';
            txCategoryInput.value = '';
            txUnitInput.value = '';
            txStockInput.value = '';
            txStockWarning.style.display = 'none';
            if (isTeknikTransaction) {
                txNoNormalisasi.value = '';
                txLokasi.value = '';
                txVolume.value = '';
                document.querySelectorAll('.tx-ship-checkbox').forEach(el => el.checked = false);
            }

            if (id) {
                document.getElementById('txModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> <span>Edit Transaksi</span>';
                document.getElementById('txId').value = id;
                document.getElementById('txMethod').value = 'PUT';
                document.getElementById('txSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update';
                document.getElementById('txPendingInfo').style.display = 'none';
                document.getElementById('txUserRow').style.display = 'none';

                const loading = document.getElementById('txLoading');
                loading.classList.add('show');
                txModal.show();

                fetch(`{{ url('transactions') }}/${id}/edit-data`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(res => res.json())
                    .then(data => {
                        loading.classList.remove('show');
                        document.getElementById('txDate').value = data.date;
                        document.getElementById('txType').value = data.type;
                        txItemSelect.value = data.item_id;
                        txQuantityInput.value = data.quantity;
                        txPriceInput.value = data.price || '';
                        document.getElementById('txDescription').value = data.description || '';
                        if (isTeknikTransaction) {
                            txNoNormalisasi.value = data.no_normalisasi || data.item.no_normalisasi || '';
                            txLokasi.value = data.lokasi || data.item.lokasi || '';
                            txVolume.value = data.quantity || '';
                            document.querySelectorAll('.tx-ship-checkbox').forEach(el => el.checked = (data.ship_unloader || []).includes(el.value));
                        }
                        refreshItemInfo();
                        togglePriceInput();
                    })
                    .catch(() => {
                        loading.classList.remove('show');
                        document.getElementById('txErrorMsg').textContent = 'Gagal memuat data transaksi.';
                        document.getElementById('txError').style.display = 'block';
                    });
            } else {
                document.getElementById('txModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> <span>Input Transaksi</span>';
                document.getElementById('txId').value = '';
                document.getElementById('txMethod').value = 'POST';
                document.getElementById('txSubmitBtn').innerHTML = '<i class="bi bi-send-fill"></i> Kirim Transaksi';
                document.getElementById('txPendingInfo').style.display = 'block';
                document.getElementById('txUserRow').style.display = 'block';
                document.getElementById('txDate').value = new Date().toISOString().split('T')[0];
                togglePriceInput();
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
            loading.classList.add('show');
            submitBtn.disabled = true;

            const formData = new FormData(form);
            const url = txId ? `{{ url('transactions') }}/${txId}` : '{{ route("transactions.store") }}';
            if (method === 'PUT') formData.append('_method', 'PUT');

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
                .then(({ data }) => {
                    loading.classList.remove('show');
                    submitBtn.disabled = false;

                    if (data.success) {
                        txModal.hide();
                        Toast.fire({ icon: 'success', title: data.message });
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        let messages = [];
                        if (data.errors) Object.keys(data.errors).forEach(key => messages.push(data.errors[key][0]));
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
    </script>
@endpush
