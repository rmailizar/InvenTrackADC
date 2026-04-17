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
            'categoryData',
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
}
