<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemLookupController extends Controller
{
    public function index()
    {
        $categories = Item::query()->select('category')->distinct()->orderBy('category')->pluck('category');
        $units = Item::query()->select('unit')->distinct()->orderBy('unit')->pluck('unit');

        return view('items.lookups', compact('categories', 'units'));
    }

    /**
     * Ganti nilai kategori atau satuan di semua barang (ubah nama / gabung / "hapus" dengan memindahkan ke nilai lain).
     */
    public function replace(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:category,unit',
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:255',
        ]);

        $field = $validated['type'] === 'category' ? 'category' : 'unit';
        $from = trim($validated['from']);
        $to = trim($validated['to']);

        if ($from === $to) {
            return back()->with('error', 'Nilai lama dan baru tidak boleh sama.');
        }

        $count = Item::where($field, $from)->count();
        if ($count === 0) {
            return back()->with('error', 'Tidak ada barang yang memakai nilai tersebut.');
        }

        Item::where($field, $from)->update([$field => $to]);

        $label = $validated['type'] === 'category' ? 'Kategori' : 'Satuan';

        return back()->with('success', "{$label} \"{$from}\" berhasil diubah menjadi \"{$to}\" pada {$count} barang.");
    }
}
