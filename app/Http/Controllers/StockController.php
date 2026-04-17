<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class StockController extends Controller
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

        if ($request->filled('stock_status') && $request->stock_status === 'low') {
            $items = $query->get()->filter(fn($item) => $item->is_low_stock);
            $categories = Item::select('category')->distinct()->pluck('category');
            return view('stock.index', ['items' => $items, 'categories' => $categories, 'paginated' => false]);
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = Item::select('category')->distinct()->pluck('category');

        return view('stock.index', compact('items', 'categories') + ['paginated' => true]);
    }
}
