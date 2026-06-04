@extends('layouts.app')

@section('title', 'Rekap Stok')
@section('subtitle', 'Ringkasan stok barang saat ini')

@section('content')
    @php
        $isTeknik = auth()->user()->bidang === 'teknik';
    @endphp
    <div class="animate-fade-in">
        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="{{ route('stock.index') }}">
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label">Cari Barang</label>
                        <input type="text" name="search" class="form-control" placeholder="{{ $isTeknik ? 'Nama, no normalisasi, atau komponen...' : 'Nama atau kategori...' }}"
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ $isTeknik ? 'Komponen' : 'Kategori' }}</label>
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
                        <a href="{{ route('stock.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i>
                            Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header flex-wrap gap-2">
                <span><i class="bi bi-clipboard-data-fill text-primary-custom me-2"></i>Rekap Stok Barang</span>
                <div class="d-flex flex-wrap gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-warning stock-trigger-btn"
                        data-bs-toggle="modal" data-bs-target="#stockRequestModal"
                        {{ $requestOrderCount + $outOfStockCount === 0 ? 'disabled' : '' }}>
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Request Order: {{ $requestOrderCount }}
                    </button>
                    <button type="button" class="btn btn-sm btn-danger stock-trigger-btn"
                        data-bs-toggle="modal" data-bs-target="#stockRequestModal"
                        {{ $requestOrderCount + $outOfStockCount === 0 ? 'disabled' : '' }}>
                        <i class="bi bi-x-circle-fill me-1"></i>
                        Out of Stock: {{ $outOfStockCount }}
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table" id="stock-table">
                        <thead>
                            @if($isTeknik)
                                <tr>
                                    <th style="width:50px;">No</th>
                                    <th>No Normalisasi</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Komponen</th>
                                    <th>Ship Unloader</th>
                                    <th>Lokasi</th>
                                    <th class="text-center">Volume</th>
                                    <th>Satuan</th>
                                    <th class="text-center">Total Masuk</th>
                                    <th class="text-center">Total Keluar</th>
                                    <th class="text-center">Stok Saat Ini</th>
                                    <th class="text-center">Min Stok</th>
                                    <th>Status</th>
                                </tr>
                            @else
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
                            @endif
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
                                    @if($isTeknik)
                                        <td class="fw-600">{{ $item->no_normalisasi ?? '-' }}</td>
                                        <td class="fw-600">{{ $item->name }}</td>
                                        <td>{{ $item->category }}</td>
                                        <td>{{ $item->component ?? '-' }}</td>
                                        <td>{{ $item->stock_ship_unloader_label }}</td>
                                        <td>{{ $item->lokasi ?? '-' }}</td>
                                        <td class="text-center fw-700">{{ $item->volume === null ? '-' : number_format($item->volume) }}</td>
                                        <td>{{ $item->unit }}</td>
                                    @else
                                        <td class="fw-600">{{ $item->name }}</td>
                                        <td>{{ $item->category }}</td>
                                        <td>{{ $item->unit }}</td>
                                    @endif
                                    <td class="text-center fw-600 text-success-custom">{{ number_format($totalMasuk) }}</td>
                                    <td class="text-center fw-600 text-danger-custom">{{ number_format($totalKeluar) }}</td>
                                    <td class="text-center fw-700"
                                        style="font-size:15px; {{ $currentStock <= 0 ? 'color:var(--danger);' : ($currentStock <= $item->min_stock ? 'color:var(--warning-dark);' : 'color:var(--success);') }}">
                                        {{ number_format($currentStock) }}
                                    </td>
                                    <td class="text-center">{{ $item->min_stock }}</td>
                                    <td>
                                        @if($currentStock <= 0)
                                            <span class="badge-status badge-rejected"><i class="bi bi-x-circle-fill"></i>
                                                Out of Stock</span>
                                        @elseif($currentStock <= $item->min_stock)
                                            <span class="badge-status badge-pending">
                                                <i class="bi bi-exclamation-triangle-fill"></i> Request Order
                                            </span>
                                        @else
                                            <span class="badge-status badge-approved"><i class="bi bi-check-circle-fill"></i> Ready</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="{{ $isTeknik ? 14 : 9 }}">
                                        <i class="bi bi-inbox"
                                            style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
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

    <div class="modal fade inventrack-modal" id="stockRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cart-plus-fill me-2"></i>Request Stok Barang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('stock-requests.store') }}" id="stockRequestForm">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 rounded-3 mb-3" style="background:var(--primary-bg);font-size:13px;color:var(--text-secondary);">
                            Data otomatis berisi seluruh barang berstatus <strong>Request Order</strong> dan <strong>Out of Stock</strong>.
                            @if(auth()->user()->isAdmin())
                                <br>Admin hanya dapat melihat daftar request stok. Submit request hanya tersedia untuk staf.
                            @endif
                        </div>
                        @error('lines')
                            <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:12px;">{{ $message }}</div>
                        @enderror
                        <div id="stockRequestCategoryGroups" class="d-flex flex-column gap-3"></div>
                        <div id="stockRequestEmptyState" class="empty-state d-none" style="padding:30px 10px;">
                            <i class="bi bi-check-circle" style="font-size:40px;color:var(--success);"></i>
                            <h6 class="mt-2" style="font-size:13px;">Tidak ada barang yang perlu diajukan</h6>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @if(auth()->user()->isStaff() || (auth()->user()->isAdmin() && $isTeknik))
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="stockRequestSubmitBtn">
                                <i class="bi bi-send-fill me-1"></i> Submit Request
                            </button>
                        @else
                            <span class="text-muted" style="font-size:12px;">{{ $isTeknik ? 'Hanya Admin Teknik yang dapat membuat request stok.' : 'Hanya staf yang dapat membuat request stok.' }}</span>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.__stockRequestItems = @json($stockRequestItems);
        window.__stockRequestReadonly = @json(auth()->user()->isAdmin() && !$isTeknik);
        window.__stockRequestIsTeknik = @json($isTeknik);

        (function () {
            const modalEl = document.getElementById('stockRequestModal');
            const groupsContainer = document.getElementById('stockRequestCategoryGroups');
            const emptyState = document.getElementById('stockRequestEmptyState');
            const submitBtn = document.getElementById('stockRequestSubmitBtn');
            if (!modalEl || !groupsContainer) return;

            function formatNumber(value) {
                return new Intl.NumberFormat('id-ID').format(value || 0);
            }

            function reindexRows() {
                groupsContainer.querySelectorAll('.stock-request-line-row').forEach(function (tr, index) {
                    tr.querySelectorAll('[data-name]').forEach(function (input) {
                        input.name = `lines[${index}][${input.dataset.name}]`;
                    });
                });

                groupsContainer.querySelectorAll('.stock-request-category-group').forEach(function (section) {
                    if (!section.querySelector('.stock-request-line-row')) {
                        section.remove();
                        return;
                    }

                    const subtitle = section.querySelector('.stock-request-category-subtitle');
                    const count = section.querySelectorAll('.stock-request-line-row').length;
                    if (subtitle) {
                        subtitle.textContent = `${count} barang perlu diajukan`;
                    }
                });

                const hasRows = groupsContainer.querySelectorAll('.stock-request-line-row').length > 0;
                const hasPendingRows = groupsContainer.querySelectorAll('.stock-request-line-row[data-has-pending="1"]').length > 0;
                emptyState.classList.toggle('d-none', hasRows);
                if (submitBtn) submitBtn.disabled = !hasRows || hasPendingRows;
            }

            function buildCategoryGroup(category, items) {
                const section = document.createElement('section');
                section.className = 'stock-request-category-group';
                section.innerHTML = `
                    <div class="stock-request-category-header">
                        <div>
                            <div class="stock-request-category-title">${category || 'Tanpa Kategori'}</div>
                            <div class="stock-request-category-subtitle">${items.length} barang perlu diajukan</div>
                        </div>
                    </div>
                    <div class="table-responsive rounded-3 border" style="border-color:var(--border-color) !important;">
                        <table class="table table-sm mb-0">
                            <thead>
                                ${window.__stockRequestIsTeknik ? `
                                    <tr>
                                        <th style="min-width:140px;">No Normalisasi</th>
                                        <th style="min-width:220px;">Nama Barang</th>
                                        <th style="min-width:140px;">Ship Unloader</th>
                                        <th style="min-width:140px;">Lokasi</th>
                                        <th style="width:120px;">Volume</th>
                                        <th style="width:150px;">Harga Barang</th>
                                        <th style="width:120px;">Jumlah</th>
                                        <th style="min-width:180px;">Keterangan</th>
                                        <th style="width:70px;" class="text-center">Hapus</th>
                                    </tr>
                                ` : `
                                    <tr>
                                        <th style="min-width:220px;">Nama Barang</th>
                                        <th style="width:150px;">Harga Barang</th>
                                        <th style="width:120px;">Jumlah</th>
                                        <th style="min-width:180px;">Keterangan</th>
                                        <th style="width:70px;" class="text-center">Hapus</th>
                                    </tr>
                                `}
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                `;

                const tbody = section.querySelector('tbody');
                items.forEach(function (item) {
                    tbody.appendChild(buildRow(item));
                });

                return section;
            }

            function buildRow(item) {
                const tr = document.createElement('tr');
                tr.className = 'stock-request-line-row';
                tr.dataset.hasPending = item.has_pending_request ? '1' : '0';
                const statusLabel = item.stock_status === 'out_of_stock' ? 'Out of Stock' : 'Request Order';
                const readonlyAttrs = window.__stockRequestReadonly ? 'readonly tabindex="-1"' : '';
                const disabledAttrs = window.__stockRequestReadonly ? 'disabled tabindex="-1"' : '';
                const pendingWarning = item.has_pending_request
                    ? '<div class="stock-request-inline-warning mt-1"><i class="bi bi-info-circle-fill me-1"></i>Barang ini masih memiliki request pending. Hapus baris ini untuk submit request baru.</div>'
                    : '';
                tr.innerHTML = window.__stockRequestIsTeknik ? `
                    <td>
                        <input type="hidden" data-name="item_id" value="${item.id}">
                        <input type="text" class="form-control form-control-sm fw-600" value="${item.no_normalisasi || '-'}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm fw-600" value="${item.name}" readonly>
                        <div class="small text-muted mt-1">${statusLabel} - stok ${formatNumber(item.current_stock)} ${item.unit || ''}</div>
                        ${pendingWarning}
                    </td>
                    <td><input type="text" class="form-control form-control-sm" value="${item.ship_unloader || '-'}" readonly></td>
                    <td><input type="text" class="form-control form-control-sm" value="${item.lokasi || '-'}" readonly></td>
                    <td><input type="text" class="form-control form-control-sm" value="${formatNumber(item.current_stock)}" readonly></td>
                    <td>
                        <input type="number" data-name="price" class="form-control form-control-sm" min="0" step="1" value="${item.price || 0}" ${readonlyAttrs} required>
                    </td>
                    <td>
                        <input type="number" data-name="quantity" class="form-control form-control-sm" min="1" value="1" ${readonlyAttrs} required>
                    </td>
                    <td>
                        <input type="text" data-name="description" class="form-control form-control-sm" maxlength="500" placeholder="Keterangan..." ${readonlyAttrs}>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-stock-request-line" title="Hapus" ${disabledAttrs}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                ` : `
                    <td>
                        <input type="hidden" data-name="item_id" value="${item.id}">
                        <input type="text" class="form-control form-control-sm fw-600" value="${item.name}" readonly>
                        <div class="small text-muted mt-1">${statusLabel} - stok ${formatNumber(item.current_stock)} ${item.unit || ''}</div>
                        ${pendingWarning}
                    </td>
                    <td>
                        <input type="number" data-name="price" class="form-control form-control-sm" min="0" step="1" value="${item.price || 0}" ${readonlyAttrs} required>
                    </td>
                    <td>
                        <input type="number" data-name="quantity" class="form-control form-control-sm" min="1" value="1" ${readonlyAttrs} required>
                    </td>
                    <td>
                        <input type="text" data-name="description" class="form-control form-control-sm" maxlength="500" placeholder="Keterangan..." ${readonlyAttrs}>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-stock-request-line" title="Hapus" ${disabledAttrs}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;

                const removeBtn = tr.querySelector('.btn-remove-stock-request-line');
                if (removeBtn && !window.__stockRequestReadonly) {
                    removeBtn.addEventListener('click', function () {
                        tr.remove();
                        reindexRows();
                    });
                }

                return tr;
            }

            modalEl.addEventListener('show.bs.modal', function () {
                groupsContainer.replaceChildren();
                const grouped = {};

                (window.__stockRequestItems || []).forEach(function (item) {
                    const category = item.category || 'Tanpa Kategori';
                    if (!grouped[category]) grouped[category] = [];
                    grouped[category].push(item);
                });

                Object.keys(grouped).sort().forEach(function (category) {
                    groupsContainer.appendChild(buildCategoryGroup(category, grouped[category]));
                });
                reindexRows();
            });
        })();
    </script>
@endpush
