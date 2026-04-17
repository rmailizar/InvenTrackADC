<div class="mt-3 p-3 rounded-3 border" style="background: var(--body-bg); border-color: var(--border-color) !important;">
    <div class="fw-600 mb-2" style="font-size:13px;">
        <i class="bi bi-sliders2 text-primary-custom me-1"></i>Kelola opsi kategori &amp; satuan
    </div>
    <p class="text-muted small mb-3">Ubah nama menerapkan ke <strong>semua barang</strong> yang memakai nilai tersebut. Hapus memindahkan semua barang ke nilai baru sehingga nilai lama tidak lagi dipakai.</p>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="small text-uppercase text-muted fw-600 mb-2" style="font-size:11px; letter-spacing:0.04em;">Kategori</div>
            @forelse($categories as $cat)
                <div class="d-flex align-items-center justify-content-between gap-2 py-1 border-bottom" style="border-color: var(--border-color) !important;">
                    <span class="text-truncate" title="{{ $cat }}">{{ $cat }}</span>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 lookup-replace-btn" data-type="category" data-from="{{ $cat }}" data-mode="edit" title="Ubah nama">Ubah</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 lookup-replace-btn" data-type="category" data-from="{{ $cat }}" data-mode="hapus" title="Hapus / pindahkan">Hapus</button>
                    </div>
                </div>
            @empty
                <span class="text-muted small">Belum ada data kategori.</span>
            @endforelse
        </div>
        <div class="col-md-6">
            <div class="small text-uppercase text-muted fw-600 mb-2" style="font-size:11px; letter-spacing:0.04em;">Satuan</div>
            @forelse($units as $unit)
                <div class="d-flex align-items-center justify-content-between gap-2 py-1 border-bottom" style="border-color: var(--border-color) !important;">
                    <span class="text-truncate" title="{{ $unit }}">{{ $unit }}</span>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 lookup-replace-btn" data-type="unit" data-from="{{ $unit }}" data-mode="edit" title="Ubah nama">Ubah</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 lookup-replace-btn" data-type="unit" data-from="{{ $unit }}" data-mode="hapus" title="Hapus / pindahkan">Hapus</button>
                    </div>
                </div>
            @empty
                <span class="text-muted small">Belum ada data satuan.</span>
            @endforelse
        </div>
    </div>
</div>

<div class="modal fade" id="lookupReplaceModal" tabindex="-1" aria-labelledby="lookupReplaceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('items.lookup.replace') }}">
                @csrf
                <input type="hidden" name="type" id="lookup-type">
                <input type="hidden" name="from" id="lookup-from">
                <div class="modal-header">
                    <h5 class="modal-title" id="lookupReplaceModalLabel">Ubah kategori / satuan</h5>
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

    document.querySelectorAll('.lookup-replace-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const typ = btn.getAttribute('data-type');
            const from = btn.getAttribute('data-from');
            const mode = btn.getAttribute('data-mode');
            typeInput.value = typ;
            fromInput.value = from;
            toInput.value = '';

            const isCat = typ === 'category';
            const labelJenis = isCat ? 'kategori' : 'satuan';
            if (mode === 'hapus') {
                titleEl.textContent = 'Hapus ' + labelJenis;
                hintEl.innerHTML = 'Semua barang dengan ' + labelJenis + ' <strong>' + escapeHtml(from) + '</strong> akan memakai ' + labelJenis + ' baru berikut (nilai lama tidak lagi dipakai):';
            } else {
                titleEl.textContent = 'Ubah nama ' + labelJenis;
                hintEl.innerHTML = 'Ganti <strong>' + escapeHtml(from) + '</strong> menjadi (untuk semua barang yang memakainya):';
            }
            modal.show();
            setTimeout(function() { toInput.focus(); }, 300);
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
