<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Item;
use App\Exports\TransactionExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['item', 'user', 'approver'])->approved();

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
                $q->where('category', $request->category);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 🔥 FILTER HARGA (TERTINGGI / TERENDAH)
        if ($request->filled('price_filter') && $request->filled('year')) {

            if ($request->price_filter == 'tertinggi') {
                $query->whereIn('id', function ($sub) use ($request) {
                    $sub->select('t2.id')
                        ->from('transactions as t2')
                        ->whereYear('t2.date', $request->year)
                        ->whereColumn('t2.item_id', 'transactions.item_id')
                        ->whereRaw('t2.price = (
                    SELECT MAX(t3.price)
                    FROM transactions t3
                    WHERE t3.item_id = t2.item_id
                    AND YEAR(t3.date) = ?
                )', [$request->year]);
                });
            }

            if ($request->price_filter == 'terendah') {
                $query->whereIn('id', function ($sub) use ($request) {
                    $sub->select('t2.id')
                        ->from('transactions as t2')
                        ->whereYear('t2.date', $request->year)
                        ->whereColumn('t2.item_id', 'transactions.item_id')
                        ->whereRaw('t2.price = (
                    SELECT MIN(t3.price)
                    FROM transactions t3
                    WHERE t3.item_id = t2.item_id
                    AND YEAR(t3.date) = ?
                )', [$request->year]);
                });
            }
        }

        $transactions = $query->latest('date')->paginate(20)->withQueryString();
        $categories = Item::select('category')->distinct()->pluck('category');

        // Summary stats
        $totalMasuk = (clone $query)->where('type', 'masuk')->sum('quantity');
        $totalKeluar = (clone $query)->where('type', 'keluar')->sum('quantity');

        // 🔥 LIST TAHUN UNTUK DROPDOWN
        $years = Transaction::selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('reports.index', compact('transactions', 'categories', 'totalMasuk', 'totalKeluar', 'years'));
    }

    public function export(Request $request)
    {
        $filename = 'laporan_transaksi_' . now()->format('Y-m-d_His') . '.xlsx';

        $query = Transaction::with(['item', 'user', 'approver'])->approved();

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
                $q->where('category', $request->category);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 🔥 FILTER HARGA (HARUS SAMA DENGAN INDEX)
        if ($request->filled('price_filter') && $request->filled('year')) {

            if ($request->price_filter == 'tertinggi') {
                $query->whereIn('id', function ($sub) use ($request) {
                    $sub->select('t2.id')
                        ->from('transactions as t2')
                        ->whereYear('t2.date', $request->year)
                        ->whereColumn('t2.item_id', 'transactions.item_id')
                        ->whereRaw('t2.price = (
                        SELECT MAX(t3.price)
                        FROM transactions t3
                        WHERE t3.item_id = t2.item_id
                        AND YEAR(t3.date) = ?
                    )', [$request->year]);
                });
            }

            if ($request->price_filter == 'terendah') {
                $query->whereIn('id', function ($sub) use ($request) {
                    $sub->select('t2.id')
                        ->from('transactions as t2')
                        ->whereYear('t2.date', $request->year)
                        ->whereColumn('t2.item_id', 'transactions.item_id')
                        ->whereRaw('t2.price = (
                        SELECT MIN(t3.price)
                        FROM transactions t3
                        WHERE t3.item_id = t2.item_id
                        AND YEAR(t3.date) = ?
                    )', [$request->year]);
                });
            }
        }

        // 🔥 AMBIL DATA FINAL
        $data = $query->latest('date')->get();

        return Excel::download(new TransactionExport(
            $request->date_from,
            $request->date_to,
            $request->category,
            $request->type,
            $request->year,
            $request->price_filter
        ), $filename);
    }
}
