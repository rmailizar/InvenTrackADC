@extends('layouts.app')

@section('title', 'Request Stok')
@section('subtitle', 'Daftar permintaan stok dari karyawan')

@section('content')
<div class="animate-fade-in">
    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form method="GET" action="{{ route('stock-requests.index') }}">
            <div class="row align-items-end g-3">
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama / barang..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('stock-requests.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header">
            <span>
                <i class="bi bi-inbox-fill text-primary-custom me-2"></i>Daftar Request Stok
                @if($pendingCount > 0)
                    <span class="badge bg-warning text-dark ms-2">{{ $pendingCount }} pending</span>
                @endif
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="stock-requests-table">
                    <thead>
                        <tr>
                            <th style="width:45px;">No</th>
                            <th>Tanggal</th>
                            <th>Nama Pemohon</th>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th class="text-center">Jumlah</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            @if(auth()->user()->isAdmin())
                            <th class="text-center" style="width:120px;">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $index => $req)
                        <tr>
                            <td>{{ $requests->firstItem() + $index }}</td>
                            <td style="white-space:nowrap;">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                            <td class="fw-600">{{ $req->requester_name }}</td>
                            <td class="fw-600">{{ $req->item->name ?? '-' }}</td>
                            <td>{{ $req->item->category ?? '-' }}</td>
                            <td class="text-center fw-700" style="color:var(--primary);">{{ $req->quantity }} {{ $req->item->unit ?? '' }}</td>
                            <td style="max-width:200px;">
                                @if($req->notes)
                                    <span style="font-size:12px;color:var(--text-secondary);">{{ Str::limit($req->notes, 50) }}</span>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px;">-</span>
                                @endif
                            </td>
                            <td>
                                @if($req->status === 'pending')
                                    <span class="badge-status" style="background:var(--warning-bg);color:var(--warning-dark);">
                                        <i class="bi bi-clock-fill"></i> Pending
                                    </span>
                                @elseif($req->status === 'approved')
                                    <span class="badge-status" style="background:var(--success-bg);color:var(--success);">
                                        <i class="bi bi-check-circle-fill"></i> Approved
                                    </span>
                                    @if($req->processor)
                                        <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                            oleh {{ $req->processor->name }}
                                        </div>
                                    @endif
                                @else
                                    <span class="badge-status" style="background:var(--danger-bg);color:var(--danger);">
                                        <i class="bi bi-x-circle-fill"></i> Rejected
                                    </span>
                                    @if($req->processor)
                                        <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                            oleh {{ $req->processor->name }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            @if(auth()->user()->isAdmin())
                            <td class="text-center">
                                @if($req->status === 'pending')
                                <div class="d-flex gap-1 justify-content-center">
                                    <form method="POST" action="{{ route('stock-requests.approve', $req) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Setujui request ini?')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('stock-requests.reject', $req) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Tolak request ini?')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </div>
                                @else
                                    <span style="font-size:11px;color:var(--text-muted);">—</span>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr class="no-data-row">
                            <td colspan="{{ auth()->user()->isAdmin() ? 9 : 8 }}">
                                <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                Tidak ada request stok
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($requests->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $requests->links('pagination.custom') }}
    </div>
    @endif
</div>
@endsection
