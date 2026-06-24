@extends('layouts.app')

@php
    $isTeknik = auth()->user()->bidang === 'teknik';
    $activeTransactionType = request('type') === 'out' ? 'out' : 'in';
    $pageTitle = $isTeknik
        ? ($activeTransactionType === 'out' ? 'Goods Issue' : 'Goods Receipt')
        : 'Transaksi';
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
@endphp

@section('title', $pageTitle)
@section('subtitle', $isTeknik ? 'Input dan riwayat ' . $pageTitle : 'Daftar transaksi barang masuk & keluar')

@section('content')
    <div class="animate-fade-in {{ $isTeknik ? 'technical-transaction-page' : '' }}">
        <!-- Header Actions Wrapper -->
        <div class="header-action-wrapper d-none">
            <div class="section-header-actions">
                <form method="GET" action="{{ route('transactions.index') }}">
                    @if($isTeknik)
                        <input type="hidden" name="type" value="{{ $activeTransactionType }}">
                    @endif
                    <div class="action-row-1">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari barang..." value="{{ request('search') }}" style="width: 180px;">
                        @unless($isTeknik)
                            <select name="type" class="form-select form-select-sm" style="width: 100px;" onchange="this.form.submit()">
                                <option value="">Semua Jenis</option>
                                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>In</option>
                                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Out</option>
                            </select>
                        @endunless
                        <select name="year" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                            <option value="">Semua Tahun</option>
                            @foreach($years as $yr)
                                <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                        <select name="month" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                            <option value="">Semua Bulan</option>
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="action-row-2">
                        <a href="{{ $isTeknik ? route('transactions.index', ['type' => $activeTransactionType]) : route('transactions.index') }}" class="btn btn-reset btn-sm" title="Reset Filter">
                            <i class="bi bi-arrow-repeat"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @if($isTeknik)
            <div class="row g-3 align-items-start">
                <div class="col-xl-4 col-lg-5">
                    <div class="card position-sticky" style="top: 16px;">
                        <div class="card-header">
                            <span>
                                <i class="bi {{ $activeTransactionType === 'out' ? 'bi-box-arrow-up' : 'bi-box-arrow-in-down' }} text-primary-custom me-2"></i>
                                Input {{ $pageTitle }}
                            </span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('transactions.store') }}" id="txInlineForm">
                                @csrf
                                <input type="hidden" name="type" value="{{ $activeTransactionType }}">

                                <div class="mb-3">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required min="{{ date('Y-m-d') }}">
                                </div>

                                <div class="mb-3">
                                <label class="form-label">No Normalisasi</label>
                                <input type="text" class="form-control" id="txInlineNoNormalisasi" readonly placeholder="000-000-000">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Spare Part <span class="text-danger">*</span></label>
                                    <select name="item_id" class="form-select" required id="txInlineItemSelect">
                                        <option value="">-- Pilih Spare Part --</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}"
                                                data-category="{{ $item->category }}"
                                                data-unit="{{ $item->unit }}"
                                                data-stock="{{ $item->current_stock }}"
                                                data-no-normalisasi="{{ $item->no_normalisasi }}"
                                                data-lokasi="{{ $item->lokasi }}"
                                                data-component="{{ $item->component }}"
                                                data-volume="{{ $item->volume }}"
                                                data-ship-unloader="{{ $item->stock_ship_unloader }}">
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Komponen</label>
                                        <input type="text" class="form-control" id="txInlineCategory" readonly placeholder="Auto Fill">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Tipe Barang</label>
                                        <input type="text" class="form-control" id="txInlineItemCategory" readonly placeholder="Auto Fill">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Stok</label>
                                        <input type="text" class="form-control" id="txInlineStock" readonly placeholder="Auto Fill">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Volume</label>
                                        <input type="text" class="form-control" id="txInlineVolume" readonly placeholder="Auto Fill">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Lokasi</label>
                                        <input type="text" class="form-control" id="txInlineLokasi" readonly placeholder="Auto Fill">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Ship Unloader <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-nowrap align-items-center gap-1 ship-input-group" id="itemShipBadges">

                                        @foreach([1, 2, 3, 4] as $ship)
                                            <label class="ship-checkbox-label">
                                                <input class="ship-checkbox-input tx-ship-checkbox" type="checkbox" name="ship_unloader[]" value="{{ $ship }}" data-ship="{{ $ship }}">
                                                <span class="ship-checkbox-box">{{ $ship }}</span>
                                            </label>
                                        @endforeach

                                        <label class="ship-checkbox-label">
                                            <input class="ship-checkbox-input" type="checkbox" id="txShipAll" data-ship="all">
                                            <span class="ship-checkbox-box px-2" style="width: auto; min-width: 24px;">ALL</span>
                                        </label>

                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control" min="1" required id="txInlineQuantity">
                                        <div id="txInlineStockWarning" class="text-danger mt-1" style="font-size:12px;display:none;">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Melebihi stok tersedia.
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Satuan</label>
                                        <input type="text" class="form-control" id="txInlineUnit" readonly placeholder="Auto Fill">
                                    </div>
                                </div>

                                <button type="submit"
                                    class="btn w-100
                                    @if($isTeknik && $activeTransactionType === 'in')
                                        btn-receipt-submit
                                    @elseif($isTeknik && $activeTransactionType === 'out')
                                        btn-issue-submit
                                    @else
                                        btn-primary
                                    @endif">
                                    <i class="bi bi-send-fill"></i>
                                    Simpan {{ $pageTitle }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-xl-8 col-lg-7">
                    @include('transactions.partials.table', ['transactions' => $transactions, 'showCreateButton' => false])
                </div>
            </div>
        @else
            @include('transactions.partials.table', ['transactions' => $transactions])
        @endif
    </div>

    <div class="modal fade inventrack-modal {{ $isTeknik ? 'technical-transaction-modal' : '' }}" id="transactionDetailModal" tabindex="-1" aria-hidden="true">
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

    <div class="modal fade inventrack-modal {{ $isTeknik ? 'technical-transaction-modal' : '' }}" id="transactionModal" tabindex="-1" aria-hidden="true">
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
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required id="txDate" min="{{ date('Y-m-d') }}>
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
                                        data-component="{{ $item->component }}"
                                        data-volume="{{ $item->volume }}"
                                        data-ship-unloader="{{ $item->stock_ship_unloader }}">
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
                                <label class="form-label">{{ $isTeknik ? 'Tipe Barang' : 'Kategori' }}</label>
                                <input type="text" class="form-control" id="txCategoryReadonly" readonly>
                            </div>
                        </div>

                        @unless($isTeknik)
                            <div class="mb-3">
                                <label class="form-label">Harga Satuan</label>
                                <input type="number" name="price" class="form-control" min="0" step="1" placeholder="0" id="txPrice">
                            </div>
                        @endunless

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
        var transactionDetailData = @json($transactionDetailData);
        var currentScript = document.currentScript;
        var transactionSectionRoot = currentScript ? (currentScript.closest('.content-section') || document) : document;
        var txModalEl = document.getElementById('transactionModal');
        var txModal = new bootstrap.Modal(txModalEl);
        var txDetailModalEl = document.getElementById('transactionDetailModal');
        var txDetailModal = new bootstrap.Modal(txDetailModalEl);
        var txTypeSelect = document.getElementById('txType');
        var txItemSelect = document.getElementById('txItemSelect');
        var txCategoryInput = document.getElementById('txItemCategory');
        var txUnitInput = document.getElementById('txItemUnit');
        var txStockInput = document.getElementById('txItemStock');
        var txQuantityInput = document.getElementById('txQuantity');
        var txCategoryReadonly = document.getElementById('txCategoryReadonly');
        var txPriceInput = document.getElementById('txPrice');
        var txStockWarning = document.getElementById('txStockWarning');
        var txNoNormalisasi = document.getElementById('txNoNormalisasi');
        var txLokasi = document.getElementById('txLokasi');
        var txVolume = document.getElementById('txVolume');
        var isTeknikTransaction = @json($isTeknik);
        var activeTransactionType = @json($activeTransactionType);

        function setBadgeState(badge, isActive) {
            if (!badge) return;
            if (isActive) {
                badge.classList.remove('bg-light', 'text-muted', 'border');
                badge.classList.add('badge-ship-active'); // Mengaktifkan efek glowing hijau Anda
            } else {
                badge.classList.remove('badge-ship-active');
                badge.classList.add('bg-light', 'text-muted', 'border');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('itemShipBadges');
            if (!container) return;

            const allBadge = container.querySelector('[data-ship="all"]');
            const numberBadges = container.querySelectorAll('.badge:not([data-ship="all"])');

            // Fungsi untuk memeriksa apakah semua badge angka sedang aktif
            function checkAllNumbersActive() {
                if (numberBadges.length === 0) return false;
                return Array.from(numberBadges).every(badge => badge.classList.contains('badge-ship-active'));
            }

            // Event Delegation saat area badge diklik oleh user
            container.addEventListener('click', function (e) {
                const clickedBadge = e.target.closest('.badge');
                if (!clickedBadge) return;

                const shipValue = clickedBadge.dataset.ship;

                if (shipValue === 'all') {
                    // Kasus A: Jika badge "ALL" yang diklik
                    const isCurrentlyActive = clickedBadge.classList.contains('badge-ship-active');
                    const newState = !isCurrentlyActive; // Toggle state

                    // Set status untuk ALL itu sendiri dan SEMUA badge angka
                    setBadgeState(allBadge, newState);
                    numberBadges.forEach(badge => setBadgeState(badge, newState));

                } else {
                    // Kasus B: Jika badge ANGKA (1, 2, 3, 4) yang diklik
                    const isCurrentlyActive = clickedBadge.classList.contains('badge-ship-active');
                    setBadgeState(clickedBadge, !isCurrentlyActive); // Toggle status angka yang diklik

                    // Sinkronisasi tombol ALL: Jika semua angka aktif, nyalakan ALL. Jika ada 1 saja yang mati, matikan ALL.
                    setBadgeState(allBadge, checkAllNumbersActive());
                }

                /* ==================================================================
                SINKRONISASI KE INPUT FORM (CHIPS FORM/MODAL JIKA ADA)
                Jika Anda menggunakan checkbox input di belakang layar, picu sinkronisasinya di sini
                ================================================================== */
                numberBadges.forEach(badge => {
                    const num = badge.dataset.ship;
                    // Cari input checkbox asli yang nilainya sama dengan data-ship badge
                    const correspondingInput = document.querySelector(`.tx-ship-checkbox[value="${num}"]`);
                    if (correspondingInput) {
                        correspondingInput.checked = badge.classList.contains('badge-ship-active');
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {

        const allCheckbox = document.getElementById('txShipAll');

        const shipCheckboxes = document.querySelectorAll(
            '.tx-ship-checkbox'
        );

        if (!allCheckbox) return;

        // Klik ALL
        allCheckbox.addEventListener('change', function () {

            shipCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });

        });

        // Klik salah satu SU
        shipCheckboxes.forEach(cb => {

            cb.addEventListener('change', function () {

                const allChecked = [...shipCheckboxes]
                    .every(x => x.checked);

                allCheckbox.checked = allChecked;

            });

        });

        });

        (function bindInlineTransactionForm() {
            var root = transactionSectionRoot;
            var txInlineItemSelect = root.querySelector('#txInlineItemSelect');
            var txInlineQuantity = root.querySelector('#txInlineQuantity');
            var txInlineStock = root.querySelector('#txInlineStock');
            var txInlineStockWarning = root.querySelector('#txInlineStockWarning');
            var inlineTransactionType = activeTransactionType;

            function getInlineField(id) {
                return root.querySelector('#' + id);
            }

            function checkInlineStock() {
                if (!txInlineQuantity || !txInlineStock || !txInlineStockWarning) return;
                var stock = parseInt(txInlineStock.value || '0') || 0;
                var qty = parseInt(txInlineQuantity.value || '0') || 0;
                txInlineStockWarning.style.display = inlineTransactionType === 'out' && txInlineItemSelect?.value && qty > stock ? 'block' : 'none';
            }

            function refreshInlineTransactionInfo() {
                if (!txInlineItemSelect) return;
                var selected = txInlineItemSelect.options[txInlineItemSelect.selectedIndex];
                var hasItem = Boolean(txInlineItemSelect.value);
                getInlineField('txInlineCategory').value = hasItem ? (selected.dataset.category || '') : '';
                if (getInlineField('txInlineItemCategory')) {
                    getInlineField('txInlineCategory').value = hasItem ? (selected.dataset.component || '') : '';
                    getInlineField('txInlineItemCategory').value = hasItem ? (selected.dataset.category || '') : '';
                }
                getInlineField('txInlineUnit').value = hasItem ? (selected.dataset.unit || '') : '';
                getInlineField('txInlineStock').value = hasItem ? (selected.dataset.stock || '0') : '';
                if (getInlineField('txInlineVolume')) {
                    getInlineField('txInlineVolume').value = hasItem ? (selected.dataset.volume || '') : '';
                }
                getInlineField('txInlineNoNormalisasi').value = hasItem ? (selected.dataset.noNormalisasi || '') : '';
                getInlineField('txInlineLokasi').value = hasItem ? (selected.dataset.lokasi || '') : '';

                var ships = hasItem ? (selected.dataset.shipUnloader || '').split(',').filter(Boolean) : [];
                root.querySelectorAll('.ship-checkbox-input').forEach(el => {
                    el.checked = ships.includes(el.value.toString());
                });
                checkInlineStock();
            }

            if (txInlineItemSelect) {
                txInlineItemSelect.addEventListener('change', refreshInlineTransactionInfo);
                refreshInlineTransactionInfo();
            }
            if (txInlineQuantity) {
                txInlineQuantity.addEventListener('input', checkInlineStock);
            }
        })();

        (function bindTransactionTableSection() {
            var root = transactionSectionRoot;
            var detailData = transactionDetailData;

            function appendTransactionDetail(container, label, value) {
                var wrap = document.createElement('div');
                wrap.className = 'col-sm-6';
                var muted = document.createElement('div');
                muted.className = 'text-muted';
                muted.style.fontSize = '12px';
                muted.textContent = label;
                var strong = document.createElement('div');
                strong.className = 'fw-700';
                strong.textContent = value || '-';
                wrap.appendChild(muted);
                wrap.appendChild(strong);
                container.appendChild(wrap);
            }

            function bindTransactionDetailButtons() {
                root.querySelectorAll('.btn-transaction-detail-open').forEach(function(button) {
                    button.addEventListener('click', function() {
                        var data = detailData[this.dataset.transactionId];
                        if (!data) return;
                        var grid = document.getElementById('transactionDetailGrid');
                        grid.replaceChildren();
                        appendTransactionDetail(grid, 'Tanggal', data.date);
                        appendTransactionDetail(grid, 'Jenis', data.type);
                        if (isTeknikTransaction) {
                            appendTransactionDetail(grid, 'No Normalisasi', data.no_normalisasi);
                            appendTransactionDetail(grid, 'Nama Barang', data.name);
                            appendTransactionDetail(grid, 'Komponen', data.component);
                            appendTransactionDetail(grid, 'Tipe Barang', data.category);
                            appendTransactionDetail(grid, 'Ship Unloader', data.ship_unloader);
                            appendTransactionDetail(grid, 'Lokasi', data.lokasi);
                            appendTransactionDetail(grid, 'Volume', data.volume);
                            appendTransactionDetail(grid, 'Jumlah', data.quantity);
                        } else {
                            appendTransactionDetail(grid, 'Barang', data.name);
                            appendTransactionDetail(grid, 'Kategori', data.category);
                            appendTransactionDetail(grid, 'Jumlah', data.quantity);
                            appendTransactionDetail(grid, 'Harga Satuan', data.price);
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
                root.querySelectorAll('.js-date-sort').forEach(function(button) {
                    button.addEventListener('click', function() {
                        var url = new URL(window.location.href);
                        url.searchParams.set('sort', this.dataset.sort || 'latest');
                        url.searchParams.delete('page');
                        loadTransactionsTable(url);
                    });
                });
            }

            function loadTransactionsTable(url) {
                var region = root.querySelector('#transactionsTableRegion');
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
                        detailData = data.detailData || {};
                        window.__transactionDetailData = detailData;
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
        })();

        function refreshItemInfo() {
            var selected = txItemSelect.options[txItemSelect.selectedIndex];
            if (txItemSelect.value) {
                txCategoryInput.value = isTeknikTransaction ? (selected.dataset.component || '') : (selected.dataset.category || '');
                if (txCategoryReadonly) txCategoryReadonly.value = selected.dataset.category || '';
                txUnitInput.value = selected.dataset.unit || '';
                txStockInput.value = selected.dataset.stock || '0';
                
                if (isTeknikTransaction) {
                    txNoNormalisasi.value = selected.dataset.noNormalisasi || '';
                    txLokasi.value = selected.dataset.lokasi || '';
                    txVolume.value = selected.dataset.volume || '';
                    
                    // Ambil data array ship unloader dari item terpilih di database
                    var ships = (selected.dataset.shipUnloader || '').split(',').filter(Boolean);
                    
                    // Ambil elemen container & seluruh elemen badge untuk kustomisasi visual
                    const container = document.getElementById('itemShipBadges');
                    if (container) {
                        const allBadge = container.querySelector('[data-ship="all"]');
                        const numberBadges = container.querySelectorAll('.badge:not([data-ship="all"])');
                        
                        // 1. Nyalakan/Matikan badge angka berdasarkan kecocokan database
                        numberBadges.forEach(badge => {
                            const isActive = ships.includes(badge.dataset.ship.toString());
                            setBadgeState(badge, isActive);
                        });

                        // 2. Cek apakah semua badge angka menyala. Jika ya, ikut nyalakan kotak "ALL" otomatis
                        const allNumbersAreActive = numberBadges.length > 0 && Array.from(numberBadges).every(b => b.classList.contains('badge-ship-active'));
                        setBadgeState(allBadge, ships.length > 0 && allNumbersAreActive);
                    }

                    // Tetap pertahankan status keaslian form input checkbox bawaan backend Anda
                    document.querySelectorAll('.tx-ship-checkbox').forEach(el => {
                        el.checked = ships.includes(el.value.toString());
                    });
                }
            } else {
                txCategoryInput.value = '';
                if (txCategoryReadonly) txCategoryReadonly.value = '';
                txUnitInput.value = '';
                txStockInput.value = '';
                if (isTeknikTransaction) {
                    txNoNormalisasi.value = '';
                    txLokasi.value = '';
                    txVolume.value = '';
                    
                    // Reset semua badge visual ke kondisi kosong jika dropdown dikosongkan
                    const container = document.getElementById('itemShipBadges');
                    if (container) {
                        container.querySelectorAll('.badge').forEach(badge => setBadgeState(badge, false));
                    }
                    
                    document.querySelectorAll('.tx-ship-checkbox').forEach(el => el.checked = false);
                }
            }
            checkTxStock();
        }

        function togglePriceInput() {
            checkTxStock();
        }

        function checkTxStock() {
            var stock = parseInt(txStockInput.value || '0') || 0;
            var qty = parseInt(txQuantityInput.value || '0') || 0;
            if (isTeknikTransaction) {
                txVolume.value = txItemSelect.value ? (txItemSelect.options[txItemSelect.selectedIndex].dataset.volume || '') : '';
            }
            if (txPriceInput) {
                var disabled = txTypeSelect.value === 'out';
                if (disabled) txPriceInput.value = '';
                txPriceInput.disabled = disabled;
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
            if (txCategoryReadonly) txCategoryReadonly.value = '';
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

                var loading = document.getElementById('txLoading');
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
                        if (txPriceInput) txPriceInput.value = data.price || '';
                        document.getElementById('txDescription').value = data.description || '';
                        refreshItemInfo();
                        if (isTeknikTransaction) {
                            txNoNormalisasi.value = data.no_normalisasi || data.item.no_normalisasi || '';
                            txLokasi.value = data.lokasi || data.item.lokasi || '';
                            txVolume.value = data.volume || data.item.volume || '';
                            var dbShips = (data.ship_unloader || []);
                            document.querySelectorAll('.tx-ship-checkbox').forEach(el => {
                                el.checked = dbShips.map(String).includes(el.value.toString());
                            });
                        }
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
            var form = document.getElementById('txForm');
            var errorDiv = document.getElementById('txError');
            var errorMsg = document.getElementById('txErrorMsg');
            var loading = document.getElementById('txLoading');
            var submitBtn = document.getElementById('txSubmitBtn');
            var txId = document.getElementById('txId').value;
            var method = document.getElementById('txMethod').value;

            errorDiv.style.display = 'none';
            loading.classList.add('show');
            submitBtn.disabled = true;

            var formData = new FormData(form);
            var url = txId ? `{{ url('transactions') }}/${txId}` : '{{ route("transactions.store") }}';
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
                        var messages = [];
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
