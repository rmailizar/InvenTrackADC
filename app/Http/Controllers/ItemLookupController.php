<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemLookupController extends Controller
{
    public function index()
    {
        $categories = Item::visibleFor(auth()->user())->select('category')->distinct()->orderBy('category')->pluck('category');
        $components = Item::visibleFor(auth()->user())->select('component')->whereNotNull('component')->distinct()->orderBy('component')->pluck('component');
        $units = Item::visibleFor(auth()->user())->select('unit')->distinct()->orderBy('unit')->pluck('unit');
        $shipUnloaders = $this->shipUnloaders();

        return view('items.lookups', compact('categories', 'components', 'units', 'shipUnloaders'));
    }

    /**
     * Ganti nilai kategori atau satuan di semua barang (ubah nama / gabung / "hapus" dengan memindahkan ke nilai lain).
     */
    public function replace(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:category,component,unit,ship_unloader',
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:255',
        ]);

        if ($validated['type'] === 'ship_unloader') {
            return $this->replaceShipUnloader($validated);
        }

        $field = match ($validated['type']) {
            'category' => 'category',
            'component' => 'component',
            default => 'unit',
        };
        $from = trim($validated['from']);
        $to = trim($validated['to']);

        if ($from === $to) {
            return back()->with('error', 'Nilai lama dan baru tidak boleh sama.');
        }

        $query = Item::visibleFor(auth()->user())->where($field, $from);
        $count = (clone $query)->count();
        if ($count === 0) {
            return back()->with('error', 'Tidak ada barang yang memakai nilai tersebut.');
        }

        $query->update([$field => $to]);

        $label = match ($validated['type']) {
            'category' => 'Kategori',
            'component' => 'Komponen',
            default => 'Satuan',
        };

        return back()->with('success', "{$label} \"{$from}\" berhasil diubah menjadi \"{$to}\" pada {$count} barang.");
    }

    private function shipUnloaders()
    {
        if (!auth()->user()->isTeknik() && !auth()->user()->isSuperAdmin()) {
            return collect();
        }

        return Item::visibleFor(auth()->user())
            ->whereNotNull('ship_unloader')
            ->pluck('ship_unloader')
            ->flatMap(fn($value) => explode(',', (string) $value))
            ->map(fn($value) => trim($value))
            ->filter(fn($value) => in_array($value, ['1', '2', '3', '4'], true))
            ->unique()
            ->sort()
            ->values();
    }

    private function replaceShipUnloader(array $validated)
    {
        $from = trim($validated['from']);
        $to = trim($validated['to']);

        if (!in_array($from, ['1', '2', '3', '4'], true) || !in_array($to, ['1', '2', '3', '4'], true)) {
            return back()->with('error', 'Ship Unloader harus bernilai 1, 2, 3, atau 4.');
        }

        if ($from === $to) {
            return back()->with('error', 'Nilai lama dan baru tidak boleh sama.');
        }

        $items = Item::visibleFor(auth()->user())
            ->whereNotNull('ship_unloader')
            ->get()
            ->filter(function ($item) use ($from) {
                $ships = collect(explode(',', (string) $item->ship_unloader))
                    ->map(fn($ship) => trim($ship))
                    ->all();

                return in_array($from, $ships, true);
            });

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada barang yang memakai Ship Unloader tersebut.');
        }

        foreach ($items as $item) {
            $ships = collect(explode(',', (string) $item->ship_unloader))
                ->map(fn($ship) => trim($ship))
                ->filter(fn($ship) => in_array($ship, ['1', '2', '3', '4'], true))
                ->map(fn($ship) => $ship === $from ? $to : $ship)
                ->unique()
                ->sort()
                ->values();

            $item->update(['ship_unloader' => $ships->isEmpty() ? null : $ships->implode(',')]);
        }

        return back()->with('success', "Ship Unloader {$from} berhasil dipindahkan ke Ship Unloader {$to} pada {$items->count()} barang.");
    }
}
