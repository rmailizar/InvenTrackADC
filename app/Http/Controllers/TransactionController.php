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

        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $items = Item::orderBy('name')->get();
        $categories = Item::select('category')->distinct()->pluck('category');
        $units = Item::select('unit')->distinct()->pluck('unit');

        return view('transactions.create', compact('items', 'categories', 'units'));
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
                return back()->withInput()->with('error', 'Stok tidak mencukupi. Stok saat ini: ' . $item->current_stock . ' ' . $item->unit);
            }
        }

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        $transaction = Transaction::create($validated);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil ditambahkan dan menunggu approval dari Admin.');
    }

    public function edit(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return redirect()->route('dashboard')->with('error', 'Hanya transaksi berstatus pending yang bisa diubah.');
        }

        $items = Item::orderBy('name')->get();

        return view('transactions.edit', compact('transaction', 'items'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return redirect()->route('dashboard')->with('error', 'Hanya transaksi berstatus pending yang bisa diubah.');
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
                return back()->withInput()->with('error', 'Stok tidak mencukupi. Stok saat ini: ' . $item->current_stock . ' ' . $item->unit);
            }
        }

        $transaction->update($validated);

        return redirect()->route('dashboard')->with('success', 'Transaksi pending berhasil diperbarui.');
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
