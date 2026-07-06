<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        // Super Admin bidang tab switching
        $saBidang = $this->superAdminBidangContext($request);
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $viewUser = $saBidang ? $this->createBidangProxy($saBidang) : auth()->user();

        $query = Item::query()->visibleFor($viewUser);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('no_normalisasi', 'like', "%{$search}%")
                    ->orWhere('component', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if (!$viewUser->isTeknik() && $request->filled('category')) {
            $query->where('category', $request->category);
        }

        $stockSummary = null;

        if ($viewUser->isTeknik()) {
            $statusItems = (clone $query)->latest()->get();
            $stockRows = $statusItems->map(fn($item) => [
                'item' => $item,
                'stock' => $item->current_stock,
                'min_stock' => $item->min_stock,
            ]);
            $stockSummary = [
                'total' => $stockRows->sum(fn($row) => max(0, $row['stock'])),
                'low' => $stockRows->filter(fn($row) => $row['stock'] == $row['min_stock'])->count(),
                'critical' => $stockRows->filter(fn($row) => $row['stock'] < $row['min_stock'])->count(),
            ];

            if ($request->filled('stock_status')) {
                $filteredItems = (match ($request->stock_status) {
                    'low' => $stockRows->filter(fn($row) => $row['stock'] == $row['min_stock'])->pluck('item'),
                    'critical' => $stockRows->filter(fn($row) => $row['stock'] < $row['min_stock'])->pluck('item'),
                    default => $statusItems,
                })->values();

                $page = LengthAwarePaginator::resolveCurrentPage();
                $perPage = 15;
                $items = new LengthAwarePaginator(
                    $filteredItems->forPage($page, $perPage),
                    $filteredItems->count(),
                    $perPage,
                    $page,
                    [
                        'path' => $request->url(),
                        'query' => $request->query(),
                    ]
                );
            } else {
                $items = $query->latest()->paginate(15)->withQueryString();
            }
        } else {
            $items = $query->latest()->paginate(15)->withQueryString();
        }

        $categories = Item::visibleFor($viewUser)->select('category')->distinct()->pluck('category');
        $components = Item::visibleFor($viewUser)->select('component')->whereNotNull('component')->distinct()->pluck('component');
        $units = Item::visibleFor($viewUser)->select('unit')->distinct()->pluck('unit');

        return view('items.index', compact('items', 'categories', 'components', 'units', 'stockSummary', 'saBidang', 'isSuperAdmin'));
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
            'component' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|integer|min:0',
            'bidang' => auth()->user()->isSuperAdmin() ? 'required|in:teknik,umum' : 'nullable|in:teknik,umum',
            'no_normalisasi' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'volume' => 'nullable|integer|min:0',
        ]);

        $validated['bidang'] = auth()->user()->isSuperAdmin()
            ? $validated['bidang']
            : auth()->user()->bidang;
        $validated['volume'] = $validated['bidang'] === 'teknik' ? ($validated['volume'] ?? null) : null;
        $validated['component'] = $validated['bidang'] === 'teknik' ? ($validated['component'] ?? null) : null;
        $validated['ship_unloader'] = null;

        if ($validated['bidang'] !== 'teknik') {
            $validated['no_normalisasi'] = null;
            $validated['lokasi'] = null;
        }

        Item::create($validated);

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
            'component' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|integer|min:0',
            'bidang' => auth()->user()->isSuperAdmin() ? 'required|in:teknik,umum' : 'nullable|in:teknik,umum',
            'no_normalisasi' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'volume' => 'nullable|integer|min:0',
        ]);

        $validated['bidang'] = auth()->user()->isSuperAdmin()
            ? $validated['bidang']
            : auth()->user()->bidang;
        $validated['volume'] = $validated['bidang'] === 'teknik' ? ($validated['volume'] ?? null) : null;
        $validated['component'] = $validated['bidang'] === 'teknik' ? ($validated['component'] ?? null) : null;
        unset($validated['ship_unloader']);

        if ($validated['bidang'] !== 'teknik') {
            $validated['no_normalisasi'] = null;
            $validated['lokasi'] = null;
        }

        $item->update($validated);

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
            'stock_ship_unloader' => $item->stock_ship_unloader,
        ]);
    }
}
