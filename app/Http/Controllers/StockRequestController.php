<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockRequest;
use Illuminate\Http\Request;

class StockRequestController extends Controller
{
    /**
     * Public landing page: stock recap + request form (no auth required).
     */
    public function publicIndex(Request $request)
    {
        $query = Item::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $items = $query->orderBy('name')->get();
        $categories = Item::select('category')->distinct()->pluck('category');
        $allItems = Item::orderBy('name')->get(); // for the select dropdown

        return view('public.stock-request', compact('items', 'categories', 'allItems'));
    }

    /**
     * Store a stock request (no auth required).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name' => 'required|string|max:255',
            'nip' => 'required|string|max:50',
            'jabatan' => 'required|string|max:100',
            'bidang' => 'required|string|max:100',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'requester_name.required' => 'Nama wajib diisi.',
            'nip.required' => 'NIP wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'bidang.required' => 'Bidang wajib diisi.',
            'item_id.required' => 'Silakan pilih barang.',
            'item_id.exists' => 'Barang tidak ditemukan.',
            'quantity.required' => 'Jumlah wajib diisi.',
            'quantity.min' => 'Jumlah minimal 1.',
        ]);

        $validated['status'] = 'pending';

        StockRequest::create($validated);

        return redirect()->route('public.stock-request')
            ->with('success', 'Request stok berhasil dikirim! Permintaan Anda akan ditinjau oleh Admin.');
    }

    /**
     * Admin/staff: list stock requests with date filter.
     */
    public function adminIndex(Request $request)
    {
        $query = StockRequest::with(['item', 'processor', 'completer'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('requester_name', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->paginate(15)->withQueryString();

        $pendingCount = StockRequest::pending()->count();

        return view('stock-requests.index', compact('requests', 'pendingCount'));
    }

    /**
     * Approve a stock request.
     */
    public function approve(StockRequest $stockRequest)
    {
        if ($stockRequest->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $stockRequest->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', "Request stok dari {$stockRequest->requester_name} telah disetujui.");
    }

    /**
     * Reject a stock request.
     */
    public function reject(StockRequest $stockRequest)
    {
        if ($stockRequest->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $stockRequest->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', "Request stok dari {$stockRequest->requester_name} telah ditolak.");
    }

    /**
     * Mark an approved stock request as completed.
     */
    public function complete(StockRequest $stockRequest)
    {
        if ($stockRequest->status !== 'approved') {
            return back()->with('error', 'Hanya request yang sudah disetujui yang bisa diselesaikan.');
        }

        $stockRequest->update([
            'status' => 'completed',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        return back()->with('success', "Request stok dari {$stockRequest->requester_name} telah diselesaikan.");
    }

    /**
     * Cancel an approved stock request.
     */
    public function cancel(StockRequest $stockRequest)
    {
        if ($stockRequest->status !== 'approved') {
            return back()->with('error', 'Hanya request yang sudah disetujui yang bisa dibatalkan.');
        }

        $stockRequest->update([
            'status' => 'cancelled',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        return back()->with('success', "Request stok dari {$stockRequest->requester_name} telah dibatalkan.");
    }
}
