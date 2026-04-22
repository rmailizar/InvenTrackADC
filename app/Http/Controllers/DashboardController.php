<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Transaction;
use App\Mail\LowStockAlert;
use App\Support\InventoryMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Staff should not access dashboard
        if ($user->isStaff()) {
            return redirect()->route('transactions.index');
        }

        $now = Carbon::now();

        // Stats cards
        $totalItems = Item::count();
        $masukBulanIni = Transaction::approved()->masuk()
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('quantity');
        $keluarBulanIni = Transaction::approved()->keluar()
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('quantity');
        $pendingCount = Transaction::pending()->count();

        // Low stock items
        $allItems = Item::all();
        $lowStockItems = $allItems->filter(fn($item) => $item->is_low_stock && $item->current_stock >= 0);

        // Chart data: monthly masuk vs keluar (last 12 months)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthLabel = $month->translatedFormat('M Y');

            $masuk = Transaction::approved()->masuk()
                ->whereMonth('date', $month->month)
                ->whereYear('date', $month->year)
                ->sum('quantity');

            $keluar = Transaction::approved()->keluar()
                ->whereMonth('date', $month->month)
                ->whereYear('date', $month->year)
                ->sum('quantity');

            $monthlyData[] = [
                'label' => $monthLabel,
                'masuk' => (int) $masuk,
                'keluar' => (int) $keluar,
            ];
        }

        // Yearly chart data: last 5 years
        $yearlyData = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = $now->copy()->subYears($i)->year;
            $masuk = Transaction::approved()->masuk()
                ->whereYear('date', $year)->sum('quantity');
            $keluar = Transaction::approved()->keluar()
                ->whereYear('date', $year)->sum('quantity');
            $yearlyData[] = [
                'label' => (string) $year,
                'masuk' => (int) $masuk,
                'keluar' => (int) $keluar,
            ];
        }

        // Available years for category filter
        $availableYears = Transaction::approved()
            ->selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        // Stock per category
        $categories = Item::select('category')->distinct()->pluck('category');
        $categoryData = [];
        foreach ($categories as $cat) {
            $items = Item::where('category', $cat)->get();
            $totalStock = $items->sum(fn($item) => $item->current_stock);
            $categoryData[] = [
                'category' => $cat,
                'stock' => max(0, $totalStock),
            ];
        }

        // Top 5 items most keluar
        $topKeluar = Transaction::approved()->keluar()
            ->selectRaw('item_id, SUM(quantity) as total')
            ->groupBy('item_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('item')
            ->get();

        // Recent transactions
        $recentTransactions = Transaction::with(['item', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // Pending transactions grouped by date (for admin daily approval)
        $pendingByDate = [];
        if ($user->isAdmin()) {
            $pendingByDate = Transaction::pending()
                ->with(['item', 'user'])
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy(function ($tx) {
                    return $tx->date->format('Y-m-d');
                });
        }

        return view('dashboard.index', compact(
            'totalItems',
            'masukBulanIni',
            'keluarBulanIni',
            'pendingCount',
            'lowStockItems',
            'monthlyData',
            'yearlyData',
            'categoryData',
            'availableYears',
            'topKeluar',
            'recentTransactions',
            'pendingByDate'
        ));
    }

    /**
     * Approve all pending transactions for a specific date
     */
    public function approveByDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = $request->date;

        $transactions = Transaction::pending()->whereDate('date', $date)->get();

        if ($transactions->isEmpty()) {
            return back()->with('error', 'Tidak ada transaksi pending untuk tanggal ini.');
        }

        // Check stock for all keluar transactions first
        foreach ($transactions as $tx) {
            if ($tx->type === 'keluar') {
                $item = $tx->item;
                if ($item->current_stock < $tx->quantity) {
                    return back()->with('error', "Stok {$item->name} tidak mencukupi ({$item->current_stock} {$item->unit}). Tidak bisa approve.");
                }
            }
        }

        // Approve all
        $count = 0;
        foreach ($transactions as $tx) {
            $tx->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            $count++;
        }

        // Check low stock alerts after approval
        $itemIds = $transactions->pluck('item_id')->unique();
        foreach ($itemIds as $itemId) {
            $item = Item::find($itemId);
            if ($item && $item->is_low_stock) {
                $recipients = InventoryMail::adminNotificationRecipients();
                if ($recipients === []) {
                    Log::warning('Email stok rendah tidak dikirim: tidak ada alamat admin (periksa user role=admin dan email, atau set INVENTORY_ALERT_MAIL di .env).');
                } else {
                    foreach ($recipients as $email) {
                        try {
                            Mail::to($email)->send(new LowStockAlert($item));
                        } catch (\Throwable $e) {
                            Log::error('Gagal kirim email stok rendah', [
                                'to' => $email,
                                'item_id' => $item->id,
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        // Sync to Google Sheets
        try {
            app(\App\Http\Controllers\GoogleSheetController::class)->syncAllApprovedToSheet();
        } catch (\Exception $e) {
            Log::error('Google Sheets sync after approval failed: ' . $e->getMessage());
        }

        return back()->with('success', "{$count} transaksi tanggal " . Carbon::parse($date)->format('d/m/Y') . " berhasil di-approve.");
    }

    /**
     * Reject all pending transactions for a specific date
     */
    public function rejectByDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = $request->date;

        $count = Transaction::pending()
            ->whereDate('date', $date)
            ->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        if ($count === 0) {
            return back()->with('error', 'Tidak ada transaksi pending untuk tanggal ini.');
        }

        return back()->with('success', "{$count} transaksi tanggal " . Carbon::parse($date)->format('d/m/Y') . " berhasil di-reject.");
    }

    /**
     * AJAX: Search items for autocomplete
     */
    public function searchItems(Request $request)
    {
        $q = $request->get('q', '');
        $items = Item::where('name', 'like', "%{$q}%")
            ->select('id', 'name', 'category')
            ->limit(10)
            ->get();
        return response()->json($items);
    }

    /**
     * AJAX: Get monthly + yearly chart data, optionally filtered by item_id
     */
    public function chartData(Request $request)
    {
        $itemId = $request->get('item_id');
        $now = Carbon::now();

        // Monthly (last 12 months)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthLabel = $month->translatedFormat('M Y');

            $masukQuery = Transaction::approved()->masuk()
                ->whereMonth('date', $month->month)
                ->whereYear('date', $month->year);
            $keluarQuery = Transaction::approved()->keluar()
                ->whereMonth('date', $month->month)
                ->whereYear('date', $month->year);

            if ($itemId) {
                $masukQuery->where('item_id', $itemId);
                $keluarQuery->where('item_id', $itemId);
            }

            $monthlyData[] = [
                'label' => $monthLabel,
                'masuk' => (int) $masukQuery->sum('quantity'),
                'keluar' => (int) $keluarQuery->sum('quantity'),
            ];
        }

        // Yearly (last 5 years)
        $yearlyData = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = $now->copy()->subYears($i)->year;

            $masukQuery = Transaction::approved()->masuk()->whereYear('date', $year);
            $keluarQuery = Transaction::approved()->keluar()->whereYear('date', $year);

            if ($itemId) {
                $masukQuery->where('item_id', $itemId);
                $keluarQuery->where('item_id', $itemId);
            }

            $yearlyData[] = [
                'label' => (string) $year,
                'masuk' => (int) $masukQuery->sum('quantity'),
                'keluar' => (int) $keluarQuery->sum('quantity'),
            ];
        }

        return response()->json(compact('monthlyData', 'yearlyData'));
    }

    /**
     * AJAX: Get stock per category filtered by year
     */
    public function categoryByYear(Request $request)
    {
        $year = $request->get('year');
        $categories = Item::select('category')->distinct()->pluck('category');
        $data = [];

        foreach ($categories as $cat) {
            $itemIds = Item::where('category', $cat)->pluck('id');

            $masukQuery = Transaction::approved()->masuk()->whereIn('item_id', $itemIds);
            $keluarQuery = Transaction::approved()->keluar()->whereIn('item_id', $itemIds);

            if ($year) {
                $masukQuery->whereYear('date', $year);
                $keluarQuery->whereYear('date', $year);
            }

            $masuk = $masukQuery->sum('quantity');
            $keluar = $keluarQuery->sum('quantity');

            $data[] = [
                'category' => $cat,
                'stock' => max(0, (int)($masuk - $keluar)),
            ];
        }

        return response()->json($data);
    }
}
