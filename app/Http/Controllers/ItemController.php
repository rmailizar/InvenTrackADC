<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
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

        $items = $query->latest()->paginate(15)->withQueryString();
        $categories = Item::select('category')->distinct()->pluck('category');

        return view('items.index', compact('items', 'categories'));
    }

    public function create()
    {
        $categories = Item::select('category')->distinct()->pluck('category');
        $units = Item::select('unit')->distinct()->pluck('unit');
        return view('items.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|integer|min:0',
        ]);

        Item::create($validated);

        return redirect()->route('items.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(Item $item)
    {
        $categories = Item::select('category')->distinct()->pluck('category');
        $units = Item::select('unit')->distinct()->pluck('unit');
        return view('items.edit', compact('item', 'categories', 'units'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|integer|min:0',
        ]);

        $item->update($validated);

        return redirect()->route('items.index')->with('success', 'Barang berhasil diupdate.');
    }

    public function destroy(Item $item)
    {
        if ($item->transactions()->count() > 0) {
            return back()->with('error', 'Tidak bisa menghapus barang yang memiliki transaksi.');
        }

        $item->delete();

        return redirect()->route('items.index')->with('success', 'Barang berhasil dihapus.');
    }
}
