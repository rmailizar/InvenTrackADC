@php
    $isTeknik = auth()->user()->bidang === 'teknik';
    $currentSort = request('sort', 'latest') === 'oldest' ? 'oldest' : 'latest';
    $nextSort = $currentSort === 'oldest' ? 'latest' : 'oldest';
@endphp

<div id="reportTransactionsTableRegion">
    <div class="card">
        <div class="card-header">
            <span>
                <i class="bi bi-table me-2"></i>
                Data Transaksi (Approved)
            </span>
            <span class="text-muted" style="font-size:12px;">
                {{ $transactions->total() }} data
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        @if($isTeknik)
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>
                                    <span class="date-sort-header">
                                        Tanggal
                                        <button type="button" class="btn-date-sort js-report-date-sort"
                                            data-sort="{{ $nextSort }}"
                                            title="Urutkan {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}"
                                            aria-label="Urutkan tanggal {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}">
                                            <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </span>
                                </th>
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
                                <th>
                                    <span class="date-sort-header">
                                        Tanggal
                                        <button type="button" class="btn-date-sort js-report-date-sort"
                                            data-sort="{{ $nextSort }}"
                                            title="Urutkan {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}"
                                            aria-label="Urutkan tanggal {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}">
                                            <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </span>
                                </th>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Satuan</th>
                                <th>Harga Satuan</th>
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
                                    <td>{{ $tx->item->unit ?? '-' }}</td>
                                    <td>{{ $tx->price === null ? '-' : 'Rp ' . number_format($tx->price, 0, ',', '.') }}</td>
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
            </div>
        </div>
    </div>

    @if($transactions->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $transactions->links('pagination.custom') }}
        </div>
    @endif
</div>
