<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['item', 'user', 'approver'])->visibleFor(auth()->user());

        // Staff can only see their own transactions
        if (auth()->user()->isStaff()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_normalisasi', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%")
                    ->orWhereHas('item', fn($itemQuery) => $itemQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('no_normalisasi', 'like', "%{$search}%")
                        ->orWhere('lokasi', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $sort = $request->input('sort', 'latest') === 'oldest' ? 'asc' : 'desc';
        $transactions = $query
            ->orderBy('date', $sort)
            ->orderBy('created_at', $sort)
            ->orderBy('id', $sort)
            ->paginate(15)
            ->withQueryString();

        // Pass items for modal dropdown
        $items = Item::visibleFor(auth()->user())->orderBy('name')->get();
        $transactionDetailData = $this->transactionDetailData($transactions);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('transactions.partials.table', compact('transactions'))->render(),
                'detailData' => $transactionDetailData,
                'total' => $transactions->total(),
                'sort' => $request->input('sort', 'latest') === 'oldest' ? 'oldest' : 'latest',
            ]);
        }

        return view('transactions.index', compact('transactions', 'items', 'transactionDetailData'));
    }

    public function create()
    {
        return redirect()->route('transactions.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:in,out',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'ship_unloader' => 'nullable|array',
            'ship_unloader.*' => 'in:1,2,3,4',
        ]);

        $item = Item::findOrFail($validated['item_id']);
        abort_unless(auth()->user()->canAccessBidang($item->bidang), 403, 'Anda tidak memiliki akses ke barang bidang ini.');

        if ($validated['type'] === 'out') {
            if ($item->current_stock < $validated['quantity']) {
                $errorMsg = 'Stok tidak mencukupi. Stok saat ini: ' . $item->current_stock . ' ' . $item->unit;
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return back()->withInput()->with('error', $errorMsg);
            }
        }

        $validated['user_id'] = auth()->id();
        $validated['bidang'] = $item->bidang;
        $validated['no_normalisasi'] = $item->no_normalisasi;
        $validated['lokasi'] = $item->lokasi;
        $validated['volume'] = $item->bidang === 'teknik' ? $validated['quantity'] : null;
        $validated['ship_unloader'] = $item->bidang === 'teknik'
            ? ($this->normalizeShipUnloader($validated['ship_unloader'] ?? []) ?: $item->ship_unloader)
            : null;
        $validated['status'] = $item->bidang === 'teknik' ? 'approved' : 'pending';
        $validated['approved_by'] = $item->bidang === 'teknik' ? auth()->id() : null;
        $validated['approved_at'] = $item->bidang === 'teknik' ? now() : null;
        $validated['price'] = $validated['price'] ?? null;

        Transaction::create($validated);

        $successMsg = $item->bidang === 'teknik'
            ? 'Transaksi Teknik berhasil ditambahkan dan otomatis approved.'
            : 'Transaksi berhasil ditambahkan dan menunggu approval dari Admin.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->route('transactions.index')->with('success', $successMsg);
    }

    public function edit(Transaction $transaction, Request $request)
    {
        if ($transaction->status !== 'pending') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Hanya transaksi berstatus pending yang bisa diubah.'], 422);
            }
            return redirect()->route('dashboard')->with('error', 'Hanya transaksi berstatus pending yang bisa diubah.');
        }

        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        // AJAX request returns JSON data for modal pre-fill
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'id' => $transaction->id,
                'item_id' => $transaction->item_id,
                'date' => $transaction->date->format('Y-m-d'),
                'type' => $transaction->type,
                'quantity' => $transaction->quantity,
                'price' => $transaction->price,
                'description' => $transaction->description,
                'no_normalisasi' => $transaction->no_normalisasi,
                'lokasi' => $transaction->lokasi,
                'volume' => $transaction->quantity,
                'ship_unloader' => $transaction->ship_unloader ? explode(',', $transaction->ship_unloader) : [],
                'item' => [
                    'category' => $transaction->item->category ?? '',
                    'unit' => $transaction->item->unit ?? '',
                    'current_stock' => $transaction->item->current_stock ?? 0,
                    'no_normalisasi' => $transaction->item->no_normalisasi ?? '',
                    'lokasi' => $transaction->item->lokasi ?? '',
                    'volume' => $transaction->item->current_stock ?? '',
                    'ship_unloader' => $transaction->item->ship_unloader ?? '',
                ],
            ]);
        }

        return redirect()->route('transactions.index');
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            $errorMsg = 'Hanya transaksi berstatus pending yang bisa diubah.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return redirect()->route('dashboard')->with('error', $errorMsg);
        }

        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:in,out',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'ship_unloader' => 'nullable|array',
            'ship_unloader.*' => 'in:1,2,3,4',
        ]);

        $item = Item::findOrFail($validated['item_id']);
        abort_unless(auth()->user()->canAccessBidang($item->bidang), 403, 'Anda tidak memiliki akses ke barang bidang ini.');

        if ($validated['type'] === 'out') {
            if ($item->current_stock < $validated['quantity']) {
                $errorMsg = 'Stok tidak mencukupi. Stok saat ini: ' . $item->current_stock . ' ' . $item->unit;
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return back()->withInput()->with('error', $errorMsg);
            }
        }

        $validated['price'] = $validated['price'] ?? null;
        $validated['bidang'] = $item->bidang;
        $validated['no_normalisasi'] = $item->no_normalisasi;
        $validated['lokasi'] = $item->lokasi;
        $validated['volume'] = $item->bidang === 'teknik' ? $validated['quantity'] : null;
        $validated['ship_unloader'] = $item->bidang === 'teknik'
            ? ($this->normalizeShipUnloader($validated['ship_unloader'] ?? []) ?: $item->ship_unloader)
            : null;

        $transaction->update($validated);

        $successMsg = 'Transaksi pending berhasil diperbarui.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->route('dashboard')->with('success', $successMsg);
    }

    public function destroy(Transaction $transaction)
    {
        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        if ($transaction->status !== 'pending') {
            return redirect()->route('dashboard')->with('error', 'Hanya transaksi berstatus pending yang bisa dihapus.');
        }

        $transaction->delete();

        return redirect()->route('dashboard')->with('success', 'Transaksi pending berhasil dihapus.');
    }

    public function approve(Transaction $transaction)
    {
        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Hanya transaksi pending yang bisa disetujui.');
        }

        if ($transaction->type === 'out') {
            $item = $transaction->item;
            if ($item && $item->current_stock < $transaction->quantity) {
                return back()->with('error', "Stok {$item->name} tidak mencukupi.");
            }
        }

        $transaction->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Transaksi berhasil disetujui.');
    }

    public function reject(Transaction $transaction)
    {
        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Hanya transaksi pending yang bisa ditolak.');
        }

        $transaction->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Transaksi berhasil ditolak.');
    }

    public function getItemDetails($id)
    {
        $item = Item::findOrFail($id);
        abort_unless(auth()->user()->canAccessBidang($item->bidang), 403, 'Anda tidak memiliki akses ke barang bidang ini.');

        return response()->json([
            'category' => $item->category,
            'unit' => $item->unit,
            'current_stock' => $item->current_stock,
            'no_normalisasi' => $item->no_normalisasi,
            'lokasi' => $item->lokasi,
            'volume' => $item->current_stock,
            'ship_unloader' => $item->ship_unloader,
            'ship_unloader_label' => $item->ship_unloader_label,
        ]);
    }

    private function normalizeShipUnloader(array $ships): ?string
    {
        $normalized = collect($ships)
            ->map(fn($ship) => (string) $ship)
            ->filter(fn($ship) => in_array($ship, ['1', '2', '3', '4'], true))
            ->unique()
            ->sort()
            ->values();

        return $normalized->isEmpty() ? null : $normalized->implode(',');
    }

    private function transactionDetailData($transactions): array
    {
        $data = [];

        foreach ($transactions as $txRow) {
            $data[$txRow->id] = [
                'date' => $txRow->date->format('d/m/Y'),
                'type' => $txRow->type_label,
                'no_normalisasi' => $txRow->no_normalisasi ?: ($txRow->item->no_normalisasi ?? '-'),
                'name' => $txRow->item->name ?? '-',
                'category' => $txRow->item->category ?? '-',
                'ship_unloader' => $txRow->ship_unloader_label,
                'lokasi' => $txRow->lokasi ?: ($txRow->item->lokasi ?? '-'),
                'volume' => $txRow->quantity,
                'quantity' => $txRow->quantity,
                'unit' => $txRow->item->unit ?? '-',
                'price' => $txRow->price === null ? '-' : 'Rp ' . number_format($txRow->price, 0, ',', '.'),
                'user' => $txRow->user->name ?? '-',
                'status' => $txRow->bidang === 'teknik' ? 'Approve' : ($txRow->status === 'pending' ? 'Menunggu Approval' : ucfirst($txRow->status)),
                'description' => $txRow->description ?: '-',
            ];
        }

        return $data;
    }
}
