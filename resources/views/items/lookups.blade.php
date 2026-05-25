@extends('layouts.app')

@php
    $isTeknikLookup = auth()->user()->isTeknik();
    $lookupCategoryLabel = $isTeknikLookup ? 'Komponen' : 'Kategori';
    $lookupMasterLabel = $isTeknikLookup ? 'Master SOH' : 'Master Barang';
@endphp

@section('title', 'Kelola ' . $lookupCategoryLabel . ' & Satuan')
@section('subtitle', auth()->user()->isTeknik() || auth()->user()->isSuperAdmin() ? 'Ubah nama atau hapus (pindahkan) nilai ' . strtolower($lookupCategoryLabel) . ', satuan, dan Ship Unloader' : 'Ubah nama atau hapus (pindahkan) nilai kategori & satuan')

@section('content')
<div class="animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="text-muted" style="font-size:13px;">
            {{ $lookupCategoryLabel }}: <strong>{{ $categories->count() }}</strong> &middot; Satuan: <strong>{{ $units->count() }}</strong>
            @if(auth()->user()->isTeknik() || auth()->user()->isSuperAdmin())
                &middot; Ship Unloader: <strong>{{ $shipUnloaders->count() }}</strong>
            @endif
        </div>
        <a href="{{ route('items.index') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Kembali ke {{ $lookupMasterLabel }}
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-sliders2 text-primary-custom me-2"></i>Kelola opsi {{ strtolower($lookupCategoryLabel) }}, satuan{{ auth()->user()->isTeknik() || auth()->user()->isSuperAdmin() ? ' & Ship Unloader' : '' }}</span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-4">
                <strong>Ubah</strong> menerapkan ke semua barang yang memakai nilai tersebut.
                <strong>Hapus</strong> akan meminta nilai pengganti agar semua barang dipindahkan (nilai lama tidak lagi dipakai).
            </p>

            <div class="row g-4">
                <div class="{{ auth()->user()->isTeknik() || auth()->user()->isSuperAdmin() ? 'col-lg-4' : 'col-lg-6' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-700" style="font-size:13px; letter-spacing:0.04em;">{{ strtoupper($lookupCategoryLabel) }}</div>
                    </div>
                    <div class="border rounded-3" style="border-color: var(--border-color) !important;">
                        @forelse($categories as $cat)
                            <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom lookup-row" style="border-color: var(--border-color) !important;">
                                <span class="text-truncate" title="{{ $cat }}">{{ $cat }}</span>
                                <div class="d-flex gap-2 flex-shrink-0">
                                    <button type="button" class="btn btn-sm btn-outline-primary lookup-btn" data-type="category" data-from="{{ $cat }}" data-mode="edit">
                                        <i class="bi bi-pencil"></i> Ubah
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger lookup-btn" data-type="category" data-from="{{ $cat }}" data-mode="hapus">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="p-3 text-muted small">Belum ada data {{ strtolower($lookupCategoryLabel) }}.</div>
                        @endforelse
                    </div>
                </div>

                <div class="{{ auth()->user()->isTeknik() || auth()->user()->isSuperAdmin() ? 'col-lg-4' : 'col-lg-6' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-700" style="font-size:13px; letter-spacing:0.04em;">SATUAN</div>
                    </div>
                    <div class="border rounded-3" style="border-color: var(--border-color) !important;">
                        @forelse($units as $unit)
                            <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom lookup-row" style="border-color: var(--border-color) !important;">
                                <span class="text-truncate" title="{{ $unit }}">{{ $unit }}</span>
                                <div class="d-flex gap-2 flex-shrink-0">
                                    <button type="button" class="btn btn-sm btn-outline-primary lookup-btn" data-type="unit" data-from="{{ $unit }}" data-mode="edit">
                                        <i class="bi bi-pencil"></i> Ubah
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger lookup-btn" data-type="unit" data-from="{{ $unit }}" data-mode="hapus">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="p-3 text-muted small">Belum ada data satuan.</div>
                        @endforelse
                    </div>
                </div>

                @if(auth()->user()->isTeknik() || auth()->user()->isSuperAdmin())
                    <div class="col-lg-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-700" style="font-size:13px; letter-spacing:0.04em;">SHIP UNLOADER</div>
                        </div>
                        <div class="border rounded-3" style="border-color: var(--border-color) !important;">
                            @forelse($shipUnloaders as $ship)
                                <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom lookup-row" style="border-color: var(--border-color) !important;">
                                    <span class="text-truncate" title="Ship {{ $ship }}">Ship {{ $ship }}</span>
                                    <div class="d-flex gap-2 flex-shrink-0">
                                        <button type="button" class="btn btn-sm btn-outline-primary lookup-btn" data-type="ship_unloader" data-from="{{ $ship }}" data-mode="edit">
                                            <i class="bi bi-pencil"></i> Ubah
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger lookup-btn" data-type="ship_unloader" data-from="{{ $ship }}" data-mode="hapus">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="p-3 text-muted small">Belum ada data Ship Unloader.</div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade inventrack-modal" id="lookupReplaceModal" tabindex="-1" aria-labelledby="lookupReplaceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('items.lookup.replace') }}">
                @csrf
                <input type="hidden" name="type" id="lookup-type">
                <input type="hidden" name="from" id="lookup-from">
                <div class="modal-header">
                    <h5 class="modal-title" id="lookupReplaceModalLabel">Ubah {{ strtolower($lookupCategoryLabel) }} / satuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3" id="lookup-modal-hint"></p>
                    <label class="form-label" for="lookup-to">Nilai baru <span class="text-danger">*</span></label>
                    <input type="text" name="to" id="lookup-to" class="form-control" required placeholder="Ketik nilai pengganti" maxlength="255" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('lookupReplaceModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const hintEl = document.getElementById('lookup-modal-hint');
    const titleEl = document.getElementById('lookupReplaceModalLabel');
    const typeInput = document.getElementById('lookup-type');
    const fromInput = document.getElementById('lookup-from');
    const toInput = document.getElementById('lookup-to');

    document.querySelectorAll('.lookup-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const typ = btn.getAttribute('data-type');
            const from = btn.getAttribute('data-from');
            const mode = btn.getAttribute('data-mode');
            typeInput.value = typ;
            fromInput.value = from;
            toInput.value = '';

            const categoryLabel = @json(strtolower($lookupCategoryLabel));
            const labelJenis = typ === 'category' ? categoryLabel : (typ === 'ship_unloader' ? 'Ship Unloader' : 'satuan');
            const currentValue = typ === 'ship_unloader' ? 'Ship ' + from : from;
            if (mode === 'hapus') {
                titleEl.textContent = 'Hapus ' + labelJenis;
                hintEl.innerHTML = 'Semua barang dengan ' + labelJenis + ' <strong>' + escapeHtml(currentValue) + '</strong> akan dipindahkan ke nilai baru berikut:';
            } else {
                titleEl.textContent = 'Ubah nama ' + labelJenis;
                hintEl.innerHTML = 'Ganti <strong>' + escapeHtml(currentValue) + '</strong> menjadi (untuk semua barang yang memakainya):';
            }
            toInput.placeholder = typ === 'ship_unloader' ? 'Isi 1, 2, 3, atau 4' : 'Ketik nilai pengganti';

            modal.show();
            setTimeout(function() { toInput.focus(); }, 250);
        });
    });

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
});
</script>
@endpush
