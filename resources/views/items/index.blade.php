@extends('layouts.app')

@section('title', auth()->user()->isTeknik() ? 'Master SOH' : 'Master Barang')
@section('subtitle', auth()->user()->isTeknik() ? 'Kelola daftar spare part inventory' : 'Kelola daftar barang inventory')

@section('content')
    @php
        $isTeknik = auth()->user()->bidang === 'teknik';
        $itemLabel = $isTeknik ? 'Spare Part' : 'Barang';
        $itemLowerLabel = $isTeknik ? 'spare part' : 'barang';
        $componentLabel = $isTeknik ? 'Komponen' : 'Kategori';
        $itemDetailData = [];
        foreach ($items as $itemRow) {
            $itemDetailData[$itemRow->id] = [
                'name' => $itemRow->name,
                'no_normalisasi' => $itemRow->no_normalisasi ?: '-',
                'category' => $itemRow->category,
                'bidang' => $itemRow->bidang ? ucfirst($itemRow->bidang) : '-',
                'lokasi' => $itemRow->lokasi ?: '-',
                'volume' => $itemRow->current_stock,
                'ship_unloader' => $itemRow->ship_unloader_label,
                'unit' => $itemRow->unit,
                'min_stock' => $itemRow->min_stock,
                'current_stock' => $itemRow->current_stock,
            ];
        }
    @endphp
    <div class="animate-fade-in">
        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="{{ route('items.index') }}">
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label">Cari {{ $itemLabel }}</label>
                        <input type="text" name="search" class="form-control" placeholder="Nama atau {{ strtolower($componentLabel) }}..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ $componentLabel }}</label>
                        <select name="category" class="form-select">
                            <option value="">Semua {{ $componentLabel }}</option>
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
                            @if($isTeknik)
                                <tr>
                                    <th style="width:50px;">No</th>
                                    <th>No Normalisasi</th>
                                    <th>Nama Spare Part</th>
                                    <th>Komponen</th>
                                    <th>Lokasi</th>
                                    <th>Ship Unloader</th>
                                    <th>Satuan</th>
                                    <th>Min Stok</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Status</th>
                                    <th style="width:100px;">Aksi</th>
                                </tr>
                            @else
                                <tr>
                                    <th style="width:50px;">No</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    @if(auth()->user()->isSuperAdmin())
                                        <th>Bidang</th>
                                    @endif
                                    <th>Satuan</th>
                                    <th>Min Stok</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Status</th>
                                    <th style="width:100px;">Aksi</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @forelse($items as $index => $item)
                                <tr>
                                    <td>{{ $items->firstItem() + $index }}</td>
                                    @if($isTeknik)
                                        <td class="fw-600">{{ $item->no_normalisasi ?? '-' }}</td>
                                        <td class="fw-600">{{ $item->name }}</td>
                                        <td>{{ $item->category }}</td>
                                        <td>{{ $item->lokasi ?? '-' }}</td>
                                        <td>{{ $item->ship_unloader_label }}</td>
                                        <td>{{ $item->unit }}</td>
                                    @else
                                        <td class="fw-600">{{ $item->name }}</td>
                                        <td>{{ $item->category }}</td>
                                        @if(auth()->user()->isSuperAdmin())
                                            <td>{{ ucfirst($item->bidang) }}</td>
                                        @endif
                                        <td>{{ $item->unit }}</td>
                                    @endif
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
                                            <span class="badge-status badge-rejected">
                                                <i class="bi bi-x-circle-fill"></i> Out of Stock
                                            </span>

                                        @elseif($item->current_stock < $item->min_stock)
                                            <span class="badge-status badge-pending">
                                                <i class="bi bi-exclamation-triangle-fill"></i> Request Order
                                            </span>

                                        @else
                                            <span class="badge-status badge-approved">
                                                <i class="bi bi-check-circle-fill"></i> Ready
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-action edit" title="Edit"
                                                onclick="openItemModal({{ $item->id }})">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button type="button" class="btn-action view btn-item-detail-open" title="Lihat Detail"
                                                data-item-id="{{ $item->id }}">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <form action="{{ route('items.destroy', $item) }}" method="POST"
                                                id="deleteItem-{{ $item->id }}">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn-action delete" title="Hapus"
                                                    onclick="swalConfirm('Hapus {{ $itemLabel }}', 'Yakin hapus {{ $itemLowerLabel }} ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteItem-{{ $item->id }}')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="{{ $isTeknik ? 11 : (auth()->user()->isSuperAdmin() ? 9 : 8) }}">
                                        <i class="bi bi-inbox"
                                            style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                        Belum ada data {{ $itemLowerLabel }}
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

    <div class="modal fade inventrack-modal" id="itemDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-eye-fill me-2"></i>Detail {{ $itemLabel }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3" id="itemDetailGrid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
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
                        <i class="bi bi-plus-circle-fill"></i> <span>Tambah {{ $itemLabel }}</span>
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
                            <label class="form-label">Nama {{ $itemLabel }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Masukkan nama {{ $itemLowerLabel }}" required
                                id="itemName">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ $componentLabel }} <span class="text-danger">*</span></label>
                                <input type="text" name="category" list="category-list" class="form-control"
                                    placeholder="Pilih atau ketik {{ strtolower($componentLabel) }}" required id="itemCategory">
                                <datalist id="category-list">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                <input type="text" name="unit" list="unit-list" class="form-control"
                                    placeholder="Pilih atau ketik satuan" required id="itemUnit">
                                <datalist id="unit-list">
                                    @foreach($units as $u)
                                        <option value="{{ $u }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Minimum Stok <span class="text-danger">*</span></label>
                            <input type="number" name="min_stock" class="form-control" min="0" required id="itemMinStock"
                                value="0">
                            <small class="text-muted">Sistem akan memberikan peringatan jika stok di bawah angka ini</small>
                        </div>

                        @if($isTeknik || auth()->user()->isSuperAdmin())
                            <div class="technical-item-fields">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">No Normalisasi / No Seri</label>
                                        <input type="text" name="no_normalisasi" class="form-control" placeholder="Contoh: SU-01-MTR-001" id="itemNoNormalisasi">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Lokasi</label>
                                        <input type="text" name="lokasi" class="form-control" placeholder="Letak penyimpanan" id="itemLokasi">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Stok Saat Ini</label>
                                        <input type="number" name="current_stock" class="form-control" min="0" required id="itemCurrentStock" value="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ship Unloader</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach([1, 2, 3, 4] as $ship)
                                                <label class="form-check form-check-inline mb-0">
                                                    <input class="form-check-input item-ship-checkbox" type="checkbox" name="ship_unloader[]" value="{{ $ship }}">
                                                    <span class="form-check-label">SU - {{ $ship }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(auth()->user()->isSuperAdmin())
                            <div class="mb-3">
                                <label class="form-label">Bidang <span class="text-danger">*</span></label>
                                <select name="bidang" class="form-select" id="itemBidang" required>
                                    <option value="">-- Pilih Bidang --</option>
                                    <option value="umum">Umum</option>
                                    <option value="teknik">Teknik</option>
                                </select>
                            </div>
                        @endif
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
        window.__itemDetailData = @json($itemDetailData);
        const isTeknikItem = @json($isTeknik);
        const itemLabel = @json($itemLabel);
        const itemLowerLabel = @json($itemLowerLabel);
        const componentLabel = @json($componentLabel);
        const itemModalEl = document.getElementById('itemModal');
        const itemModal = new bootstrap.Modal(itemModalEl);
        const itemDetailModalEl = document.getElementById('itemDetailModal');
        const itemDetailModal = new bootstrap.Modal(itemDetailModalEl);

        function appendDetailCell(container, label, value) {
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

        document.querySelectorAll('.btn-item-detail-open').forEach(function(button) {
            button.addEventListener('click', function() {
                const data = window.__itemDetailData[this.dataset.itemId];
                if (!data) return;
                const grid = document.getElementById('itemDetailGrid');
                grid.replaceChildren();
                if (isTeknikItem) {
                    appendDetailCell(grid, 'No Normalisasi', data.no_normalisasi);
                    appendDetailCell(grid, 'Nama Spare Part', data.name);
                    appendDetailCell(grid, componentLabel, data.category);
                    appendDetailCell(grid, 'Lokasi', data.lokasi);
                    appendDetailCell(grid, 'Ship Unloader', data.ship_unloader);
                } else {
                    appendDetailCell(grid, 'Nama Barang', data.name);
                    appendDetailCell(grid, 'Kategori', data.category);
                    appendDetailCell(grid, 'Bidang', data.bidang);
                }
                appendDetailCell(grid, 'Satuan', data.unit);
                appendDetailCell(grid, 'Min Stok', data.min_stock);
                appendDetailCell(grid, 'Stok Saat Ini', data.current_stock);
                itemDetailModal.show();
            });
        });

        function openItemModal(id = null) {
            // Reset form
            document.getElementById('itemForm').reset();
            document.getElementById('itemError').style.display = 'none';
            document.querySelectorAll('#itemForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.getElementById('itemMinStock').value = '0';
            if (document.getElementById('itemCurrentStock')) {
                document.getElementById('itemCurrentStock').value = '0';
            }
            document.querySelectorAll('.item-ship-checkbox').forEach(el => el.checked = false);
            toggleTechnicalItemFields();

            if (id) {
                // Edit mode — load data
                document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> <span>Edit ' + itemLabel + '</span>';
                document.getElementById('itemId').value = id;
                document.getElementById('itemMethod').value = 'PUT';
                document.getElementById('itemSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update';

                const loading = document.getElementById('itemLoading');
                loading.classList.add('show');
                itemModal.show();

                fetch(`{{ url('items') }}/${id}/edit-data`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(res => res.json())
                    .then(data => {
                        loading.classList.remove('show');
                        document.getElementById('itemName').value = data.name;
                        document.getElementById('itemCategory').value = data.category;
                        document.getElementById('itemUnit').value = data.unit;
                        document.getElementById('itemMinStock').value = data.min_stock;
                        if (document.getElementById('itemNoNormalisasi')) {
                            document.getElementById('itemNoNormalisasi').value = data.no_normalisasi || '';
                            document.getElementById('itemLokasi').value = data.lokasi || '';
                            document.getElementById('itemCurrentStock').value = data.current_stock || 0;
                            const ships = (data.ship_unloader || '').split(',').filter(Boolean);
                            document.querySelectorAll('.item-ship-checkbox').forEach(el => el.checked = ships.includes(el.value));
                        }
                        if (document.getElementById('itemBidang')) {
                            document.getElementById('itemBidang').value = data.bidang || '';
                        }
                        toggleTechnicalItemFields();
                    })
                    .catch(() => {
                        loading.classList.remove('show');
                        document.getElementById('itemErrorMsg').textContent = 'Gagal memuat data ' + itemLowerLabel + '.';
                        document.getElementById('itemError').style.display = 'block';
                    });
            } else {
                // Create mode
                document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> <span>Tambah ' + itemLabel + '</span>';
                document.getElementById('itemId').value = '';
                document.getElementById('itemMethod').value = 'POST';
                document.getElementById('itemSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
                toggleTechnicalItemFields();
                itemModal.show();
            }
        }

        function toggleTechnicalItemFields() {
            const wrapper = document.querySelector('.technical-item-fields');
            if (!wrapper) return;
            const bidangSelect = document.getElementById('itemBidang');
            const show = !bidangSelect || bidangSelect.value === 'teknik';
            wrapper.style.display = show ? '' : 'none';
        }

        const itemBidangSelect = document.getElementById('itemBidang');
        if (itemBidangSelect) {
            itemBidangSelect.addEventListener('change', toggleTechnicalItemFields);
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
            const url = itemId ? `{{ url('items') }}/${itemId}` : '{{ route("items.store") }}';

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
        itemModalEl.addEventListener('hidden.bs.modal', function () {
            document.getElementById('itemForm').reset();
            document.getElementById('itemError').style.display = 'none';
            document.querySelectorAll('#itemForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        });
    </script>
@endpush
