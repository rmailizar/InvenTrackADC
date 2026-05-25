<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query()->visibleFor(auth()->user());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('no_normalisasi', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $items = $query->latest()->paginate(15)->withQueryString();
        $categories = Item::visibleFor(auth()->user())->select('category')->distinct()->pluck('category');
        $units = Item::visibleFor(auth()->user())->select('unit')->distinct()->pluck('unit');

        return view('items.index', compact('items', 'categories', 'units'));
    }

    public function create()
    {
        return redirect()->route('items.index');
    }

    /**
     * Return item data as JSON for modal edit pre-fill
     */
    public function show(Item $item)
    {
        $this->authorizeItemDepartment($item);

        return response()->json($this->itemPayload($item));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|integer|min:0',
            'bidang' => auth()->user()->isSuperAdmin() ? 'required|in:teknik,umum' : 'nullable|in:teknik,umum',
            'no_normalisasi' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'current_stock' => 'nullable|integer|min:0',
            'ship_unloader' => 'nullable|array',
            'ship_unloader.*' => 'in:1,2,3,4',
        ]);

        $validated['bidang'] = auth()->user()->isSuperAdmin()
            ? $validated['bidang']
            : auth()->user()->bidang;
        $validated['ship_unloader'] = $this->normalizeShipUnloader($validated['ship_unloader'] ?? []);
        $validated['volume'] = null;

        if ($validated['bidang'] !== 'teknik') {
            $validated['no_normalisasi'] = null;
            $validated['lokasi'] = null;
            $validated['ship_unloader'] = null;
        }

        $currentStock = (int) ($validated['current_stock'] ?? 0);
        unset($validated['current_stock']);

        DB::transaction(function () use ($validated, $currentStock) {
            $item = Item::create($validated);

            if ($item->bidang === 'teknik' && $currentStock > 0) {
                $this->createStockAdjustment($item, $currentStock, 'Stok awal dari Master SOH');
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Barang berhasil ditambahkan.']);
        }

        return redirect()->route('items.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(Item $item, Request $request)
    {
        $this->authorizeItemDepartment($item);

        // AJAX request returns JSON data for modal pre-fill
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($this->itemPayload($item));
        }

        return redirect()->route('items.index');
    }

    public function update(Request $request, Item $item)
    {
        $this->authorizeItemDepartment($item);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|integer|min:0',
            'bidang' => auth()->user()->isSuperAdmin() ? 'required|in:teknik,umum' : 'nullable|in:teknik,umum',
            'no_normalisasi' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'current_stock' => 'nullable|integer|min:0',
            'ship_unloader' => 'nullable|array',
            'ship_unloader.*' => 'in:1,2,3,4',
        ]);

        $validated['bidang'] = auth()->user()->isSuperAdmin()
            ? $validated['bidang']
            : auth()->user()->bidang;
        $validated['ship_unloader'] = $this->normalizeShipUnloader($validated['ship_unloader'] ?? []);
        $validated['volume'] = null;

        if ($validated['bidang'] !== 'teknik') {
            $validated['no_normalisasi'] = null;
            $validated['lokasi'] = null;
            $validated['ship_unloader'] = null;
        }

        $targetStock = (int) ($validated['current_stock'] ?? $item->current_stock);
        unset($validated['current_stock']);

        DB::transaction(function () use ($item, $validated, $targetStock) {
            $item->update($validated);
            $item->refresh();

            if ($item->bidang === 'teknik') {
                $delta = $targetStock - $item->current_stock;

                if ($delta !== 0) {
                    $this->createStockAdjustment($item, abs($delta), 'Penyesuaian stok dari Master SOH', $delta > 0 ? 'in' : 'out');
                }
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Barang berhasil diupdate.']);
        }

        return redirect()->route('items.index')->with('success', 'Barang berhasil diupdate.');
    }

    public function destroy(Item $item)
    {
        $this->authorizeItemDepartment($item);

        $item->delete();

        return redirect()->route('items.index')->with('success', 'Barang berhasil dihapus.');
    }

    private function authorizeItemDepartment(Item $item): void
    {
        abort_unless(auth()->user()->canAccessBidang($item->bidang), 403, 'Anda tidak memiliki akses ke barang bidang ini.');
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

    private function itemPayload(Item $item): array
    {
        return array_merge($item->toArray(), [
            'current_stock' => $item->current_stock,
            'volume' => $item->current_stock,
        ]);
    }

    private function createStockAdjustment(Item $item, int $quantity, string $description, string $type = 'in'): void
    {
        Transaction::create([
            'item_id' => $item->id,
            'user_id' => auth()->id(),
            'bidang' => $item->bidang,
            'no_normalisasi' => $item->no_normalisasi,
            'lokasi' => $item->lokasi,
            'volume' => $quantity,
            'ship_unloader' => $item->ship_unloader,
            'date' => now()->toDateString(),
            'type' => $type,
            'quantity' => $quantity,
            'price' => null,
            'description' => $description,
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }
}
