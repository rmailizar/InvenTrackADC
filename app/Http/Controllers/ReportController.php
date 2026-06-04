<?php

namespace App\Http\Controllers;

use App\Exports\StockRecapExport;
use App\Exports\TransactionExport;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $activeTable = $request->input('table', 'transactions') === 'stock' ? 'stock' : 'transactions';
        $categoryColumn = auth()->user()?->isTeknik() ? 'component' : 'category';
        $categories = Item::visibleFor(auth()->user())
            ->select($categoryColumn)
            ->whereNotNull($categoryColumn)
            ->distinct()
            ->orderBy($categoryColumn)
            ->pluck($categoryColumn);
        $years = Transaction::visibleFor(auth()->user())
            ->selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($activeTable === 'stock') {
            $stockRows = $this->buildStockRows($request);
            $stockItems = $this->paginateCollection($stockRows, 20, $request);

            $totalMasuk = $stockRows->sum('masuk');
            $totalKeluar = $stockRows->sum('keluar');
            $totalAkhir = $stockRows->sum('stok_akhir');

            return view('reports.index', compact(
                'activeTable',
                'categories',
                'years',
                'stockItems',
                'totalMasuk',
                'totalKeluar',
                'totalAkhir'
            ));
        }

        $query = $this->transactionQuery($request);
        $totalMasuk = (clone $query)->where('type', 'in')->sum('quantity');
        $totalKeluar = (clone $query)->where('type', 'out')->sum('quantity');
        $totalAkhir = $totalMasuk - $totalKeluar;
        $sort = $request->input('sort', 'latest') === 'oldest' ? 'asc' : 'desc';
        $transactions = $query
            ->orderBy('date', $sort)
            ->orderBy('created_at', $sort)
            ->orderBy('id', $sort)
            ->paginate(20)
            ->withQueryString();

        if (!$request->has('inventrack_section') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'html' => view('reports.partials.transactions-table', compact('transactions'))->render(),
                'sort' => $request->input('sort', 'latest') === 'oldest' ? 'oldest' : 'latest',
            ]);
        }

        return view('reports.index', compact(
            'activeTable',
            'transactions',
            'categories',
            'years',
            'totalMasuk',
            'totalKeluar',
            'totalAkhir'
        ));
    }

    public function export(Request $request)
    {
        if ($request->input('table') === 'stock') {
            $filename = 'laporan_rekap_stok_' . now()->format('Y-m-d_His') . '.xlsx';

            return Excel::download(new StockRecapExport(
                null,
                null,
                null,
                $request->category,
                $request->search,
                $request->stock_status
            ), $filename);
        }

        $filename = 'laporan_transaksi_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new TransactionExport(
            $request->date_from,
            $request->date_to,
            $request->category,
            $request->type,
            $request->year,
            $request->price_filter,
            $request->sort
        ), $filename);
    }

    private function transactionQuery(Request $request)
    {
        $query = Transaction::with(['item', 'user', 'approver'])
            ->visibleFor(auth()->user())
            ->approved();

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        if ($request->filled('category')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where(auth()->user()?->isTeknik() ? 'component' : 'category', $request->category);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if (!auth()->user()?->isTeknik() && $request->filled('price_filter') && $request->filled('year')) {
            $operator = $request->price_filter === 'tertinggi' ? 'MAX' : null;
            $operator = $request->price_filter === 'terendah' ? 'MIN' : $operator;

            if ($operator) {
                $query->whereIn('id', function ($sub) use ($request, $operator) {
                    $sub->select('t2.id')
                        ->from('transactions as t2')
                        ->whereYear('t2.date', $request->year)
                        ->whereNotNull('t2.price')
                        ->whereColumn('t2.item_id', 'transactions.item_id')
                        ->whereRaw("t2.price = (
                            SELECT {$operator}(t3.price)
                            FROM transactions t3
                            WHERE t3.item_id = t2.item_id
                            AND t3.price IS NOT NULL
                            AND YEAR(t3.date) = ?
                        )", [$request->year]);
                });
            }
        }

        return $query;
    }

    private function buildStockRows(Request $request)
    {
        $items = Item::query()
            ->visibleFor(auth()->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('component', 'like', "%{$search}%")
                        ->orWhere('no_normalisasi', 'like', "%{$search}%")
                        ->orWhere('lokasi', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn($query) => $query->where(auth()->user()?->isTeknik() ? 'component' : 'category', $request->category))
            ->orderBy('name')
            ->get();

        $rows = $items->map(function (Item $item) {
            $masuk = $item->transactions()->approved()->masuk()->sum('quantity');
            $keluar = $item->transactions()->approved()->keluar()->sum('quantity');
            $stokAkhir = $masuk - $keluar;

            return (object) [
                'item' => $item,
                'stok_awal' => 0,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'stok_akhir' => $stokAkhir,
            ];
        });

        if ($request->filled('stock_status')) {
            $rows = $rows->filter(function ($row) use ($request) {
                return match ($request->stock_status) {
                    'low' => $row->stok_akhir <= $row->item->min_stock,
                    default => true,
                };
            });
        }

        return $rows->values();
    }

    private function paginateCollection($collection, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
