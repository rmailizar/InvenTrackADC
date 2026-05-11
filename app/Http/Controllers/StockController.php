<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockRequestLine;
use App\Models\Transaction;
use Illuminate\Http\Request;

class StockController extends Controller
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

        $stockRequestItems = $this->stockRequestTriggerItems();
        $pendingStockRequestItemIds = $this->pendingStockRequestItemIds();
        $stockRequestItems = $stockRequestItems->map(function ($item) use ($pendingStockRequestItemIds) {
            $item['has_pending_request'] = in_array($item['id'], $pendingStockRequestItemIds, true);

            return $item;
        });
        $requestOrderCount = $stockRequestItems->where('stock_status', 'request_order')->count();
        $outOfStockCount = $stockRequestItems->where('stock_status', 'out_of_stock')->count();

        if ($request->filled('stock_status') && $request->stock_status === 'low') {
            $items = $query->get()->filter(fn($item) => $item->is_low_stock);
            $categories = Item::visibleFor(auth()->user())->select('category')->distinct()->orderBy('category')->pluck('category');

            return view('stock.index', [
                'items' => $items,
                'categories' => $categories,
                'paginated' => false,
                'stockRequestItems' => $stockRequestItems,
                'requestOrderCount' => $requestOrderCount,
                'outOfStockCount' => $outOfStockCount,
            ]);
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = Item::visibleFor(auth()->user())->select('category')->distinct()->orderBy('category')->pluck('category');

        return view('stock.index', compact(
            'items',
            'categories',
            'stockRequestItems',
            'requestOrderCount',
            'outOfStockCount'
        ) + ['paginated' => true]);
    }

    private function stockRequestTriggerItems()
    {
        $year = now()->year;

        return Item::visibleFor(auth()->user())
            ->orderBy('name')
            ->get()
            ->map(function (Item $item) use ($year) {
                $stock = $item->current_stock;

                if ($stock <= 0) {
                    $status = 'out_of_stock';
                } elseif ($stock < $item->min_stock) {
                    $status = 'request_order';
                } else {
                    return null;
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'no_normalisasi' => $item->no_normalisasi,
                    'category' => $item->category,
                    'lokasi' => $item->lokasi,
                    'volume' => $stock,
                    'ship_unloader' => $item->stock_ship_unloader_label,
                    'unit' => $item->unit,
                    'current_stock' => $stock,
                    'min_stock' => $item->min_stock,
                    'price' => (int) Transaction::where('item_id', $item->id)
                        ->whereYear('date', $year)
                        ->max('price'),
                    'stock_status' => $status,
                ];
            })
            ->filter()
            ->values();
    }

    private function pendingStockRequestItemIds(): array
    {
        if (!auth()->check() || !auth()->user()->isStaff()) {
            return [];
        }

        return StockRequestLine::whereHas('stockRequest', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('status', 'pending');
            })
            ->pluck('item_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
