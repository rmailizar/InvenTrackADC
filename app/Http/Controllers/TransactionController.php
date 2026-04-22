<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Transaction;
use App\Mail\NewTransactionNotification;
use App\Support\InventoryMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['item', 'user', 'approver']);

        // Staff can only see their own transactions
        if (auth()->user()->isStaff()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
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

        $transactions = $query->latest()->paginate(15)->withQueryString();

        // Pass items for modal dropdown
        $items = Item::orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'items'));
    }

    public function create()
    {
        return redirect()->route('transactions.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'date' => 'required|date',
            'type' => 'required|in:masuk,keluar',
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['price'] = $request->filled('price') ? (int) $request->price : 0;

        $item = Item::findOrFail($validated['item_id']);

        // Check stock for keluar
        if ($validated['type'] === 'keluar') {
            if ($item->current_stock < $validated['quantity']) {
                $errorMsg = 'Stok tidak mencukupi. Stok saat ini: ' . $item->current_stock . ' ' . $item->unit;
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return back()->withInput()->with('error', $errorMsg);
            }
        }

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        $transaction = Transaction::create($validated);

        $successMsg = 'Transaksi berhasil ditambahkan dan menunggu approval dari Admin.';

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
                'item' => [
                    'category' => $transaction->item->category ?? '',
                    'unit' => $transaction->item->unit ?? '',
                    'current_stock' => $transaction->item->current_stock ?? 0,
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

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'date' => 'required|date',
            'type' => 'required|in:masuk,keluar',
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['price'] = $request->filled('price') ? (int) $request->price : 0;

        $item = Item::findOrFail($validated['item_id']);

        if ($validated['type'] === 'keluar') {
            if ($item->current_stock < $validated['quantity']) {
                $errorMsg = 'Stok tidak mencukupi. Stok saat ini: ' . $item->current_stock . ' ' . $item->unit;
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return back()->withInput()->with('error', $errorMsg);
            }
        }

        $transaction->update($validated);

        $successMsg = 'Transaksi pending berhasil diperbarui.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->route('dashboard')->with('success', $successMsg);
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return redirect()->route('dashboard')->with('error', 'Hanya transaksi berstatus pending yang bisa dihapus.');
        }

        $transaction->delete();

        return redirect()->route('dashboard')->with('success', 'Transaksi pending berhasil dihapus.');
    }

    public function getItemDetails($id)
    {
        $item = Item::findOrFail($id);
        return response()->json([
            'category' => $item->category,
            'unit' => $item->unit,
            'current_stock' => $item->current_stock,
        ]);
    }
}
