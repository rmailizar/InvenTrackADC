@extends('layouts.app')

@section('title', 'Laporan')
@section('subtitle', 'Laporan transaksi dan rekap stok inventory')

@section('content')
    @php
        $isStock = $activeTable === 'stock';
        $isTeknik = auth()->user()->bidang === 'teknik';
        $transactionTabParams = request()->only(['month', 'category', 'type', 'year', 'price_filter', 'sort']);
        $stockTabParams = request()->only(['search', 'category', 'stock_status']);
        $exportParams = $isStock
            ? request()->only(['search', 'category', 'stock_status'])
            : request()->except('page');
        $exportParams['table'] = $activeTable;
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

    <div class="animate-fade-in">
        <div class="report-tabs mb-3">
            <a href="{{ route('reports.index', array_merge($transactionTabParams, ['table' => 'transactions'])) }}"
                class="report-tab {{ !$isStock ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i>
                Data Transaksi (Approved)
            </a>
            <a href="{{ route('reports.index', array_merge($stockTabParams, ['table' => 'stock'])) }}"
                class="report-tab {{ $isStock ? 'active' : '' }}">
                <i class="bi bi-clipboard-data-fill"></i>
                Rekap Stok
            </a>
        </div>

        <!-- Filter Bar -->
        <!-- Header Actions Wrapper -->
        <div class="header-action-wrapper d-none">
            <div class="section-header-actions">
                <form method="GET" action="{{ route('reports.index') }}">
                    <input type="hidden" name="table" value="{{ $activeTable }}">
                    <div class="action-row-1">
                        @if($isStock)
                            <div class="position-relative" id="reportsSearchWrapper">
                                <input type="text"
                                    id="reportsSearchInput"
                                    class="form-control form-control-sm"
                                    name="search"
                                    value="{{ request('search') }}"
                                    autocomplete="off"
                                    placeholder="Cari barang..."
                                    style="width: 160px;">
                                <div id="reportsSearchSuggestions" class="autocomplete-suggestions" style="display:none;"></div>
                            </div>
                            <select name="category" class="form-select form-select-sm" style="width: 130px;" onchange="this.form.submit()">
                                <option value="">Semua {{ $isTeknik ? 'Tipe' : 'Kategori' }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                            <select name="stock_status" class="form-select form-select-sm" style="width: 110px;" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Stok Rendah</option>
                            </select>
                        @else
                            <select name="year" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                                <option value="">Semua Tahun</option>
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                            <select name="month" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                                <option value="">Semua Bulan</option>
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <select name="category" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                                <option value="">Semua {{ $isTeknik ? 'Tipe' : 'Kategori' }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                            <select name="type" class="form-select form-select-sm" style="width: 100px;" onchange="this.form.submit()">
                                <option value="">Semua Jenis</option>
                                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>{{ $isTeknik ? 'GR (IN)' : 'In' }}</option>
                                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>{{ $isTeknik ? 'GI (OUT)' : 'Out' }}</option>
                            </select>
                            @unless($isTeknik)
                                <select name="price_filter" class="form-select form-select-sm" style="width: 110px;" onchange="this.form.submit()">
                                    <option value="">Semua Harga</option>
                                    <option value="tertinggi" {{ request('price_filter') == 'tertinggi' ? 'selected' : '' }}>Tertinggi</option>
                                    <option value="terendah" {{ request('price_filter') == 'terendah' ? 'selected' : '' }}>Terendah</option>
                                </select>
                            @endunless
                        @endif
                    </div>
                    <div class="action-row-2">
                        <a href="{{ route('reports.index', ['table' => $activeTable]) }}" class="btn btn-reset btn-sm" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards + Export Button -->
        <div class="row g-3 mb-4 report-summary-cards">
            <div class="col-sm-6 col-lg-3">
                <div class="stats-card success">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
                        <div class="stats-copy">
                            <div class="stats-value" style="font-size:22px;">{{ number_format($totalMasuk) }}</div>
                            <div class="stats-label">{{ $isTeknik ? 'Total Goods Receipt' : 'Total Masuk' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stats-card danger">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
                        <div class="stats-copy">
                            <div class="stats-value" style="font-size:22px;">{{ number_format($totalKeluar) }}</div>
                            <div class="stats-label">{{ $isTeknik ? 'Total Goods Issue' : 'Total Keluar' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stats-card warning">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon"><i class="bi bi-calculator-fill"></i></div>
                        <div class="stats-copy">
                            <div class="stats-value" style="font-size:22px;">{{ number_format($totalAkhir) }}</div>
                            <div class="stats-label">{{ $isStock ? 'Stok Akhir' : 'Selisih' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('reports.export', $exportParams) }}" class="text-decoration-none" id="reportExportLink">
                    <div class="stats-card primary" style="cursor:pointer;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stats-icon">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                            </div>
                            <div class="stats-copy">
                                <div class="stats-value" style="font-size:22px; color:var(--primary);">
                                    Export
                                </div>
                                <div class="stats-label">Download Excel</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Table -->
        @if($isStock)
        <div class="card">
            <div class="card-header">
                <span>
                    <i class="bi {{ $isStock ? 'bi-clipboard-data-fill' : 'bi-table' }} me-2"></i>
                    {{ $isStock ? 'Rekap Stok' : 'Data Transaksi (Approved)' }}
                </span>
                <span class="text-muted" style="font-size:12px;">
                    {{ $isStock ? $stockItems->total() : $transactions->total() }} data
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    @if($isStock)
                        <table class="table" id="reports-stock-table">
                            <thead>
                                @if($isTeknik)
                                    <tr>
                                        <th style="width:50px;">No</th>
                                        <th>No Normalisasi</th>
                                        <th>Nama Barang</th>
                                        <th>Komponen</th>
                                        <th>Tipe Barang</th>
                                        <th>Ship Unloader</th>
                                        <th>Lokasi</th>
                                        <th class="text-center">Volume</th>
                                        <th>Satuan</th>
                                        <th class="text-center">Total Goods Receipt</th>
                                        <th class="text-center">Total Goods Issue</th>
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
                                @forelse($stockItems as $index => $row)
                                    <tr data-item-id="{{ $row->item->id }}"
                                        data-name="{{ $row->item->name }}"
                                        data-normalisasi="{{ $row->item->no_normalisasi ?? '' }}"
                                        data-component="{{ $row->item->component ?? '' }}"
                                        data-category="{{ $row->item->category }}">
                                        <td>{{ $stockItems->firstItem() + $index }}</td>
                                        @if($isTeknik)
                                            <td class="fw-600">{{ $row->item->no_normalisasi ?? '-' }}</td>
                                            <td class="fw-600">{{ $row->item->name }}</td>
                                            <td>{{ $row->item->component ?? '-' }}</td>
                                            <td>{{ $row->item->category }}</td>
                                            <td>{{ $row->item->stock_ship_unloader_label }}</td>
                                            <td>{{ $row->item->lokasi ?? '-' }}</td>
                                            <td class="text-center fw-700">{{ $row->item->volume === null ? '-' : number_format($row->item->volume) }}</td>
                                            <td>{{ $row->item->unit }}</td>
                                        @else
                                            <td class="fw-600">{{ $row->item->name }}</td>
                                            <td>{{ $row->item->category }}</td>
                                            <td>{{ $row->item->unit }}</td>
                                        @endif
                                        <td class="text-center fw-600 text-success-custom">{{ number_format($row->masuk) }}</td>
                                        <td class="text-center fw-600 text-danger-custom">{{ number_format($row->keluar) }}</td>
                                        <td class="text-center fw-700"
                                            style="{{ $row->stok_akhir <= 0 ? 'color:var(--danger);' : ($row->stok_akhir <= $row->item->min_stock ? 'color:var(--warning-dark);' : 'color:var(--success);') }}">
                                            {{ number_format($row->stok_akhir) }}
                                        </td>
                                        <td class="text-center">{{ number_format($row->item->min_stock) }}</td>
                                        <td>
                                            @if($row->stok_akhir <= 0)
                                                <span class="badge-status badge-rejected"><i class="bi bi-x-circle-fill"></i>
                                                    Out of Stock</span>
                                            @elseif($row->stok_akhir <= $row->item->min_stock)
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
                                            Tidak ada data stok untuk filter ini
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <table class="table">
                            <thead>
                                @if($isTeknik)
                                    <tr>
                                        <th style="width:50px;">No</th>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>No Normalisasi</th>
                                        <th>Nama Barang</th>
                                        <th>Komponen</th>
                                        <th>Tipe Barang</th>
                                        <th>Ship Unloader</th>
                                        <th>Lokasi</th>
                                        <th class="text-center">Volume</th>
                                        <th class="text-center">Jumlah</th>
                                        <th>Satuan</th>
                                        <th>User</th>
                                        <th>Status</th>
                                    </tr>
                                @else
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
                                        <th>Keterangan</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody>
                                @forelse($transactions as $index => $tx)
                                    <tr>
                                        <td>{{ $transactions->firstItem() + $index }}</td>
                                        <td>{{ $tx->date->format('d/m/Y') }}</td>
                                        @if($isTeknik)
                                            <td>
                                                <span class="badge-status badge-{{ $tx->type }}">
                                                    <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"
                                                        style="font-size:10px;"></i>
                                                    {{ $tx->type_label }}
                                                </span>
                                            </td>
                                            <td class="fw-600">{{ $tx->no_normalisasi ?? $tx->item->no_normalisasi ?? '-' }}</td>
                                            <td class="fw-600">{{ $tx->item->name ?? '-' }}</td>
                                            <td>{{ $tx->item->component ?? '-' }}</td>
                                            <td>{{ $tx->item->category ?? '-' }}</td>
                                            <td>{{ $tx->ship_unloader_label }}</td>
                                            <td>{{ $tx->lokasi ?? $tx->item->lokasi ?? '-' }}</td>
                                            <td class="text-center fw-700">{{ $tx->volume === null ? '-' : number_format($tx->volume) }}</td>
                                            <td class="text-center fw-700">{{ number_format($tx->quantity) }}</td>
                                            <td>{{ $tx->item->unit ?? '-' }}</td>
                                            <td>{{ $tx->user->name ?? '-' }}</td>
                                            <td><span class="badge-status badge-approved">Approved</span></td>
                                        @else
                                            <td class="fw-600">{{ $tx->item->name ?? '-' }}</td>
                                            <td>{{ $tx->item->category ?? '-' }}</td>
                                            <td>
                                                <span class="badge-status badge-{{ $tx->type }}">
                                                    <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"
                                                        style="font-size:10px;"></i>
                                                    {{ $tx->type_label }}
                                                </span>
                                            </td>
                                            <td class="fw-700">{{ number_format($tx->quantity) }}</td>
                                            <td>{{ $tx->price === null ? '-' : 'Rp ' . number_format($tx->price, 0, ',', '.') }}</td>
                                            <td>{{ $tx->item->unit ?? '-' }}</td>
                                            <td>{{ $tx->user->name ?? '-' }}</td>
                                            <td style="max-width:200px; font-size:12px; color:var(--text-secondary);">
                                                {{ \Illuminate\Support\Str::limit($tx->description, 50) }}
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr class="no-data-row">
                                        <td colspan="{{ $isTeknik ? 14 : 10 }}">
                                            <i class="bi bi-inbox"
                                                style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                            Tidak ada data untuk filter ini
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        @if($isStock && $stockItems->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $stockItems->links('pagination.custom') }}
            </div>
        @endif
        @else
            @include('reports.partials.transactions-table', ['transactions' => $transactions])
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        function updateReportExportLink(url) {
            const exportLink = document.getElementById('reportExportLink');
            if (!exportLink) return;

            const exportUrl = new URL(exportLink.href);
            const currentUrl = new URL(url.toString());
            const sort = currentUrl.searchParams.get('sort');

            if (sort) {
                exportUrl.searchParams.set('sort', sort);
            } else {
                exportUrl.searchParams.delete('sort');
            }

            exportLink.href = exportUrl.toString();
        }

        function bindReportDateSort() {
            document.querySelectorAll('.js-report-date-sort').forEach(function(button) {
                button.addEventListener('click', function() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('table', 'transactions');
                    url.searchParams.set('sort', this.dataset.sort || 'latest');
                    url.searchParams.delete('page');
                    loadReportTransactionsTable(url);
                });
            });
        }

        function loadReportTransactionsTable(url) {
            const region = document.getElementById('reportTransactionsTableRegion');
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
                    window.history.pushState({}, '', url.toString());
                    updateReportExportLink(url);
                    bindReportDateSort();
                })
                .catch(() => {
                    Toast.fire({ icon: 'error', title: 'Gagal mengurutkan data laporan.' });
                    region.classList.remove('table-ajax-loading');
                });
        }

        bindReportDateSort();
        
        // Autocomplete search suggestions for Reports Stock
        (function() {
            const searchInput = document.getElementById('reportsSearchInput');
            const suggestionsBox = document.getElementById('reportsSearchSuggestions');
            const searchWrapper = document.getElementById('reportsSearchWrapper');
            const isTeknik = @json($isTeknik);

            if (!searchInput || !suggestionsBox || !searchWrapper) return;

            searchInput.addEventListener('input', function() {
                const keyword = this.value.trim().toLowerCase();
                if (!keyword) {
                    resetTable();
                    suggestionsBox.style.display = 'none';
                    return;
                }

                const rows = document.querySelectorAll('#reports-stock-table tbody tr:not(.no-data-row)');
                let results = [];

                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const normalisasi = row.getAttribute('data-normalisasi') || '';
                    const component = row.getAttribute('data-component') || '';
                    const category = row.getAttribute('data-category') || '';

                    let matched = false;
                    if (isTeknik) {
                        matched = name.toLowerCase().includes(keyword) || 
                                  normalisasi.toLowerCase().includes(keyword) || 
                                  component.toLowerCase().includes(keyword);
                    } else {
                        matched = name.toLowerCase().includes(keyword) || 
                                  category.toLowerCase().includes(keyword);
                    }

                    if (matched) {
                        results.push({
                            id: row.getAttribute('data-item-id'),
                            name: row.getAttribute('data-name'),
                            normalisasi: row.getAttribute('data-normalisasi'),
                            component: row.getAttribute('data-component'),
                            category: row.getAttribute('data-category')
                        });
                    }
                });

                renderSuggestions(results);
            });

            function renderSuggestions(items) {
                if (!items.length) {
                    suggestionsBox.innerHTML = '<div class="autocomplete-no-result">Tidak ada barang ditemukan</div>';
                    suggestionsBox.style.display = 'block';
                    return;
                }

                suggestionsBox.innerHTML = items.map(item => `
                    <div class="autocomplete-item" data-id="${item.id}">
                        <div class="autocomplete-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="autocomplete-content">
                            <div class="autocomplete-title">${item.name}</div>
                            <div class="autocomplete-subtitle">
                                ${isTeknik ? `${item.normalisasi || '-'} • ${item.component || '-'}` : `${item.category || '-'}`}
                            </div>
                        </div>
                    </div>
                `).join('');

                suggestionsBox.style.display = 'block';

                suggestionsBox.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('click', function() {
                        searchInput.value = this.querySelector('.autocomplete-title').textContent.trim();
                        filterTable(this.getAttribute('data-id'));
                    });
                });
            }

            function filterTable(id) {
                document.querySelectorAll('#reports-stock-table tbody tr:not(.no-data-row)').forEach(row => {
                    row.style.display = row.getAttribute('data-item-id') === id ? '' : 'none';
                });
                suggestionsBox.style.display = 'none';
            }

            function resetTable() {
                document.querySelectorAll('#reports-stock-table tbody tr:not(.no-data-row)').forEach(row => {
                    row.style.display = '';
                });
            }

            document.addEventListener('click', function(e) {
                if (searchWrapper && !searchWrapper.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });

            searchInput.addEventListener('focus', function() {
                if (this.value.trim() !== '' && suggestionsBox.innerHTML.trim() !== '') {
                    suggestionsBox.style.display = 'block';
                }
            });
        })();
    </script>
@endpush
