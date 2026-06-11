@php
    $isTeknik = auth()->user()->bidang === 'teknik';
    $currentSort = request('sort', 'latest') === 'oldest' ? 'oldest' : 'latest';
    $nextSort = $currentSort === 'oldest' ? 'latest' : 'oldest';
    $showCreateButton = $showCreateButton ?? true;
@endphp

<div id="transactionsTableRegion">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted" style="font-size:13px;">Total: {{ $transactions->total() }} transaksi</div>
        @if($showCreateButton)
            <button type="button" class="btn btn-primary" onclick="openTransactionModal()">
                <i class="bi bi-plus-lg"></i> Input Transaksi
            </button>
        @endif
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="transactions-table">
                    <thead>
                        @if($isTeknik)
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>
                                    <span class="date-sort-header">
                                        Tanggal
                                        <button type="button" class="btn-date-sort js-date-sort"
                                            data-sort="{{ $nextSort }}"
                                            title="Urutkan {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}"
                                            aria-label="Urutkan tanggal {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}">
                                            <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </span>
                                </th>
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
                                <th class="text-center" style="width:72px;">Aksi</th>
                            </tr>
                        @else
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>
                                    <span class="date-sort-header">
                                        Tanggal
                                        <button type="button" class="btn-date-sort js-date-sort"
                                            data-sort="{{ $nextSort }}"
                                            title="Urutkan {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}"
                                            aria-label="Urutkan tanggal {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}">
                                            <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </span>
                                </th>
                                <th>Jenis</th>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Satuan</th>
                                <th>Harga Satuan</th>
                                <th>User</th>
                                <th>Keterangan</th>
                                <th class="text-center" style="width:72px;">Aksi</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($transactions as $index => $tx)
                            <tr>
                                <td>{{ $transactions->firstItem() + $index }}</td>
                                <td>{{ $tx->date->format('d/m/Y') }}</td>
                                @if($isTeknik)
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
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-transaction-detail-open"
                                                title="Lihat Detail" data-transaction-id="{{ $tx->id }}">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                title="Edit" onclick="openTransactionModal({{ $tx->id }})">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form action="{{ route('transactions.destroy', $tx) }}" method="POST" id="deleteTx-{{ $tx->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"
                                                    onclick="swalConfirm('Hapus Transaksi', 'Yakin hapus transaksi ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteTx-{{ $tx->id }}')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @else
                                    <td>
                                        <span class="badge-status badge-{{ $tx->type }}">
                                            <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill" style="font-size:10px;"></i>
                                            {{ $tx->type_label }}
                                        </span>
                                    </td>
                                    <td class="fw-600">{{ $tx->item->name ?? '-' }}</td>
                                    <td>{{ $tx->item->category ?? '-' }}</td>
                                    <td class="fw-700">{{ number_format($tx->quantity) }}</td>
                                    <td>{{ $tx->item->unit ?? '-' }}</td>
                                    <td>{{ $tx->price === null ? '-' : 'Rp ' . number_format($tx->price, 0, ',', '.') }}</td>
                                    <td>{{ $tx->user->name ?? '-' }}</td>
                                    <td style="max-width:220px; font-size:12px; color:var(--text-secondary);">
                                        {{ \Illuminate\Support\Str::limit($tx->description, 60) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-transaction-detail-open"
                                            title="Lihat Detail" data-transaction-id="{{ $tx->id }}">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td colspan="{{ $isTeknik ? 13 : 11 }}">
                                    <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
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
