<div class="section-title">
    <i class="bi bi-arrow-left-right"></i> Input Transaksi Teknik
</div>

<div class="row g-4 public-technical-transaction-forms">
    @foreach([
        ['type' => 'in', 'title' => 'Goods Receipt', 'icon' => 'bi-box-arrow-in-down', 'button' => 'Simpan Goods Receipt'],
        ['type' => 'out', 'title' => 'Goods Issue', 'icon' => 'bi-box-arrow-up', 'button' => 'Simpan Goods Issue'],
    ] as $form)
        @php
            $prefix = $form['type'] === 'in' ? 'publicReceipt' : 'publicIssue';
            $isOldForm = old('type') === $form['type'];
        @endphp
        <div class="col-lg-6">
            <div class="request-form-card h-100 public-technical-transaction-card">
                <div class="request-form-header">
                    <h5><i class="bi {{ $form['icon'] }} me-2"></i>{{ $form['title'] }}</h5>
                    <p>Input {{ strtolower($form['title']) }} spare part bidang Teknik</p>
                </div>
                <div class="request-form-body">
                    <form method="POST" action="{{ route('public.teknik.transactions.store') }}" data-public-technical-form data-type="{{ $form['type'] }}">
                        @csrf
                        <input type="hidden" name="type" value="{{ $form['type'] }}">

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="{{ $isOldForm ? old('date', date('Y-m-d')) : date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Spare Part <span class="text-danger">*</span></label>
                                <select name="item_id" class="form-select" data-public-item-select required>
                                    <option value="">-- Pilih Spare Part --</option>
                                    @foreach($allItems as $item)
                                        <option value="{{ $item->id }}"
                                            data-stock="{{ $item->current_stock }}"
                                            data-unit="{{ $item->unit }}"
                                            data-category="{{ $item->category }}"
                                            data-component="{{ $item->component }}"
                                            data-normalization="{{ $item->no_normalisasi }}"
                                            data-location="{{ $item->lokasi }}"
                                            @selected($isOldForm && (string) old('item_id') === (string) $item->id)>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($isOldForm) @error('item_id')<div class="text-danger mt-1 small">{{ $message }}</div>@enderror @endif
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label">No. Normalisasi</label>
                                <input type="text" class="form-control" data-public-item-detail="normalization" readonly>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Komponen</label>
                                <input type="text" class="form-control" data-public-item-detail="component" readonly>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Tipe Barang</label>
                                <input type="text" class="form-control" data-public-item-detail="category" readonly>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Lokasi</label>
                                <input type="text" class="form-control" data-public-item-detail="location" readonly>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Stok</label>
                                <input type="text" class="form-control" data-public-item-detail="stock" readonly>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label">Ship Unloader <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach([1, 2, 3, 4] as $ship)
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="ship_unloader[]" value="{{ $ship }}"
                                                @checked($isOldForm && in_array((string) $ship, old('ship_unloader', []), true))>
                                            <span class="form-check-label">Ship {{ $ship }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @if($isOldForm) @error('ship_unloader')<div class="text-danger mt-1 small">{{ $message }}</div>@enderror @endif
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="quantity" class="form-control" min="1" value="{{ $isOldForm ? old('quantity') : '' }}" data-public-quantity required>
                                    <span class="input-group-text" data-public-item-unit>-</span>
                                </div>
                                <div class="text-danger mt-1 small d-none" data-public-stock-warning>Jumlah melebihi stok tersedia.</div>
                                @if($isOldForm) @error('quantity')<div class="text-danger mt-1 small">{{ $message }}</div>@enderror @endif
                            </div>

                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Keterangan tambahan (opsional)">{{ $isOldForm ? old('description') : '' }}</textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="margin-top: 10px !important;">
                            <i class="bi bi-send-fill me-1"></i>{{ $form['button'] }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
